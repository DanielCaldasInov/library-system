<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\PublisherController;
use App\Models\Author;
use App\Models\Book;
use App\Models\Publisher;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard', [
            'stats' => [
                'booksCount' => Book::count(),
                'recentBooks' => Book::query()
                    ->select(['id', 'name', 'created_at'])
                    ->latest()
                    ->take(5)
                    ->get(),

                'authorsCount' => Author::count(),
                'recentAuthors' => Author::query()
                    ->select(['id', 'name', 'created_at'])
                    ->latest()
                    ->take(5)
                    ->get(),

                'publishersCount' => Publisher::count(),
                'recentPublishers' => Publisher::query()
                    ->select(['id', 'name', 'created_at'])
                    ->latest()
                    ->take(5)
                    ->get(),
            ],
        ]);
    })->name('dashboard');

    Route::resource('books', BookController::class)
        ->only(['index']);

    Route::resource('authors', AuthorController::class)
        ->only(['index']);

    Route::resource('publishers', PublisherController::class)
        ->only(['index']);

    Route::get('/books/export', [BookController::class, 'export'])
        ->name('books.export');
});
