<?php

namespace App\Services\Emails;

use App\Models\Review;
use App\Models\User;
use App\Notifications\ReviewCreatedNotification;
use App\Notifications\ReviewEvaluatedNotification;

class ReviewEmailService
{
    public function sendReviewCreated(Review $review): void
    {
        $admins = User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'admin'))
            ->whereNotNull('email')
            ->get(['id', 'email']);

        foreach ($admins as $admin) {
            $admin->notify(
                (new ReviewCreatedNotification($review))
            );
        }
    }

    public function sendReviewEvaluated(Review $review): void
    {
        $review->loadMissing(['user:id,email']);

        if (! $review->user?->email) {
            return;
        }

        $review->user->notify(
            new ReviewEvaluatedNotification($review->id)
        );
    }
}
