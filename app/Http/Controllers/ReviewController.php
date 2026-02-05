<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Review;
use App\Models\Request as BookRequest;
use App\Models\User;
use App\Notifications\ReviewEvaluatedNotification;
use App\Services\Emails\ReviewEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();
        $filter = $request->get('filter', 'citizen');
        $status = $request->get('status', 'all');

        $sort = $request->get('sort', 'created_at');
        $direction = strtolower($request->get('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = Review::query()
            ->with([
                'user:id,name,email',
                'book:id,name',
                'request:id,number',
            ])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search, $filter) {
                if ($filter === 'book') {
                    $q->whereHas('book', fn ($b) => $b->where('name', 'like', "%{$search}%"));
                    return;
                }

                $q->whereHas('user', fn ($u) =>
                $u->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                );
            });

        if (in_array($sort, ['rating', 'status', 'created_at'], true)) {
            $query->orderBy($sort, $direction);
        } elseif ($sort === 'citizen') {
            $query->orderBy(
                User::select('name')
                    ->whereColumn('users.id', 'reviews.user_id')
                    ->limit(1),
                $direction
            );
        } elseif ($sort === 'book') {
            $query->orderBy(
                Book::select('name')
                    ->whereColumn('books.id', 'reviews.book_id')
                    ->limit(1),
                $direction
            );
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $reviews = $query
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Reviews/Index', [
            'reviews' => $reviews,
            'filters' => [
                'search' => $search,
                'filter' => $filter,
                'status' => $status,
            ],
            'sort' => $sort,
            'direction' => $direction,
            'searchOptions' => [
                ['value' => 'citizen', 'label' => 'Citizen'],
                ['value' => 'book', 'label' => 'Book'],
            ],
            'statusOptions' => [
                ['value' => 'all', 'label' => 'All'],
                ['value' => 'pending', 'label' => 'Pending'],
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'rejected', 'label' => 'Rejected'],
            ],
        ]);
    }

    public function store(Request $httpRequest, BookRequest $request)
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        if ($request->user_id !== $user->id) {
            abort(403);
        }

        if (! in_array($request->status, [
            BookRequest::STATUS_AWAITING_CONFIRMATION,
            BookRequest::STATUS_COMPLETED,
        ], true)) {
            return back()->withErrors([
                'review' => 'You can only leave a review when the request is awaiting confirmation or completed.',
            ]);
        }

        if ($request->book_id === null) {
            return back()->withErrors([
                'review' => 'This request no longer has a book associated, so it cannot be reviewed.',
            ]);
        }

        if ($request->review()->exists()) {
            return back()->withErrors([
                'review' => 'You have already submitted a review for this request.',
            ]);
        }

        $validated = $httpRequest->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        $review = Review::create([
            'request_id' => $request->id,
            'book_id' => $request->book_id,
            'user_id' => $user->id,
            'rating' => (int) $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'status' => Review::STATUS_PENDING,
            'rejection_reason' => null,
        ]);

        $review->load([
            'user:id,name,email,profile_photo_path',
            'request:id,number,status',
            'book:id,name,cover',
        ]);

        app(ReviewEmailService::class)->sendReviewCreated($review);

        return back()->with('success', 'Review submitted successfully. It will be reviewed by an admin.');
    }

    public function show(Review $review)
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403);
        }

        $review->load([
            'user:id,name,email,created_at',
            'book:id,name,cover,ISBN,price',
            'request:id,number,status,requested_at,due_at,received_at,returned_at',
        ]);

        return Inertia::render('Reviews/Show', [
            'review' => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'status' => $review->status,
                'rejection_reason' => $review->rejection_reason,
                'created_at' => $review->created_at,
                'updated_at' => $review->updated_at,

                'user' => $review->user ? [
                    'id' => $review->user->id,
                    'name' => $review->user->name,
                    'email' => $review->user->email,
                    'created_at' => $review->user->created_at,
                ] : null,

                'book' => $review->book ? [
                    'id' => $review->book->id,
                    'name' => $review->book->name,
                    'cover' => $review->book->cover,
                    'ISBN' => $review->book->ISBN,
                    'price' => $review->book->price,
                ] : null,

                'request' => $review->request ? [
                    'id' => $review->request->id,
                    'number' => $review->request->number,
                    'status' => $review->request->status,
                    'requested_at' => $review->request->requested_at,
                    'due_at' => $review->request->due_at,
                    'returned_at' => $review->request->returned_at,
                    'received_at' => $review->request->received_at,
                ] : null,
            ],
        ]);
    }

    public function update(Request $request, Review $review)
    {
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:active,rejected'],
            'rejection_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validated['status'] === Review::STATUS_REJECTED) {
            $reason = trim((string) ($validated['rejection_reason'] ?? ''));
            if ($reason === '') {
                return back()->withErrors([
                    'rejection_reason' => 'Rejection reason is required when rejecting a review.',
                ]);
            }

            $review->status = Review::STATUS_REJECTED;
            $review->rejection_reason = $reason;
        } else {
            $review->status = Review::STATUS_ACTIVE;
            $review->rejection_reason = null;
        }

        $review->save();

        app(ReviewEmailService::class)
            ->sendReviewEvaluated($review);

        return redirect()
            ->route('reviews.show', $review)
            ->with('success', 'Review updated successfully.');
    }

}
