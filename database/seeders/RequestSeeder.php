<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Request;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class RequestSeeder extends Seeder
{
    public function run(): void
    {
        $citizenRole = Role::firstOrCreate(['name' => 'citizen']);

        $admin = User::whereHas('role', fn ($q) => $q->where('name', 'admin'))->first();

        $books = Book::all();

        Request::factory()
            ->count(5)
            ->active()
            ->sequence(fn ($sequence) => [
                'user_id' => User::factory()->create([
                    'role_id' => $citizenRole->id,
                ])->id,
                'book_id' => $books[$sequence->index]->id,
            ])
            ->create();

        Request::factory()
            ->count(5)
            ->awaitingConfirmation()
            ->sequence(fn ($sequence) => [
                'user_id' => User::factory()->create([
                    'role_id' => $citizenRole->id,
                ])->id,
                'book_id' => $books[$sequence->index + 5]->id,
            ])
            ->create();

        Request::factory()
            ->count(10)
            ->completed()
            ->sequence(fn ($sequence) => [
                'user_id' => User::factory()->create([
                    'role_id' => $citizenRole->id,
                ])->id,
                'book_id' => $books[$sequence->index + 10]->id,
                'received_by_admin_id' => $admin->id,
            ])
            ->create();
    }
}
