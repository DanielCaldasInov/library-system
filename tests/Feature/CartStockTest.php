<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Book;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Request as BookRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'citizen']);
});

it('prevents adding a book to the cart if stock is completely exhausted', function () {
    $user = User::factory()->create();

    $book = Book::factory()->create([
        'stock' => 1,
        'price' => 15.00,
    ]);

    BookRequest::factory()->active()->create([
        'book_id' => $book->id,
    ]);

    $response = $this->actingAs($user)
        ->post(route('cart.items.store'), [
            'book_id' => $book->id,
            'qty' => 1,
        ]);

    $response->assertSessionHasErrors([
        'book_id' => 'This book is out of stock and cannot be purchased right now.'
    ]);

    $this->assertDatabaseEmpty('cart_items');
});

it('limits the added quantity to the available stock and shows a warning', function () {
    $user = User::factory()->create();

    $book = Book::factory()->create([
        'stock' => 2,
        'price' => 20.00,
    ]);

    $response = $this->actingAs($user)
        ->post(route('cart.items.store'), [
            'book_id' => $book->id,
            'qty' => 5,
        ]);

    $response->assertSessionHas('warning', 'You can only add up to 2 copies of this book.');

    $this->assertDatabaseHas('cart_items', [
        'book_id' => $book->id,
        'qty' => 2,
    ]);
});

it('prevents updating cart item quantity beyond available stock', function () {
    $user = User::factory()->create();

    $book = Book::factory()->create([
        'stock' => 3,
        'price' => 10.00,
    ]);

    $cart = Cart::create([
        'user_id' => $user->id,
        'status' => 'active',
        'last_activity_at' => now(),
    ]);

    $cartItem = CartItem::create([
        'cart_id' => $cart->id,
        'book_id' => $book->id,
        'qty' => 1,
    ]);

    BookRequest::factory()->count(2)->active()->create([
        'book_id' => $book->id,
    ]);

    $response = $this->actingAs($user)
        ->patch(route('cart.items.update', $cartItem->id), [
            'qty' => 2,
        ]);

    $response->assertSessionHas('error', 'You cannot request more than 1 copies. Stock is limited.');

    expect($cartItem->fresh()->qty)->toBe(1);
});
