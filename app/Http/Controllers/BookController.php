<?php

namespace App\Http\Controllers;

use App\Exports\BooksExport;
use App\Models\Author;
use App\Models\Book;
use App\Models\Publisher;
use App\Models\Review;
use App\Services\GoogleBooks\GoogleBooksClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Request as BookRequest;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');

        $availability = $request->get('availability', '');

        $books = Book::query()
            ->with([
                'authors:id,name',
                'publisher:id,name',
            ])
            ->withCount([
                'requests as active_requests_count' => function ($q) {
                    $q->whereIn('status', [
                        BookRequest::STATUS_ACTIVE,
                        BookRequest::STATUS_AWAITING_CONFIRMATION,
                    ]);
                }
            ])
            ->when($availability === 'available', function ($query) {
                $query->having('active_requests_count', '=', 0);
            })
            ->when($availability === 'unavailable', function ($query) {
                $query->having('active_requests_count', '>', 0);
            })
            ->when($request->filled('search'), function ($query) use ($request) {
                match ($request->filter) {
                    'author' => $query->whereHas('authors', fn ($q) =>
                    $q->where('name', 'like', "%{$request->search}%")
                    ),
                    'publisher' => $query->whereHas('publisher', fn ($q) =>
                    $q->where('name', 'like', "%{$request->search}%")
                    ),
                    default => $query->where('name', 'like', "%{$request->search}%"),
                };
            })
            ->when(in_array($sort, ['name', 'price'], true), function ($query) use ($sort, $direction) {
                $query->orderBy($sort, $direction);
            })
            ->when($sort === 'publisher', function ($query) use ($direction) {
                $query->join('publishers', 'publishers.id', '=', 'books.publisher_id')
                    ->orderBy('publishers.name', $direction)
                    ->select('books.*');
            })
            ->when($sort === 'author', function ($query) use ($direction) {
                $query->orderBy(
                    Author::select('name')
                        ->join('author_book', 'authors.id', '=', 'author_book.author_id')
                        ->whereColumn('author_book.book_id', 'books.id')
                        ->orderBy('name')
                        ->limit(1),
                    $direction
                );
            })
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Books/Index', [
            'books' => $books,
            'filters' => [
                'search' => $request->get('search', ''),
                'filter' => $request->get('filter', 'name'),
                'availability' => $availability,
            ],
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Books/Create', [
            'authors' => Author::query()->orderBy('name')->get(['id', 'name']),
            'publishers' => Publisher::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'isbn' => [
                'required',
                'string',
                'min:10',
                'max:13',
                'regex:/^\d{10}(\d{3})?$/',
                'unique:books,ISBN',
            ],
            'price' => ['required', 'numeric', 'min:1'],
            'bibliography' => ['required', 'string'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'publisher_id' => ['required', 'integer', 'exists:publishers,id'],
            'authors' => ['required', 'array', 'min:1'],
            'authors.*' => ['integer', 'exists:authors,id'],
        ]);

        $isbn = preg_replace('/\D+/', '', $validated['isbn']);

        $coverPath = null;
        if ($request->hasFile('cover')) {
            $coverPath = '/storage/' . $request->file('cover')->store('books/covers', 'public');
        }

        $book = Book::create([
            'name' => $validated['name'],
            'ISBN' => $isbn,
            'price' => $validated['price'],
            'bibliography' => $validated['bibliography'],
            'cover' => $coverPath,
            'publisher_id' => $validated['publisher_id'],
        ]);

        $book->authors()->sync($validated['authors']);

        return redirect()
            ->route('books.index')
            ->with('success', 'Book created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book)
    {
        $book->load([
            'publisher',
            'authors',
        ]);

        $hasActiveRequest = BookRequest::query()
            ->where('book_id', $book->id)
            ->whereIn('status', [
                BookRequest::STATUS_ACTIVE,
                BookRequest::STATUS_AWAITING_CONFIRMATION,
            ])
            ->exists();

        $bookRequestsQuery = BookRequest::query()
            ->where('book_id', $book->id)
            ->orderByDesc('requested_at');

        $bookRequestsCount = (clone $bookRequestsQuery)->count();

        $bookRequests = $bookRequestsQuery
            ->paginate(10, ['*'], 'requests_page')
            ->withQueryString()
            ->through(fn (BookRequest $r) => [
                'id' => $r->id,
                'number' => $r->number,
                'status' => $r->status,
                'requested_at' => $r->requested_at,
                'returned_at' => $r->returned_at,
            ]);

        $reviews = Review::query()
            ->where('book_id', $book->id)
            ->where('status', Review::STATUS_ACTIVE)
            ->with('user:id,name,profile_photo_path')
            ->orderByDesc('created_at')
            ->paginate(10, ['*'], 'reviews_page')
            ->withQueryString();

        return Inertia::render('Books/Show', [
            'book' => $book,
            'isAvailable' => ! $hasActiveRequest,
            'bookRequestsCount' => $bookRequestsCount,
            'bookRequests' => $bookRequests,
            'reviews' => $reviews,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Book $book)
    {
        $book->load(['authors:id', 'publisher:id']);
        $book->cover_url = $book->cover ? Storage::disk('public')->url($book->cover) : null;

        return Inertia::render('Books/Edit', [
            'book' => $book,
            'authors' => Author::query()->orderBy('name')->get(['id', 'name']),
            'publishers' => Publisher::query()->orderBy('name')->get(['id', 'name']),
            'selectedAuthors' => $book->authors->pluck('id'),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Book $book)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'isbn' => ['required', 'digits:13', Rule::unique('books', 'isbn')->ignore($book->id)],
            'price' => ['nullable', 'numeric', 'min:0'],
            'bibliography' => ['nullable', 'string'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'publisher_id' => ['required', 'integer', 'exists:publishers,id'],
            'authors' => ['required', 'array', 'min:1'],
            'authors.*' => ['integer', 'exists:authors,id'],
        ]);

        $coverPath = $book->cover;

        if ($request->hasFile('cover')) {
            if ($book->cover && Storage::disk('public')->exists($book->cover)) {
                Storage::disk('public')->delete($book->cover);
            }

            $coverPath = '/storage/'.$request->file('cover')->store('books/covers', 'public');
        }

        $book->update([
            'name' => $validated['name'],
            'isbn' => $validated['isbn'],
            'price' => $validated['price'] ?? null,
            'bibliography' => $validated['bibliography'] ?? null,
            'cover' => $coverPath,
            'publisher_id' => $validated['publisher_id'],
        ]);

        $book->authors()->sync($validated['authors']);

        return redirect()
            ->route('books.index')
            ->with('success', 'Book updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        $hasActiveRequests = $book->requests()
            ->whereIn('status', [
                BookRequest::STATUS_ACTIVE,
                BookRequest::STATUS_AWAITING_CONFIRMATION,
            ])
            ->exists();

        if ($hasActiveRequests) {
            return back()->with(
                'error',
                'This book cannot be deleted because it has active or pending requests.'
            );
        }

        if ($book->cover && Storage::disk('public')->exists($book->cover)) {
            Storage::disk('public')->delete($book->cover);
        }

        $book->authors()->detach();
        $book->delete();

        return redirect()
            ->route('books.index')
            ->with('success', 'Book deleted successfully.');
    }


    public function export(Request $request)
    {
        return Excel::download(
            new BooksExport(
                $request->only(['filter', 'search']),
                $request->get('sort'),
                $request->get('direction', 'asc')
            ),
            'books.xlsx'
        );
    }

    public function importIndex(Request $request)
    {
        $client = GoogleBooksClient::make();

        $apiOk = $client->ping();

        $filters = [
            'title' => (string) $request->get('title', ''),
            'author' => (string) $request->get('author', ''),
            'publisher' => (string) $request->get('publisher', ''),
            'isbn' => (string) $request->get('isbn', ''),
            'per_page' => (int) $request->get('per_page', 20),
        ];

        $perPage = max(1, min((int) $filters['per_page'], 40));
        $page = max(1, (int) $request->get('page', 1));

        $capTotal = (int) $request->get('cap', 200);
        $capTotal = max(1, $capTotal);

        $results = [];
        $totalItems = 0;

        $meta = [
            'current_page' => $page,
            'last_page' => 1,
            'per_page' => $perPage,
            'total' => 0,
            'from' => 0,
            'to' => 0,
        ];

        $hasAnyFilter = trim($filters['title'] . $filters['author'] . $filters['publisher'] . $filters['isbn']) !== '';

        if ($apiOk && $hasAnyFilter) {
            $parts = [];

            if ($filters['isbn'] !== '') {
                $isbnDigits = preg_replace('/\D+/', '', $filters['isbn']) ?? '';
                if ($isbnDigits !== '') {
                    $parts[] = 'isbn:' . $isbnDigits;
                }
            }

            if ($filters['title'] !== '') {
                $parts[] = 'intitle:"' . $filters['title'] . '"';
            }

            if ($filters['author'] !== '') {
                $parts[] = 'inauthor:"' . $filters['author'] . '"';
            }

            if ($filters['publisher'] !== '') {
                $parts[] = 'inpublisher:"' . $filters['publisher'] . '"';
            }

            $q = trim(implode(' ', $parts));

            if ($q !== '') {
                $startIndex = ($page - 1) * $perPage;

                $json = $client->search(
                    q: $q,
                    maxResults: $perPage,
                    startIndex: $startIndex,
                    country: 'PT',
                    capTotal: $capTotal
                );

                $items = $json['items'] ?? [];
                $totalItems = (int) ($json['totalItems'] ?? 0);

                $totalCapped = (int) ($json['totalItemsCapped'] ?? min($totalItems, $capTotal));
                $lastPageCapped = (int) ($json['lastPageCapped'] ?? max(1, (int) ceil($totalCapped / $perPage)));
                $pageCapped = (int) ($json['pageCapped'] ?? $page);

                $results = collect($items)
                    ->map(fn ($item) => $client->normalizeVolume($item))
                    ->values()
                    ->all();

                $from = $totalCapped > 0 ? min((($pageCapped - 1) * $perPage) + 1, $totalCapped) : 0;
                $to = $totalCapped > 0 ? min((($pageCapped - 1) * $perPage) + count($results), $totalCapped) : 0;

                $meta = [
                    'current_page' => $pageCapped,
                    'last_page' => $lastPageCapped,
                    'per_page' => $perPage,
                    'total' => $totalCapped,
                    'from' => $from,
                    'to' => $to,
                ];
            }
        }

        return Inertia::render('Books/Import', [
            'apiOk' => $apiOk,
            'filters' => [
                'title' => $filters['title'],
                'author' => $filters['author'],
                'publisher' => $filters['publisher'],
                'isbn' => $filters['isbn'],
                'per_page' => $perPage,
            ],
            'results' => $results,
            'totalItems' => $totalItems,
            'meta' => $meta,
        ]);
    }

    public function importStore(Request $request)
    {
        $data = $request->validate([
            'volume_ids' => ['required', 'array', 'min:1'],
            'volume_ids.*' => ['string'],
        ]);

        $client = GoogleBooksClient::make();

        if (! $client->ping()) {
            return back()->with('error', 'Google Books API is unavailable right now.');
        }

        $imported = 0;
        $skipped = 0;

        $volumeIds = array_values(array_unique(array_filter($data['volume_ids'])));

        foreach ($volumeIds as $volumeId) {
            try {
                $raw = $client->getVolume($volumeId, 'PT');
                $n = $client->normalizeVolume($raw);

                $isbn = $n['ISBN'] ?? null;
                if (!is_string($isbn) || $isbn === '') {
                    $skipped++;
                    continue;
                }

                $already = Book::query()->where('ISBN', $isbn)->exists();
                if ($already) {
                    $skipped++;
                    continue;
                }

                DB::transaction(function () use ($n, &$imported) {

                    $publisherName = $n['publisher_name'] ?? 'Unknown';

                    $publisher = Publisher::firstOrCreate(
                        ['name' => $publisherName],
                        ['logo' => 'http://picsum.photos/seed/' . rand(0, 999) . '/100']
                    );

                    $cover = $n['cover'] ?: ('http://picsum.photos/seed/' . rand(0, 999) . '/100');

                    $book = Book::create([
                        'ISBN' => $n['ISBN'],
                        'name' => $n['name'] ?? 'Untitled',
                        'publisher_id' => $publisher->id,
                        'bibliography' => $n['bibliography'] ?? '—',
                        'cover' => $cover,
                        'price' => (float) ($n['price'] ?? random_int(15, 200)),
                    ]);

                    $authorIds = [];
                    foreach (($n['authors'] ?? []) as $authorName) {
                        if (!is_string($authorName) || trim($authorName) === '') {
                            continue;
                        }

                        $author = Author::firstOrCreate(
                            ['name' => trim($authorName)],
                            ['photo' => 'http://picsum.photos/seed/' . rand(0, 999) . '/100']
                        );

                        $authorIds[] = $author->id;
                    }

                    if (!empty($authorIds)) {
                        $book->authors()->sync($authorIds);
                    }

                    $imported++;
                });

            } catch (\Throwable $e) {
                $skipped++;
                continue;
            }
        }

        return redirect()
            ->route('books.index')
            ->with('success', "Import finished: {$imported} imported, {$skipped} skipped.");
    }
}
