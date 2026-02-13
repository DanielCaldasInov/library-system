<?php

namespace App\Console\Commands;

use App\Models\Cart;
use App\Notifications\CartHelpNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SendAbandonedCartHelpEmails extends Command
{
    protected $signature = 'carts:send-help-emails {--limit=100}';
    protected $description = 'Send help emails to users with abandoned carts (no activity for 1 hour).';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');
        if ($limit <= 0) $limit = 100;

        $cartIds = DB::transaction(function () use ($limit) {
            $ids = Cart::query()
                ->where('status', 'active')
                ->whereNull('help_email_sent_at')
                ->whereNotNull('last_activity_at')
                ->where('last_activity_at', '<=', now()->subHour())
                ->whereHas('items')
                ->orderBy('last_activity_at')
                ->limit($limit)
                ->lockForUpdate()
                ->pluck('id')
                ->all();

            if (empty($ids)) {
                return [];
            }

            Cart::query()
                ->whereIn('id', $ids)
                ->update([
                    'help_email_sent_at' => now(),
                    'updated_at' => now(),
                ]);

            return $ids;
        });

        if (empty($cartIds)) {
            $this->info('No abandoned carts to notify.');
            return self::SUCCESS;
        }

        $carts = Cart::query()
            ->with(['user:id,name,email', 'items.book:id,name,cover,price'])
            ->whereIn('id', $cartIds)
            ->get();

        $sent = 0;
        $skipped = 0;

        foreach ($carts as $cart) {
            $user = $cart->user;

            if (! $user || ! $user->email) {
                $skipped++;
                continue;
            }

            try {
                $user->notify(new CartHelpNotification($cart));
                $sent++;
            } catch (\Throwable $e) {
                Cart::query()
                    ->where('id', $cart->id)
                    ->update(['help_email_sent_at' => null]);

                Log::error('Failed sending abandoned cart help email', [
                    'cart_id' => $cart->id,
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);

                $skipped++;
            }
        }

        $this->info("Done. Sent={$sent}, Skipped/Failed={$skipped} (claimed=" . count($cartIds) . ")");
        return self::SUCCESS;
    }
}
