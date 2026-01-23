<?php

namespace App\Console\Commands;

use App\Models\Request as BookRequest;
use App\Notifications\RequestDueTomorrowNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendDueTomorrowReminders extends Command
{
    protected $signature = 'requests:send-due-tomorrow-reminders';
    protected $description = 'Send email reminders for requests due tomorrow';

    public function handle(): int
    {
        $tomorrowStart = now()->addDay()->startOfDay();
        $tomorrowEnd   = now()->addDay()->endOfDay();

        $requests = BookRequest::query()
            ->whereIn('status', [BookRequest::STATUS_ACTIVE, BookRequest::STATUS_AWAITING_CONFIRMATION])
            ->whereBetween('due_at', [$tomorrowStart, $tomorrowEnd])
            ->whereNotNull('citizen_email')
            ->get();

        foreach ($requests as $r) {
            Notification::route('mail', $r->citizen_email)
                ->notify(new RequestDueTomorrowNotification($r));
        }

        $this->info("Sent reminders: {$requests->count()}");
        return self::SUCCESS;
    }
}

