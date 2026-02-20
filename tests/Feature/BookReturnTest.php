<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Request as BookRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows a citizen to return their own active book request', function () {
    Role::firstOrCreate(['name' => 'citizen']);

    $user = User::factory()->create();

    $bookRequest = BookRequest::factory()->active()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->patch(route('requests.returned', $bookRequest->id));

    $response->assertRedirect();
    $response->assertSessionHas('success', 'Marked as returned. Awaiting admin confirmation.');

    $this->assertDatabaseHas('requests', [
        'id' => $bookRequest->id,
        'status' => BookRequest::STATUS_AWAITING_CONFIRMATION,
    ]);

    expect($bookRequest->fresh()->returned_at)->not->toBeNull();
});

it('prevents a citizen from returning someone else\'s book request', function () {
    Role::firstOrCreate(['name' => 'citizen']);

    $owner = User::factory()->create();
    $intruder = User::factory()->create();

    $bookRequest = BookRequest::factory()->active()->create([
        'user_id' => $owner->id,
    ]);

    $response = $this->actingAs($intruder)
        ->patch(route('requests.returned', $bookRequest->id));

    $response->assertForbidden();

    $this->assertDatabaseHas('requests', [
        'id' => $bookRequest->id,
        'status' => BookRequest::STATUS_ACTIVE,
    ]);
});

it('prevents returning a request that is not active anymore', function () {
    Role::firstOrCreate(['name' => 'citizen']);

    $user = User::factory()->create();

    $bookRequest = BookRequest::factory()->completed()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)
        ->patch(route('requests.returned', $bookRequest->id));

    $response->assertSessionHas('error', 'This request is not active.');

    $this->assertDatabaseHas('requests', [
        'id' => $bookRequest->id,
        'status' => BookRequest::STATUS_COMPLETED,
    ]);
});
