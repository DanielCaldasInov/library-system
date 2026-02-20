<?php

use App\Models\Book;
use App\Models\User;
use App\Models\Role;
use App\Models\Request as BookRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Role::firstOrCreate(['name' => 'citizen']);
});

it('allows a citizen with a profile photo to create a book request', function () {
    $user = User::factory()->create([
        'profile_photo_path' => 'photos/dummy-avatar.jpg',
    ]);

    $book = Book::factory()->create([
        'stock' => 5,
    ]);

    $response = $this->actingAs($user)
        ->post(route('requests.store'), [
            'book_id' => $book->id,
        ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();

    $this->assertDatabaseHas('requests', [
        'book_id' => $book->id,
        'user_id' => $user->id,
        'book_name' => $book->name,
        'status' => BookRequest::STATUS_ACTIVE,
    ]);
});

it('prevents a citizen without a profile photo from creating a book request', function () {
    $user = User::factory()->create([
        'profile_photo_path' => null,
    ]);

    $book = Book::factory()->create([
        'stock' => 5,
    ]);

    $response = $this->actingAs($user)
        ->post(route('requests.store'), [
            'book_id' => $book->id,
        ]);

    $response->assertSessionHasErrors([
        'book_id' => 'You must upload a profile photo before creating a request.'
    ]);

    $this->assertDatabaseMissing('requests', [
        'book_id' => $book->id,
        'user_id' => $user->id,
    ]);
});

it('prevents a request if the book does not exist', function () {
    $user = User::factory()->create([
        'profile_photo_path' => 'photos/dummy-avatar.jpg',
    ]);

    $response = $this->actingAs($user)
        ->post(route('requests.store'), [
            'book_id' => 99999, // Livro inexistente
        ]);

    $response->assertSessionHasErrors(['book_id']);

    $this->assertDatabaseEmpty('requests');
});

it('prevents a user from having more than 3 active requests', function () {
    $user = User::factory()->create([
        'profile_photo_path' => 'photos/dummy-avatar.jpg',
    ]);

    $books = Book::factory()->count(4)->create([
        'stock' => 5,
    ]);

    for ($i = 0; $i < 3; $i++) {
        BookRequest::factory()->active()->create([
            'user_id' => $user->id,
            'book_id' => $books[$i]->id,
        ]);
    }

    $response = $this->actingAs($user)
        ->post(route('requests.store'), [
            'book_id' => $books[3]->id,
        ]);

    $response->assertSessionHasErrors([
        'book_id' => 'You already have 3 active requests. Return a book before requesting another.'
    ]);

    $this->assertDatabaseCount('requests', 3);
});

it('prevents requesting a book that is already borrowed by someone else and has no stock left', function () {
    $user1 = User::factory()->create(['profile_photo_path' => 'photos/test1.jpg']);
    $user2 = User::factory()->create(['profile_photo_path' => 'photos/test2.jpg']);

    $book = Book::factory()->create([
        'stock' => 1,
    ]);

    BookRequest::factory()->active()->create([
        'user_id' => $user1->id,
        'book_id' => $book->id,
    ]);

    $response = $this->actingAs($user2)
        ->post(route('requests.store'), [
            'book_id' => $book->id,
        ]);

    $response->assertInvalid([
        'book_id' => 'This book is out of stock and not available for request right now.'
    ]);
});
