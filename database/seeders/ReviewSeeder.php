<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\Request as BookRequest;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $requests = BookRequest::query()
            ->whereNotNull('book_id')
            ->whereNotNull('user_id')
            ->whereIn('status', [
                BookRequest::STATUS_COMPLETED,
                BookRequest::STATUS_AWAITING_CONFIRMATION,
            ])
            ->whereDoesntHave('review')
            ->inRandomOrder()
            ->take(60)
            ->get();

        foreach ($requests as $req) {
            Review::factory()->create([
                'request_id' => $req->id,
                'book_id' => $req->book_id,
                'user_id' => $req->user_id,
            ]);
        }
    }
}
