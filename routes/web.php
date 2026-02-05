<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserController;
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

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    //Exports
    Route::get('/books/export', [BookController::class, 'export'])->name('books.export');

    //Imports
    Route::get('/books/import', [BookController::class, 'importIndex'])->name('books.import.index');
    Route::post('/books/import', [BookController::class, 'importStore'])->name('books.import.store');

    // CRUDs
    Route::resource('books', BookController::class)->except(['index', 'show']);
    Route::resource('authors', AuthorController::class)->except(['index', 'show']);
    Route::resource('publishers', PublisherController::class)->except(['index', 'show']);
    Route::resource('users', UserController::class);
    Route::resource('reviews', ReviewController::class);

    // Requests admin actions
    Route::patch('requests/{request}/confirm-received', [RequestController::class, 'confirmReceived'])
        ->name('requests.confirmReceived');

    Route::patch('requests/{request}/cancel', [RequestController::class, 'cancel'])
        ->name('requests.cancel');
});

/**
 * Public catalog
 */
Route::resource('books', BookController::class)->only(['index', 'show']);
Route::resource('authors', AuthorController::class)->only(['index', 'show']);
Route::resource('publishers', PublisherController::class)->only(['index', 'show']);

/**
 * Auth-only routes
 */
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::resource('requests', RequestController::class)
        ->only(['index', 'create', 'store', 'show']);

    Route::patch('requests/{request}/returned', [RequestController::class, 'markReturned'])
        ->name('requests.returned');

    Route::post('/requests/{request}/review', [ReviewController::class, 'store'])
        ->name('requests.review.store');
});
