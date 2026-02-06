<?php

namespace App\Services\Books;

use App\Models\Book;
use App\Models\BookAvailabilityAlert;
use App\Models\Request as BookRequest;
use App\Notifications\BookAvailableNotification;

class BookAvailabilityAlertService
{
    public function notifyIfAvailable(int $bookId): void
    {
        $book = Book::query()->find($bookId);
        if (! $book) {
            return;
        }

        $hasActive = BookRequest::query()
            ->where('book_id', $bookId)
            ->whereIn('status', [
                BookRequest::STATUS_ACTIVE,
                BookRequest::STATUS_AWAITING_CONFIRMATION,
            ])
            ->exists();

        if ($hasActive) {
            return;
        }

        $alerts = BookAvailabilityAlert::query()
            ->with('user:id,email')
            ->where('book_id', $bookId)
            ->get();

        if ($alerts->isEmpty()) {
            return;
        }

        foreach ($alerts as $alert) {
            if ($alert->user?->email) {
                $alert->user->notify(
                    (new BookAvailableNotification($book))
                );
            }
        }

        BookAvailabilityAlert::query()
            ->where('book_id', $bookId)
            ->delete();
    }
}
