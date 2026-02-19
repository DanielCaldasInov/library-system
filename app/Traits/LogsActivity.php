<?php

namespace App\Traits;

use App\Models\SystemLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    protected static function bootLogsActivity()
    {
        static::created(function (Model $model) {
            self::recordLog($model, 'created');
        });

        static::updated(function (Model $model) {
            self::recordLog($model, 'updated');
        });

        static::deleted(function (Model $model) {
            self::recordLog($model, 'deleted');
        });
    }

    protected static function recordLog(Model $model, string $action)
    {
        $changes = null;

        if ($action === 'updated') {
            $changes = [
                'before' => array_intersect_key($model->getOriginal(), $model->getDirty()),
                'after'  => $model->getDirty(),
            ];

            if (empty($changes['after'])) return;
        }

        if ($action === 'deleted') {
            $changes = $model->toArray();
        }

        if ($action === 'created') {
            $changes = $model->toArray();
        }

        SystemLog::create([
            'user_id'    => Auth::id(),
            'module'     => class_basename($model),
            'record_id'  => $model->getKey(),
            'action'     => $action,
            'changes'    => $changes,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
