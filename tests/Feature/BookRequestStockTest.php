<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Book;
use App\Models\Request as BookRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'citizen']);
});

it('prevents a citizen from requesting a book with 0 stock', function () {
    $user = User::factory()->create([
        'profile_photo_path' => 'photos/test.jpg',
    ]);

    $book = Book::factory()->create([
        'stock' => 0,
    ]);

    $response = $this->actingAs($user)
        ->post(route('requests.store'), [
            'book_id' => $book->id,
        ]);

    $response->assertInvalid([
        'book_id' => 'This book is out of stock and not available for request right now.',
    ]);

    $this->assertDatabaseMissing('requests', [
        'user_id' => $user->id,
        'book_id' => $book->id,
    ]);
});

it('prevents requesting a book if the available stock is exhausted by active requests', function () {
    $user1 = User::factory()->create(['profile_photo_path' => 'photos/test1.jpg']);
    $user2 = User::factory()->create(['profile_photo_path' => 'photos/test2.jpg']);

    $book = Book::factory()->create([
        'stock' => 1,
    ]);

    BookRequest::factory()->active()->create([
        'book_id' => $book->id,
        'user_id' => $user1->id,
    ]);

    $response = $this->actingAs($user2)
        ->post(route('requests.store'), [
            'book_id' => $book->id,
        ]);

    $response->assertInvalid([
        'book_id' => 'This book is out of stock and not available for request right now.',
    ]);
});

it('allows requesting a book if available stock is greater than active requests', function () {
    $user = User::factory()->create(['profile_photo_path' => 'photos/test.jpg']);

    $book = Book::factory()->create([
        'stock' => 2,
    ]);

    BookRequest::factory()->active()->create([
        'book_id' => $book->id,
    ]);

    $response = $this->actingAs($user)
        ->post(route('requests.store'), [
            'book_id' => $book->id,
        ]);

    $response->assertRedirect(route('requests.index'));
    $response->assertSessionHas('success', 'Request created successfully.');

    $this->assertDatabaseHas('requests', [
        'user_id' => $user->id,
        'book_id' => $book->id,
        'status' => BookRequest::STATUS_ACTIVE,
    ]);
});
