<?php

namespace App\Http\Controllers;

use App\Exports\BooksExport;
use App\Models\Author;
use App\Models\Book;
use App\Models\Publisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class BookController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');

        $books = Book::query()
            ->with(['authors', 'publisher'])
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
            'filters' => $request->only(['search', 'filter']),
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
            'isbn' => ['required', 'digits:13', 'unique:books,isbn'],
            'price' => ['nullable', 'numeric', 'min:1'],
            'bibliography' => ['nullable', 'string'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'publisher_id' => ['required', 'integer', 'exists:publishers,id'],
            'authors' => ['required', 'array', 'min:1'],
            'authors.*' => ['integer', 'exists:authors,id'],
        ]);

        $coverPath = null;
        if ($request->hasFile('cover')) {
            $coverPath = '/storage/'.$request->file('cover')->store('books/covers', 'public');
        }

        $book = Book::create([
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
            ->with('success', 'Book created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book)
    {
        $book->load(['authors:id,name,photo', 'publisher:id,name,logo']);
        $book->cover_url = $book->cover ? Storage::disk('public')->url($book->cover) : null;

        return Inertia::render('Books/Show', [
            'book' => $book,
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
}
