<?php

namespace App\Notifications;

use App\Models\Review;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReviewCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Review $review
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $review = $this->review->loadMissing([
            'user:id,name,email,profile_photo_path',
            'book:id,name,cover',
            'request:id,number,status',
        ]);

        $reviewUrl = route('reviews.show', $review->id);

        $statusLabel = match ($review->request?->status) {
            'active' => 'Active',
            'awaiting_confirmation' => 'Awaiting confirmation',
            'completed' => 'Completed',
            'canceled' => 'Canceled',
            default => $review->request?->status ?? '—',
        };

        return (new MailMessage)
            ->subject('New review submitted (pending)')
            ->view('emails.reviews.created_html', [
                'citizenName' => $review->user?->name ?? '—',
                'citizenEmail' => $review->user?->email ?? '—',
                'citizenPhotoUrl' => $review->user?->profile_photo_url ?? null,

                'rating' => $review->rating,
                'comment' => $review->comment,
                'reviewStatus' => ucfirst($review->status),

                'bookName' => $review->book?->name ?? '—',
                'bookCoverUrl' => $review->book?->cover ?? null,

                'requestNumber' => $review->request?->number ?? '—',
                'requestStatus' => $statusLabel,

                'reviewUrl' => $reviewUrl,
            ]);
    }
}
