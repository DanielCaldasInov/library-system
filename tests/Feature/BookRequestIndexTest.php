<?php

use App\Models\User;
use App\Models\Role;
use App\Models\Request as BookRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('lists only the requests belonging to the logged-in citizen', function () {
    Role::firstOrCreate(['name' => 'citizen']);

    $userA = User::factory()->create();
    $userB = User::factory()->create();

    BookRequest::factory()->count(2)->active()->create([
        'user_id' => $userA->id,
    ]);

    BookRequest::factory()->count(3)->active()->create([
        'user_id' => $userB->id,
    ]);

    $response = $this->actingAs($userA)->get(route('requests.index'));

    $response->assertStatus(200);

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Requests/Index')
        ->has('requests.data', 2)
        ->where('requests.data.0.user_id', $userA->id)
        ->where('requests.data.1.user_id', $userA->id)
    );
});

it('lists all requests if the logged-in user is an admin', function () {
    Role::firstOrCreate(['name' => 'citizen']);
    $adminRole = Role::firstOrCreate(['name' => 'admin']);

    $admin = User::factory()->create(['role_id' => $adminRole->id]);
    $citizen = User::factory()->create();

    BookRequest::factory()->count(4)->active()->create([
        'user_id' => $citizen->id,
    ]);

    $response = $this->actingAs($admin)->get(route('requests.index'));

    $response->assertStatus(200);

    $response->assertInertia(fn (Assert $page) => $page
        ->component('Requests/Index')
        ->has('requests.data', 4)
    );
});
