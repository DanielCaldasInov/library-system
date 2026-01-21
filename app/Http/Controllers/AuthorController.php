<?php

namespace App\Http\Controllers;

use App\Models\Author;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
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
        return Inertia::render('Authors/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:authors,name'],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $author = new Author();
        $author->name = $validated['name'];

        if ($request->hasFile('photo')) {
            $author->photo = '/storage/'.$request->file('photo')->store('authors', 'public');
        }

        $author->save();

        return redirect()
            ->route('authors.show', $author->id)
            ->with('success', 'Author created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Author $author)
    {
        $author->load([
            'books.publisher',
        ]);

        return Inertia::render('Authors/Show', [
            'author' => $author,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Author $author)
    {
        return Inertia::render('Authors/Edit', [
            'author' => $author,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Author $author)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:authors,name,' . $author->id],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_photo' => ['nullable', 'boolean'],
        ]);

        if (($validated['remove_photo'] ?? false) && $author->photo) {
            Storage::disk('public')->delete($author->photo);
            $author->photo = null;
        }

        if ($request->hasFile('photo')) {
            if ($author->photo) {
                Storage::disk('public')->delete($author->photo);
            }

            $author->photo = '/storage/'.$request->file('photo')->store('authors', 'public');
        }

        $author->name = $validated['name'];
        $author->save();

        return redirect()
            ->route('authors.show', $author->id)
            ->with('success', 'Author updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Author $author)
    {
        if ($author->books()->exists()) {
            return back()->with(
                'error',
                'This author cannot be deleted because it has associated books.'
            );
        }

        if ($author->photo) {
            Storage::disk('public')->delete($author->photo);
        }

        $author->delete();

        return redirect()
            ->route('authors.index')
            ->with('success', 'Author deleted successfully.');
    }
}
