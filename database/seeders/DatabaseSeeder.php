<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'citizen']);

        User::factory()->create([
            'name' => 'Daniel Silva',
            'email' => 'daniel@email.com',
            'password' => Hash::make('12345678'),
            'role_id' => Role::where('name', 'admin')->first()->id
        ]);

        $this->call(BookSeeder::class);
    }
}
