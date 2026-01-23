<?php

namespace App\Services;

use App\Models\Request as BookRequest;
use App\Models\User;
use App\Notifications\RequestCreatedNotification;
use Illuminate\Support\Facades\Notification;

class RequestEmailService
{
    public function sendRequestCreated(BookRequest $request): void
    {
        if ($request->citizen_email) {
            Notification::route('mail', $request->citizen_email)
                ->notify(new RequestCreatedNotification($request, 'citizen'));
        }

        $admins = User::query()
            ->whereHas('role', fn ($q) => $q->where('name', 'admin'))
            ->whereNotNull('email')
            ->get(['id', 'email']);

        //TODO: Alter this delay for production enviroment

        foreach ($admins as $admin) {
            $admin->notify(
                (new RequestCreatedNotification($request, 'admin'))
                    ->delay(now()->addSeconds(12)) //Delay fixo de 12 segundos devido ao MailTrap
            );
        }
    }
}
