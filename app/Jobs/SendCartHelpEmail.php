<?php

namespace App\Jobs;

use App\Models\Cart;
use App\Notifications\CartHelpNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCartHelpEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $cartId) {}

    public function handle(): void
    {
        $cart = Cart::query()
            ->with(['user:id,name,email', 'items:id,cart_id'])
            ->find($this->cartId);

        if (! $cart) return;

        if ($cart->status !== 'active') return;

        if ($cart->help_email_sent_at !== null) return;

        if ($cart->items->isEmpty()) return;

        if (! $cart->last_activity_at || $cart->last_activity_at->gt(now()->subHour())) {
            return;
        }

        if ($cart->user?->email) {
            $cart->user->notify(new CartHelpNotification($cart));
            $cart->forceFill(['help_email_sent_at' => now()])->save();
        }
    }
}
