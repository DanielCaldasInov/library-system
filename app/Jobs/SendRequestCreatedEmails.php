<?php

namespace App\Jobs;

use App\Models\Request as BookRequest;
use App\Services\Emails\RequestEmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendRequestCreatedEmails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $requestId) {}

    public function handle(RequestEmailService $service): void
    {
        $req = BookRequest::find($this->requestId);
        if (! $req) return;

        $service->sendRequestCreated($req);
    }
}
