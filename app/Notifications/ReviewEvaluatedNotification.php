<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewEvaluatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $reviewId
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $review = Review::query()
            ->with([
                'book:id,name,cover',
            ])
            ->find($this->reviewId);

        // Se por algum motivo a review foi apagada, não quebra o job
        if (! $review) {
            return (new MailMessage)
                ->subject('Your review has been evaluated')
                ->line('Your review was evaluated, but the record is no longer available.')
                ->line('Thanks.');
        }

        $book = $review->book;

        return (new MailMessage)
            ->subject('Your review has been evaluated')
            ->view('emails.reviews.evaluated_html', [
                'citizenName' => $notifiable->name,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'status' => $review->status,
                'rejectionReason' => $review->rejection_reason,
                'bookName' => $book?->name ?? '—',
                'bookCover' => $book?->cover,
                'bookUrl' => $book ? route('books.show', $book->id) : url('/books'),
            ]);
    }
}
