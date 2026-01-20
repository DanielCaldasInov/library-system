<?php

namespace App\Http\Controllers;

use App\Models\Publisher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PublisherController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sort = $request->get('sort', 'name');
        $direction = $request->get('direction', 'asc');

        $publishers = Publisher::query()
            ->with(['books']) // eager loading
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('name', 'like', "%$request->search%")
                    ->orWhereHas('books', fn ($q) =>
                    $q->where('name', 'like', "%$request->search%")
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

        return Inertia::render('Publishers/Index', [
            'publishers' => $publishers,
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
        return Inertia::render('Publishers/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:publishers,name'],
            'logo' => ['required','image','mimes:jpeg,png,jpg,svg','max:2048']
        ]);

        $logoPath = null;

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('publishers', 'public');
        }
        Publisher::create([
            'name' => $validated['name'],
            'logo' => '/storage/'.$logoPath, // ex: publishers/abc.png
        ]);

        return redirect()->route('publishers.index')->with('success', 'Publisher created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Publisher $publisher)
    {
        $publisher->load([
            'books.authors',
        ]);

        return Inertia::render('Publishers/Show', [
            'publisher' => $publisher,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Publisher $publisher)
    {
        return Inertia::render('Publishers/Edit', [
        'publisher' => $publisher,
    ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Publisher $publisher)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:publishers,name,' . $publisher->id],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ]);

        if (($validated['remove_logo'] ?? false) && $publisher->logo) {
            Storage::disk('public')->delete($publisher->logo);
            $publisher->logo = null;
        }

        if ($request->hasFile('logo')) {
            if ($publisher->logo) {
                Storage::disk('public')->delete($publisher->logo);
            }

            $publisher->logo = '/storage/'.$request->file('logo')->store('publishers', 'public');
        }

        $publisher->name = $validated['name'];
        $publisher->save();

        return redirect()
            ->route('publishers.show', $publisher->id)
            ->with('success', 'Publisher updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Publisher $publisher)
    {
        if ($publisher->books()->exists()) {
            return back()->with(
                'error',
                'This publisher cannot be deleted because it has associated books.'
            );
        }

        if ($publisher->logo) {
            Storage::disk('public')->delete($publisher->logo);
        }

        $publisher->delete();

        return redirect()
            ->route('publishers.index')
            ->with('success', 'Publisher deleted successfully.');
    }
}
