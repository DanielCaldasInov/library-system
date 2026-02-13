<?php

namespace App\Notifications;

use App\Models\Cart;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CartHelpNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Cart $cart) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Need help finishing your order?')
            ->view('emails/cart/help_html', [
                'citizenName' => $notifiable->name ?? '—',
                'cartUrl' => route('cart.index'),
                'checkoutUrl' => route('checkout.delivery'),
                'appName' => config('app.name'),
            ]);
    }
}
