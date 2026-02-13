<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Payments\StripeClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    public function delivery(Request $request)
    {
        $user = $request->user();

        $cart = Cart::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['items.book:id,name,cover,price'])
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        $total = $cart->items->sum(function ($item) {
            $priceCents = (int) round(((float) ($item->book?->price ?? 0)) * 100);
            $qty = (int) $item->qty;
            return $priceCents * max(1, $qty);
        });

        return Inertia::render('Checkout/Delivery', [
            'cart' => [
                'id' => $cart->id,
                'items' => $cart->items->map(fn ($it) => [
                    'id' => $it->id,
                    'qty' => $it->qty,
                    'book' => $it->book ? [
                        'id' => $it->book->id,
                        'name' => $it->book->name,
                        'cover' => $it->book->cover,
                        'price' => $it->book->price,
                    ] : null,
                ])->values(),
                'total' => $total,
                'currency' => 'EUR',
            ],
        ]);
    }

    public function store(Request $request, StripeClient $stripeClient)
    {
        $user = $request->user();

        $validated = $request->validate([
            'delivery_name' => ['required', 'string', 'max:255'],
            'delivery_address_line1' => ['required', 'string', 'max:255'],
            'delivery_address_line2' => ['nullable', 'string', 'max:255'],
            'delivery_zip' => ['required', 'string', 'max:20'],
            'delivery_city' => ['required', 'string', 'max:120'],
            'delivery_country' => ['required', 'string', 'size:2'],
        ]);

        $cart = Cart::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->with(['items.book:id,name,price'])
            ->first();

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Your cart is empty.');
        }

        $lineItems = [];
        $total = 0;

        foreach ($cart->items as $item) {
            $book = $item->book;
            if (! $book) {
                continue;
            }

            $unitAmount = (int) round(((float) $book->price) * 100);
            $qty = (int) $item->qty;

            if ($unitAmount <= 0 || $qty <= 0) {
                continue;
            }

            $total += $unitAmount * $qty;

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => $book->name,
                    ],
                    'unit_amount' => $unitAmount,
                ],
                'quantity' => $qty,
            ];
        }

        if ($total <= 0 || empty($lineItems)) {
            return redirect()
                ->route('cart.index')
                ->with('error', 'Cart total is invalid.');
        }

        $order = null;

        DB::transaction(function () use ($user, $cart, $validated, $total, &$order) {
            $order = Order::create([
                'user_id' => $user->id,
                'status' => 'pending_payment',
                'total_amount' => $total,
                'currency' => 'eur',

                'delivery_name' => $validated['delivery_name'],
                'delivery_address_line1' => $validated['delivery_address_line1'],
                'delivery_address_line2' => $validated['delivery_address_line2'] ?? null,
                'delivery_zip' => $validated['delivery_zip'],
                'delivery_city' => $validated['delivery_city'],
                'delivery_country' => strtoupper($validated['delivery_country']),
            ]);

            foreach ($cart->items as $item) {
                if (! $item->book) {
                    continue;
                }

                $unitAmount = (int) round(((float) $item->book->price) * 100);

                OrderItem::create([
                    'order_id' => $order->id,
                    'book_id' => $item->book->id,
                    'book_name' => $item->book->name,
                    'unit_price' => $unitAmount,
                    'qty' => (int) $item->qty,
                ]);
            }

            $cart->update([
                'status' => 'converted',
            ]);

            Cart::create([
                'user_id' => $user->id,
                'status' => 'active',
                'last_activity_at' => now(),
            ]);
        });

        $stripe = $stripeClient->client();

        $session = $stripe->checkout->sessions->create([
            'mode' => 'payment',
            'customer_email' => $user->email,
            'line_items' => $lineItems,

            'success_url' => route('checkout.success', ['order' => $order->id]) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.cancel', ['order' => $order->id]),

            'metadata' => [
                'order_id' => (string) $order->id,
                'user_id' => (string) $user->id,
            ],
        ]);

        $order->update([
            'stripe_checkout_session_id' => $session->id,
        ]);

        if ($request->header('X-Inertia')) {
            return Inertia::location($session->url);
        }

        return redirect()->away($session->url);
    }

    public function success(Request $request, Order $order)
    {
        $user = Auth::user();

        if (! $user || $order->user_id !== $user->id) {
            abort(403);
        }

        return redirect()
            ->to('/orders/' . $order->id)
            ->with('success', 'Payment completed! (Pending confirmation via webhook).');
    }

    public function cancel(Request $request, Order $order)
    {
        $user = Auth::user();

        if (! $user || $order->user_id !== $user->id) {
            abort(403);
        }

        return redirect()
            ->route('cart.index')
            ->with('error', 'Payment canceled.');
    }
}
