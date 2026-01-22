<?php

namespace Database\Factories;

use App\Models\Book;
use App\Models\Request;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Request>
 */
class RequestFactory extends Factory
{
    protected $model = Request::class;

    public function definition(): array
    {
        $requestedAt = fake()->dateTimeBetween('-30 days', '-1 day');

        $dueAt = (clone $requestedAt)->modify('+5 days');

        $upperReturnBound = min($dueAt->getTimestamp(), now()->timestamp);

        $returnedAt = null;
        if (fake()->boolean(60) && $upperReturnBound >= $requestedAt->getTimestamp()) {
            $returnedAt = fake()->dateTimeBetween($requestedAt->getTimestamp(), $upperReturnBound);
        }

        $receivedAt = null;
        if ($returnedAt) {
            $receivedAt = fake()->dateTimeBetween($returnedAt, 'now');
        }

        $status = match (true) {
            is_null($returnedAt) => Request::STATUS_ACTIVE,
            is_null($receivedAt) => Request::STATUS_AWAITING_CONFIRMATION,
            default => Request::STATUS_COMPLETED,
        };

        $book = Book::inRandomOrder()->first() ?? Book::factory()->create();
        $user = User::inRandomOrder()->first() ?? User::factory()->create();

        return [
            'number' => fake()->unique()->numberBetween(100000, 999999),

            'book_id' => $book->id,
            'user_id' => $user->id,

            'citizen_name' => $user->name,
            'citizen_email' => $user->email,
            'citizen_photo' => $user->profile_photo_path,

            'book_name' => $book->name,
            'book_cover' => $book->cover,

            'requested_at' => $requestedAt,
            'due_at' => $dueAt,
            'returned_at' => $returnedAt,
            'received_at' => $receivedAt,

            'status' => $status,

            'days_elapsed' => $receivedAt?->diff($requestedAt)->days,

            'received_by_admin_id' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(function () {
            $requestedAt = fake()->dateTimeBetween('-10 days', '-1 day');
            $dueAt = (clone $requestedAt)->modify('+5 days');

            return [
                'requested_at' => $requestedAt,
                'due_at' => $dueAt,
                'returned_at' => null,
                'received_at' => null,
                'status' => Request::STATUS_ACTIVE,
                'days_elapsed' => null,
                'received_by_admin_id' => null,
            ];
        });
    }

    public function awaitingConfirmation(): static
    {
        return $this->state(function () {
            $requestedAt = fake()->dateTimeBetween('-10 days', '-6 days');
            $dueAt = (clone $requestedAt)->modify('+5 days');

            $upper = min($dueAt->getTimestamp(), now()->timestamp);
            $returnedAt = fake()->dateTimeBetween($requestedAt->getTimestamp(), $upper);

            return [
                'requested_at' => $requestedAt,
                'due_at' => $dueAt,
                'returned_at' => $returnedAt,
                'received_at' => null,
                'status' => Request::STATUS_AWAITING_CONFIRMATION,
                'days_elapsed' => null,
                'received_by_admin_id' => null,
            ];
        });
    }

    public function completed(): static
    {
        return $this->state(function () {
            $requestedAt = fake()->dateTimeBetween('-30 days', '-10 days');
            $dueAt = (clone $requestedAt)->modify('+5 days');

            $returnedAt = fake()->dateTimeBetween($requestedAt, $dueAt);

            $receivedAt = fake()->dateTimeBetween($returnedAt, 'now');

            return [
                'requested_at' => $requestedAt,
                'due_at' => $dueAt,
                'returned_at' => $returnedAt,
                'received_at' => $receivedAt,
                'status' => Request::STATUS_COMPLETED,
                'days_elapsed' => $receivedAt->diff($requestedAt)->days,
            ];
        });
    }
}
