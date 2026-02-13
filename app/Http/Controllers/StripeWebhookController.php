<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class StripeWebhookController extends Controller
{
    public function handle(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret = config('services.stripe.webhook_secret');

        if (! $secret) {
            Log::error('Stripe webhook secret is missing (STRIPE_WEBHOOK_SECRET).');
            return response('Webhook secret missing', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (Throwable $e) {
            Log::warning('Stripe webhook signature verification failed: '.$e->getMessage());
            return response('Invalid signature', Response::HTTP_BAD_REQUEST);
        }

        $type = $event->type ?? null;

        try {
            if ($type === 'checkout.session.completed') {
                /** @var \Stripe\Checkout\Session $session */
                $session = $event->data->object;

                $this->handleCheckoutCompleted($session);
            }

            if ($type === 'checkout.session.expired') {
                /** @var \Stripe\Checkout\Session $session */
                $session = $event->data->object;

                $this->handleCheckoutExpired($session);
            }
        } catch (Throwable $e) {
            Log::error('Stripe webhook processing error: '.$e->getMessage(), [
                'type' => $type,
                'event_id' => $event->id ?? null,
            ]);

            return response('Webhook processing failed', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return response('ok', Response::HTTP_OK);
    }

    private function handleCheckoutCompleted($session): void
    {
        $sessionId = $session->id ?? null;
        if (! $sessionId) return;

        $order = Order::query()
            ->where('stripe_checkout_session_id', $sessionId)
            ->first();

        if (! $order) {
            Log::warning('checkout.session.completed received but order not found', [
                'session_id' => $sessionId,
            ]);
            return;
        }

        if ($order->status === 'paid') {
            return;
        }

        $paymentIntentId = $session->payment_intent ?? null;

        $order->update([
            'status' => 'paid',
            'stripe_payment_intent_id' => $paymentIntentId,
        ]);

        //TODO: Implementar email com os detalhes do pagamento
    }

    private function handleCheckoutExpired($session): void
    {
        $sessionId = $session->id ?? null;
        if (! $sessionId) return;

        $order = Order::query()
            ->where('stripe_checkout_session_id', $sessionId)
            ->first();

        if (! $order) {
            Log::warning('checkout.session.expired received but order not found', [
                'session_id' => $sessionId,
            ]);
            return;
        }

        if ($order->status === 'paid') {
            return;
        }

        $order->update([
            'status' => 'expired',
        ]);
    }
}
