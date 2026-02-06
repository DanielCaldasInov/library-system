<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookAvailabilityAlert;
use App\Models\Request as BookRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookAvailabilityAlertController extends Controller
{
    public function store(Request $request, Book $book)
    {
        $user = Auth::user();
        if (! $user) {
            abort(403);
        }

        $hasActive = BookRequest::query()
            ->where('book_id', $book->id)
            ->whereIn('status', [
                BookRequest::STATUS_ACTIVE,
                BookRequest::STATUS_AWAITING_CONFIRMATION,
            ])
            ->exists();

        if (! $hasActive) {
            return back()->with('success', 'This book is already available.');
        }

        BookAvailabilityAlert::firstOrCreate([
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);

        return back()->with('success', 'Done! We will notify you when this book becomes available.');
    }
}
