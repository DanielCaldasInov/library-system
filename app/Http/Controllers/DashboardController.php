<?php

namespace App\Http\Controllers;

use App\Models\Author;
use App\Models\Book;
use App\Models\Publisher;
use App\Models\User;
use App\Models\Request as BookRequest;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        $requestsByStatus = BookRequest::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return Inertia::render('Dashboard', [
            'stats' => [
                'booksCount' => Book::count(),
                'recentBooks' => Book::query()
                    ->select(['id', 'name', 'created_at'])
                    ->latest()
                    ->take(5)
                    ->get(),

                'authorsCount' => Author::count(),
                'recentAuthors' => Author::query()
                    ->select(['id', 'name', 'created_at'])
                    ->latest()
                    ->take(5)
                    ->get(),

                'publishersCount' => Publisher::count(),
                'recentPublishers' => Publisher::query()
                    ->select(['id', 'name', 'created_at'])
                    ->latest()
                    ->take(5)
                    ->get(),

                'usersCount' => User::count(),
                'recentUsers' => User::query()
                    ->select(['id', 'name', 'email', 'created_at'])
                    ->latest()
                    ->take(5)
                    ->get(),

                'requestsCount' => BookRequest::count(),
                'requestsByStatus' => [
                    'active' => (int) ($requestsByStatus[BookRequest::STATUS_ACTIVE] ?? 0),
                    'awaiting_confirmation' => (int) ($requestsByStatus[BookRequest::STATUS_AWAITING_CONFIRMATION] ?? 0),
                    'completed' => (int) ($requestsByStatus[BookRequest::STATUS_COMPLETED] ?? 0),
                    'canceled' => (int) ($requestsByStatus[BookRequest::STATUS_CANCELED] ?? 0),
                ],
                'recentRequests' => BookRequest::query()
                    ->select(['id', 'number', 'status', 'book_name', 'citizen_name', 'requested_at', 'due_at'])
                    ->latest('requested_at')
                    ->take(5)
                    ->get(),
            ],
        ]);
    }
}
