<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\PublisherController;
use App\Models\Author;
use App\Models\Book;
use App\Models\Publisher;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Welcome'));

/**
 * Admin-only routes
 */
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
    'admin',
])->group(function () {

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard', [
            'stats' => [
                'booksCount' => Book::count(),
                'recentBooks' => Book::query()->select(['id','name','created_at'])->latest()->take(5)->get(),
                'authorsCount' => Author::count(),
                'recentAuthors' => Author::query()->select(['id','name','created_at'])->latest()->take(5)->get(),
                'publishersCount' => Publisher::count(),
                'recentPublishers' => Publisher::query()->select(['id','name','created_at'])->latest()->take(5)->get(),
            ],
        ]);
    })->name('dashboard');

    Route::get('/books/export', [BookController::class, 'export'])->name('books.export');

    // CRUDs
    Route::resource('books', BookController::class)->except(['index', 'show']);
    Route::resource('authors', AuthorController::class)->except(['index', 'show']);
    Route::resource('publishers', PublisherController::class)->except(['index', 'show']);
});

/**
 * Public catalog
 */
Route::resource('books', BookController::class)->only(['index', 'show']);
Route::resource('authors', AuthorController::class)->only(['index', 'show']);
Route::resource('publishers', PublisherController::class)->only(['index', 'show']);


