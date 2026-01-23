<?php

namespace App\Notifications;

use App\Models\Request as BookRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestDueTomorrowNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public BookRequest $request) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $r = $this->request;

        return (new MailMessage)
            ->subject("Reminder: request #{$r->number} due tomorrow")
            ->greeting("Hello,")
            ->line("This is a reminder that your request is due tomorrow.")
            ->line("Request number: #{$r->number}")
            ->line("Book: {$r->book_name}")
            ->line("Due at: " . $r->due_at?->format('Y-m-d H:i'))
            ->line("Please return the book on time.");
    }
}

