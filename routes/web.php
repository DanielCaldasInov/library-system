<?php

use App\Http\Controllers\AuthorController;
use App\Http\Controllers\BookAvailabilityAlertController;
use App\Http\Controllers\BookController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PublisherController;
use App\Http\Controllers\RequestController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SystemLogController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

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

    Route::get('/debug-2fa', function () {
        $user = auth()->user();
        if (!$user) return 'Faça login primeiro!';

        // 1. Vai buscar diretamente à base de dados (ignorando o Model)
        $rawDbValue = DB::table('users')->where('id', $user->id)->value('two_factor_secret');

        // 2. Vai buscar através do Model (onde está a acontecer o erro)
        $modelValue = $user->two_factor_secret;

        // 3. Tenta desencriptar manualmente
        try {
            $decrypted = decrypt($rawDbValue);
            $decryptStatus = 'Sucesso! (APP_KEY está correta)';
        } catch (\Exception $e) {
            $decryptStatus = 'Falhou: ' . $e->getMessage();
        }

        return response()->json([
            '1_valor_na_base_de_dados' => $rawDbValue ? 'Preenchido' : 'Vazio',
            '2_valor_no_model' => $modelValue ? 'Preenchido' : 'NULO! (Aqui está o bug)',
            '3_teste_desencriptacao' => $decryptStatus,
        ]);
    });

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Exports
    Route::get('/books/export', [BookController::class, 'export'])->name('books.export');

    // Imports
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

    // Logs
    Route::get('/system-logs', [SystemLogController::class, 'index'])->name('system-logs.index');
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

    // Requests
    Route::resource('requests', RequestController::class)->only(['index', 'create', 'store', 'show']);

    Route::patch('requests/{request}/returned', [RequestController::class, 'markReturned'])
        ->name('requests.returned');

    Route::post('/requests/{request}/review', [ReviewController::class, 'store'])
        ->name('requests.review.store');

    // Availability alerts
    Route::post('/books/{book}/availability-alert', [BookAvailabilityAlertController::class, 'store'])
        ->name('books.availability-alert.store');

    // Cart
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');

    Route::post('/cart/items', [CartController::class, 'store'])->name('cart.items.store');
    Route::patch('/cart/items/{cartItem}', [CartController::class, 'update'])->name('cart.items.update');
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'destroy'])->name('cart.items.destroy');

    // Checkout
    Route::get('/checkout/delivery', [CheckoutController::class, 'delivery'])->name('checkout.delivery');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/checkout/success/{order}', [CheckoutController::class, 'success'])->name('checkout.success');
    Route::get('/checkout/cancel/{order}', [CheckoutController::class, 'cancel'])->name('checkout.cancel');

    //Orders
    Route::get('/orders', [OrderController::class, 'index'])
        ->name('orders.index');

    Route::get('/orders/{order}', [OrderController::class, 'show'])
        ->name('orders.show');
});
