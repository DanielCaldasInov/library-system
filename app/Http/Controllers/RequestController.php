<?php

namespace App\Http\Controllers;

use App\Jobs\SendRequestCreatedEmails;
use App\Models\Book;
use App\Models\Request as BookRequest;
use App\Models\Review;
use App\Services\Books\BookAvailabilityAlertService;
use App\Services\Emails\ReviewEmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $status = $request->get('status', 'all');
        $perPage = (int) ($request->get('per_page', 10));
        $filter = $request->get('filter', '');

        $sort = $request->get('sort', 'requested_at');
        $direction = $request->get('direction', 'desc');

        $searchOptions = [
            ['value' => 'number', 'label' => 'Number'],
            ['value' => 'book', 'label' => 'Book'],
        ];

        if ($user->isAdmin()) {
            $searchOptions[] = ['value' => 'citizen', 'label' => 'Citizen'];
        } else {
            if ($filter === 'citizen') {
                $filter = '';
            }
        }

        $query = BookRequest::query()
            ->with([
                'book' => fn ($q) => $q->select(['id', 'name', 'cover', 'publisher_id']),
                'book.publisher' => fn ($q) => $q->select(['id', 'name']),
                'citizen' => fn ($q) => $q->select(['id', 'name', 'email']),
                'receivedByAdmin' => fn ($q) => $q->select(['id', 'name']),
            ]);

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $allowedStatuses =  ['all', 'active', 'awaiting_confirmation', 'completed', 'canceled'];

        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($request->filled('search')) {
            $search = $request->get('search');

            $query->where(function ($q) use ($search, $user, $filter) {
                if ($filter === '' || $filter === null) {
                    $q->where('number', 'like', "%$search%")
                        ->orWhere('book_name', 'like', "%$search%")
                        ->orWhereHas('book', fn ($b) => $b->where('name', 'like', "%$search%"));

                    if ($user->isAdmin()) {
                        $q->orWhere('citizen_name', 'like', "%$search%")
                            ->orWhere('citizen_email', 'like', "%$search%");
                    }

                    return;
                }

                if ($filter === 'number') {
                    $q->where('number', 'like', "%$search%");
                    return;
                }

                if ($filter === 'book') {
                    $q->where('book_name', 'like', "%$search%")
                        ->orWhereHas('book', fn ($b) => $b->where('name', 'like', "%$search%"));
                    return;
                }

                if ($filter === 'citizen' && $user->isAdmin()) {
                    $q->where('citizen_name', 'like', "%$search%")
                        ->orWhere('citizen_email', 'like', "%$search%");
                    return;
                }

                $q->where('number', 'like', "%$search%");
            });
        }

        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        if ($sort === 'requested_at' || $sort === 'due_at') {
            $query->orderBy($sort, $direction);
        } elseif ($sort === 'book') {
            $query->orderByRaw("COALESCE(book_name, '') $direction");
        } elseif ($sort === 'citizen') {
            if ($user->isAdmin()) {
                $query->orderByRaw("COALESCE(citizen_name, '') $direction");
            } else {
                $query->orderByDesc('requested_at');
            }
        } else {
            $query->orderByDesc('requested_at');
        }

        $requests = $query->paginate($perPage)->withQueryString();

        $stats = null;
        if ($user->isAdmin()) {
            $stats = [
                'activeRequests' => BookRequest::query()
                    ->whereIn('status', [BookRequest::STATUS_ACTIVE, BookRequest::STATUS_AWAITING_CONFIRMATION])
                    ->count(),

                'requestsLast30Days' => BookRequest::query()
                    ->where('requested_at', '>=', now()->subDays(30))
                    ->count(),

                'deliveredToday' => BookRequest::query()
                    ->where('status', BookRequest::STATUS_COMPLETED)
                    ->whereDate('received_at', today())
                    ->count(),
            ];
        }

        return Inertia::render('Requests/Index', [
            'requests' => $requests,
            'filters' => [
                'status' => $status,
                'search' => $request->get('search', ''),
                'filter' => $filter,
                'per_page' => $perPage,
            ],
            'sort' => $sort,
            'direction' => $direction,
            'stats' => $stats,
            'searchOptions' => $searchOptions,
            'statusOptions' => [
                ['value' => 'all', 'label' => 'All'],
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'awaiting_confirmation', 'label' => 'Awaiting confirmation'],
                ['value' => 'completed', 'label' => 'Completed'],
                ['value' => 'canceled', 'label' => 'Canceled'],
            ],
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->user();

        // Available Books
        $availableBooks = Book::query()
            ->select(['id', 'name', 'cover', 'publisher_id'])
            ->with(['publisher:id,name', 'authors:id,name'])
            ->whereDoesntHave('requests', function ($q) {
                $q->whereIn('status', [BookRequest::STATUS_ACTIVE, BookRequest::STATUS_AWAITING_CONFIRMATION]);
            })
            ->orderBy('name')
            ->get();

        // Books borrowed by the user
        $activeCount = BookRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [BookRequest::STATUS_ACTIVE, BookRequest::STATUS_AWAITING_CONFIRMATION])
            ->count();

        return Inertia::render('Requests/Create', [
            'availableBooks' => $availableBooks,
            'activeCount' => $activeCount,
            'maxActive' => 3,
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        if (is_null($user->profile_photo_path)) {
            throw ValidationException::withMessages([
                'book_id' => 'You must upload a profile photo before creating a request.',
            ]);
        }

        $data = $request->validate([
            'book_id' => ['required', 'integer', 'exists:books,id'],
        ]);

        $activeCount = BookRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [BookRequest::STATUS_ACTIVE, BookRequest::STATUS_AWAITING_CONFIRMATION])
            ->count();

        if ($activeCount >= 3) {
            throw ValidationException::withMessages([
                'book_id' => 'You already have 3 active requests. Return a book before requesting another.',
            ]);
        }

        $book = Book::with(['publisher:id,name'])->findOrFail($data['book_id']);

        // Check if book is already borrowed
        $bookHasActive = BookRequest::query()
            ->where('book_id', $book->id)
            ->whereIn('status', [BookRequest::STATUS_ACTIVE, BookRequest::STATUS_AWAITING_CONFIRMATION])
            ->exists();

        if ($bookHasActive) {
            throw ValidationException::withMessages([
                'book_id' => 'This book is not available for request right now.',
            ]);
        }

        $requestedAt = now();
        $dueAt = now()->addDays(5);

        // Next sequential number
        $nextNumber = (BookRequest::max('number') ?? 0) + 1;

        $req = BookRequest::create([
            'number' => $nextNumber,

            'book_id' => $book->id,
            'user_id' => $user->id,

            'citizen_name' => $user->name,
            'citizen_email' => $user->email,
            'citizen_photo' => $user->profile_photo_path,

            'book_name' => $book->name,
            'book_cover' => $book->cover,

            'requested_at' => $requestedAt,
            'due_at' => $dueAt,

            'status' => BookRequest::STATUS_ACTIVE,
        ]);

        SendRequestCreatedEmails::dispatch($req->id);

        return redirect()
            ->route('requests.index')
            ->with('success', 'Request created successfully.');
    }

    public function show(BookRequest $request)
    {
        $user = Auth::user();

        if (! $user->isAdmin() && $request->user_id !== $user->id) {
            abort(403);
        }

        $request->load([
            'book.publisher',
            'book.authors',
            'citizen',
            'receivedByAdmin',
            'review',
        ]);

        $isOwner = $user && $request->user_id === $user->id;

        $canLeaveReview = $isOwner
            && in_array($request->status, [
                BookRequest::STATUS_AWAITING_CONFIRMATION,
                BookRequest::STATUS_COMPLETED,
            ], true)
            && $request->review === null
            && $request->book_id !== null;

        return Inertia::render('Requests/Show', [
            'request' => $request,
            'canLeaveReview' => $canLeaveReview,
        ]);
    }

    public function markReturned(BookRequest $request)
    {
        $user = Auth::user();

        if (! $user->isAdmin() && $request->user_id !== $user->id) {
            abort(403);
        }

        if (! $request->isActive()) {
            return back()->with('error', 'This request is not active.');
        }

        if ($request->status === BookRequest::STATUS_AWAITING_CONFIRMATION) {
            return back();
        }

        $request->update([
            'status' => BookRequest::STATUS_AWAITING_CONFIRMATION,
            'returned_at' => now(),
        ]);

        return back()->with('success', 'Marked as returned. Awaiting admin confirmation.');
    }

    public function confirmReceived(
        Request $httpRequest,
        BookRequest $request,
        BookAvailabilityAlertService $alerts,
        ReviewEmailService $reviewEmails
    ) {
        $user = Auth::user();

        if (! $user || ! $user->isAdmin()) {
            abort(403);
        }

        if ($request->status !== BookRequest::STATUS_AWAITING_CONFIRMATION) {
            return back()->withErrors([
                'request' => 'This request cannot be confirmed.',
            ]);
        }

        $review = $request->review;

        if ($review && $review->isPending()) {
            $validated = $httpRequest->validate([
                'review_action' => ['required', 'in:approve,reject'],
                'rejection_reason' => ['required_if:review_action,reject', 'nullable', 'string', 'max:2000'],
            ]);

            if ($validated['review_action'] === 'approve') {
                $review->update([
                    'status' => Review::STATUS_ACTIVE,
                    'rejection_reason' => null,
                ]);
            }

            if ($validated['review_action'] === 'reject') {
                $review->update([
                    'status' => Review::STATUS_REJECTED,
                    'rejection_reason' => $validated['rejection_reason'],
                ]);
            }

            $reviewEmails->sendReviewEvaluated($review);
        }

        $request->update([
            'status' => BookRequest::STATUS_COMPLETED,
            'received_at' => now(),
            'received_by_admin_id' => $user->id,
            'days_elapsed' => $request->requested_at
                ? $request->requested_at->diffInDays(now())
                : null,
        ]);

        if ($request->book_id) {
            $alerts->notifyIfAvailable($request->book_id);
        }

        return back()->with('success', 'Request confirmed successfully.');
    }

    public function cancel(BookRequest $request)
    {
        $user = Auth::user();

        if (! $user->isAdmin()) {
            abort(403);
        }

        if ($request->status === BookRequest::STATUS_COMPLETED) {
            return back()->with('error', 'Completed requests cannot be canceled.');
        }

        DB::transaction(function () use ($request) {
            $request->review()?->delete();

            $request->update([
                'status' => BookRequest::STATUS_CANCELED,
            ]);
        });

        return back()->with('success', 'Request canceled.');
    }
}
