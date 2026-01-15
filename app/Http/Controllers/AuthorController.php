<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuthorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');

        $authors = Author::query()
            ->with(['books']) // eager loading
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', "%{$request->search}%")
                    ->orWhereHas('books', fn ($q) =>
                    $q->where('name', 'like', "%{$request->search}%")
                    );
            })
            ->when($sort === 'name', fn ($q) =>
            $q->orderBy('name', $direction)
            )
            ->when($sort === 'books', function ($q) use ($direction) {
                $q->withCount('books')
                    ->orderBy('books_count', $direction);
            })
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Authors/Index', [
            'authors' => $authors,
            'filters' => $request->only(['search']),
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
    public function show(Author $author)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Author $author)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Author $author)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Author $author)
    {
        //
    }
}
