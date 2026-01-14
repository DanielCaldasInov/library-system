<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use Illuminate\Http\Request;
use Inertia\Inertia;

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
            ->when(in_array($sort, ['name', 'price']), function ($query) use ($sort, $direction) {
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
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Book $book)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Book $book)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        //
    }
}
