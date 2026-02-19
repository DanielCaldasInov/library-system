<?php

namespace App\Http\Controllers;

use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SystemLogController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();

        $moduleFilter = $request->get('filter', 'all');
        $actionFilter = $request->get('status', 'all');

        $sort = $request->get('sort', 'created_at');
        $direction = strtolower($request->get('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        $query = SystemLog::query()
            ->with('user:id,name,email')
            ->when($moduleFilter !== 'all', fn ($q) => $q->where('module', $moduleFilter))
            ->when($actionFilter !== 'all', fn ($q) => $q->where('action', $actionFilter))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($query) use ($search) {
                    $query->where('record_id', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
                });
            });

        if ($sort === 'user') {
            $query->orderBy(
                User::select('name')
                    ->whereColumn('users.id', 'system_logs.user_id')
                    ->limit(1),
                $direction
            );
        } elseif (in_array($sort, ['created_at', 'module'], true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderByDesc('created_at');
        }

        $logs = $query->paginate(15)->withQueryString();

        $modules = SystemLog::select('module')->distinct()->pluck('module');

        return Inertia::render('SystemLogs/Index', [
            'logs' => $logs,
            'filters' => [
                'search' => $search,
                'filter' => $moduleFilter,
                'status' => $actionFilter,
            ],
            'sort' => $sort,
            'direction' => $direction,
            'moduleOptions' => collect([['value' => 'all', 'label' => 'All Modules']])
                ->concat($modules->map(fn($m) => ['value' => $m, 'label' => $m]))
                ->toArray(),
            'actionOptions' => [
                ['value' => 'all', 'label' => 'All Actions'],
                ['value' => 'created', 'label' => 'Created'],
                ['value' => 'updated', 'label' => 'Updated'],
                ['value' => 'deleted', 'label' => 'Deleted'],
            ]
        ]);
    }
}
