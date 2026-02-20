<?php

use App\Models\Book;
use App\Models\User;
use App\Models\Role;
use App\Models\Request as BookRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows a citizen with a profile photo to create a book request', function () {
    Role::firstOrCreate(['name' => 'citizen']);

    $user = User::factory()->create([
        'profile_photo_path' => 'photos/dummy-avatar.jpg',
    ]);

    $book = Book::factory()->create();

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
    Role::firstOrCreate(['name' => 'citizen']);

    $user = User::factory()->create([
        'profile_photo_path' => null,
    ]);

    $book = Book::factory()->create();

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
    Role::firstOrCreate(['name' => 'citizen']);

    $user = User::factory()->create([
        'profile_photo_path' => 'photos/dummy-avatar.jpg',
    ]);

    $response = $this->actingAs($user)
        ->post(route('requests.store'), [
            'book_id' => 99999,
        ]);

    $response->assertSessionHasErrors(['book_id']);

    $this->assertDatabaseEmpty('requests');
});

it('prevents a user from having more than 3 active requests', function () {
    Role::firstOrCreate(['name' => 'citizen']);

    $user = User::factory()->create([
        'profile_photo_path' => 'photos/dummy-avatar.jpg',
    ]);

    $books = Book::factory()->count(4)->create();

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

it('prevents requesting a book that is already borrowed by someone else', function () {
    Role::firstOrCreate(['name' => 'citizen']);

    $userA = User::factory()->create(['profile_photo_path' => 'photos/user-a.jpg']);
    $userB = User::factory()->create(['profile_photo_path' => 'photos/user-b.jpg']);

    $book = Book::factory()->create();

    BookRequest::factory()->active()->create([
        'user_id' => $userA->id,
        'book_id' => $book->id,
    ]);

    $response = $this->actingAs($userB)
        ->post(route('requests.store'), [
            'book_id' => $book->id,
        ]);

    $response->assertSessionHasErrors([
        'book_id' => 'This book is not available for request right now.'
    ]);

    $this->assertDatabaseMissing('requests', [
        'user_id' => $userB->id,
        'book_id' => $book->id,
    ]);
});
