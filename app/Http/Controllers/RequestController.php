<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Request as BookRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $status = $request->get('status', 'active');
        $perPage = (int) ($request->get('per_page', 10));

        // Filter selector coming from the select in SearchForm
        // Default is empty -> global search (number/book + citizen only for admins)
        $filter = $request->get('filter', '');

        // Build search options in the backend (Citizen only for admins)
        $searchOptions = [
            ['value' => 'number', 'label' => 'Number'],
            ['value' => 'book', 'label' => 'Book'],
        ];

        if ($user->isAdmin()) {
            $searchOptions[] = ['value' => 'citizen', 'label' => 'Citizen'];
        } else {
            // Security: if a citizen tries ?filter=citizen in URL, ignore it
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
            ])
            ->orderByDesc('requested_at');

        // Citizen: only see own requests
        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        // Status filters
        if ($status === 'active') {
            $query->whereIn('status', [BookRequest::STATUS_ACTIVE, BookRequest::STATUS_AWAITING_CONFIRMATION]);
        } elseif ($status === 'completed') {
            $query->where('status', BookRequest::STATUS_COMPLETED);
        }
        // if status === 'all' -> no extra filter (admin can use it)

        // Search
        if ($request->filled('search')) {
            $search = $request->get('search');

            $query->where(function ($q) use ($search, $user, $filter) {

                if ($filter === '' || $filter === null) {
                    $q->where('number', 'like', "%{$search}%")
                        ->orWhere('book_name', 'like', "%{$search}%")
                        ->orWhereHas('book', fn ($b) => $b->where('name', 'like', "%{$search}%"));

                    if ($user->isAdmin()) {
                        $q->orWhere('citizen_name', 'like', "%{$search}%")
                            ->orWhere('citizen_email', 'like', "%{$search}%");
                    }

                    return;
                }

                if ($filter === 'number') {
                    $q->where('number', 'like', "%{$search}%");
                    return;
                }

                if ($filter === 'book') {
                    $q->where('book_name', 'like', "%{$search}%")
                        ->orWhereHas('book', fn ($b) => $b->where('name', 'like', "%{$search}%"));
                    return;
                }

                if ($filter === 'citizen' && $user->isAdmin()) {
                    $q->where('citizen_name', 'like', "%{$search}%")
                        ->orWhere('citizen_email', 'like', "%{$search}%");
                    return;
                }

                $q->where('number', 'like', "%{$search}%");
            });
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
            'stats' => $stats,
            'searchOptions' => $searchOptions,
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

        $data = $request->validate([
            'book_id' => ['required', 'integer', 'exists:books,id'],
        ]);

        // Check for number of active requests for user
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

        //TODO: Add verification for user photo

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

        // TODO: send emails to admin and citizen

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
        ]);

        return Inertia::render('Requests/Show', [
            'request' => $request,
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

    public function confirmReceived(BookRequest $request)
    {
        $user = Auth::user();

        if (! $user->isAdmin()) {
            abort(403);
        }

        if ($request->status !== BookRequest::STATUS_AWAITING_CONFIRMATION) {
            return back()->with('error', 'This request is not awaiting confirmation.');
        }

        $receivedAt = now();
        $daysElapsed = $receivedAt->diff($request->requested_at)->days;

        $request->update([
            'status' => BookRequest::STATUS_COMPLETED,
            'received_at' => $receivedAt,
            'days_elapsed' => $daysElapsed,
            'received_by_admin_id' => $user->id,
        ]);

        return back()->with('success', 'Return confirmed successfully.');
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

        $request->update([
            'status' => BookRequest::STATUS_CANCELED,
        ]);

        return back()->with('success', 'Request canceled.');
    }
}
