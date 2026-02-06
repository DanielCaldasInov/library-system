<?php

namespace App\Notifications;

use App\Models\Book;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookAvailableNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Book $book)
    {
        $this->afterCommit();
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $bookUrl = url(route('books.show', $this->book->id, false));

        return (new MailMessage)
            ->subject('Book available: ' . ($this->book->name ?? 'Book'))
            ->view('emails/books/available_html', [
                'bookName' => $this->book->name ?? '—',
                'coverUrl' => $this->book->cover ?? null,
                'bookUrl' => $bookUrl,
                'appName' => config('app.name'),
            ]);
    }
}
