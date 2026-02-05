<?php

namespace Database\Factories;

use App\Models\Review;
use App\Models\Request as BookRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement([
            Review::STATUS_PENDING,
            Review::STATUS_ACTIVE,
            Review::STATUS_REJECTED,
        ]);

        $rating = $this->faker->numberBetween(1, 5);

        $comment = $this->faker->boolean(85)
            ? $this->faker->paragraphs($this->faker->numberBetween(1, 2), true)
            : null;

        $rejectionReason = $status === Review::STATUS_REJECTED
            ? $this->faker->sentence()
            : null;

        return [
            'request_id' => BookRequest::factory(),
            'book_id' => null,
            'user_id' => null,
            'rating' => $rating,
            'comment' => $comment,
            'status' => $status,
            'rejection_reason' => $rejectionReason,
        ];
    }

    public function pending(): static
    {
        return $this->state([
            'status' => Review::STATUS_PENDING,
            'rejection_reason' => null,
        ]);
    }

    public function active(): static
    {
        return $this->state([
            'status' => Review::STATUS_ACTIVE,
            'rejection_reason' => null,
        ]);
    }

    public function rejected(?string $reason = null): static
    {
        return $this->state([
            'status' => Review::STATUS_REJECTED,
            'rejection_reason' => $reason ?? $this->faker->sentence(),
        ]);
    }
}
