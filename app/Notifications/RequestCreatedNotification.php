<?php

namespace App\Notifications;

use App\Models\Request as BookRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestCreatedNotification extends Notification implements ShouldQueue
{
use Queueable;

public function __construct(
public BookRequest $request,
public string $audience = 'citizen'
) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $r = $this->request;

        $subject = $this->audience === 'admin'
            ? "New request #{$r->number}"
            : "Your request #{$r->number} was created";

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.requests.created_html', [
                'audience' => $this->audience,
                'number' => $r->number,
                'bookName' => $r->book_name,
                'status' => $r->status,
                'requestedAt' => $r->requested_at?->format('Y-m-d H:i') ?? '—',
                'dueAt' => $r->due_at?->format('Y-m-d H:i') ?? '—',
                'coverUrl' => $r->book_cover,
                'requestUrl' => route('requests.show', $r->id),
            ]);
    }
}
