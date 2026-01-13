<?php

namespace Database\Factories;

use App\Models\Author;
use App\Models\Publisher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ISBN' => fake()->isbn13(),
            'name' => fake()->name(),
            'publisher_id' => Publisher::factory(),
            'bibliography' => fake()->text(),
            'cover' => 'http://picsum.photos/seed/'.rand(0,999) .'/100',
            'price' => fake()->numberBetween(10,350)
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function ($book)
        {
            $author = Author::factory()->create();
            $book->authors()->attach($author->id);
        });
    }
}
