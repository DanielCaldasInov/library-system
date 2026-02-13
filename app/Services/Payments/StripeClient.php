<?php

namespace App\Services\Payments;

use Stripe\StripeClient as SdkStripeClient;

class StripeClient
{
    private SdkStripeClient $client;

    public function __construct()
    {
        $secret = config('services.stripe.secret');

        if (!is_string($secret) || trim($secret) === '') {
            throw new \RuntimeException('Stripe secret key is not configured. Set STRIPE_SECRET in .env');
        }

        $this->client = new SdkStripeClient($secret);
    }

    public function client(): SdkStripeClient
    {
        return $this->client;
    }

    public function currency(): string
    {
        $cur = config('services.stripe.currency', 'eur');
        return is_string($cur) && $cur !== '' ? strtolower($cur) : 'eur';
    }
}
