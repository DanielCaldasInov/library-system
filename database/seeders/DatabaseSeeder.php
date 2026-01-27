<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

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
        $this->call(RequestSeeder::class);
    }
}
