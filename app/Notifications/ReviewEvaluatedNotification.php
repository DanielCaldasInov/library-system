<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ReviewEvaluatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Review $review
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $book = $this->review->book;

        return (new MailMessage)
            ->subject('Your review has been evaluated')
            ->view('emails.reviews.evaluated_html', [
                'citizenName' => $notifiable->name,
                'rating' => $this->review->rating,
                'comment' => $this->review->comment,
                'status' => $this->review->status,
                'rejectionReason' => $this->review->rejection_reason,
                'bookName' => $book?->name,
                'bookCover' => $book?->cover,
                'bookUrl' => route('books.show', $book),
            ]);
    }
}
