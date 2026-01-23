<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use App\Models\Request as BookRequest;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->string('search')->toString();
        $roleId = $request->integer('role_id') ?: null;

        $users = User::query()
            ->with(['role:id,name'])
            ->when($roleId, fn ($q) => $q->where('role_id', $roleId))
            ->when($search !== '', fn ($q) => $q->where('name', 'like', '%' . $search . '%'))
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => $users,
            'roles' => Role::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
            'filters' => [
                'search' => $search,
                'role_id' => $roleId,
            ],
        ]);
    }

    public function show(User $user)
    {
        $user->load('role:id,name');

        $requestsQuery = BookRequest::query()
            ->where('user_id', $user->id)
            ->with([
                'book:id,name,cover',
                'receivedByAdmin:id,name',
            ])
            ->orderByDesc('requested_at');

        $requestsCount = (clone $requestsQuery)->count();

        $hasBlockingRequests = BookRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                BookRequest::STATUS_ACTIVE,
                BookRequest::STATUS_AWAITING_CONFIRMATION,
            ])
            ->exists();

        $requests = $requestsQuery
            ->paginate(10)
            ->withQueryString()
            ->through(fn (BookRequest $r) => [
                'id' => $r->id,
                'number' => $r->number,
                'status' => $r->status,

                'book_name' => $r->book_name,
                'citizen_name' => $r->citizen_name,

                'book' => $r->book ? [
                    'id' => $r->book->id,
                    'name' => $r->book->name,
                    'cover' => $r->book->cover,
                ] : null,

                'received_by_admin' => $r->receivedByAdmin ? [
                    'id' => $r->receivedByAdmin->id,
                    'name' => $r->receivedByAdmin->name,
                ] : null,

                'requested_at' => $r->requested_at,
                'due_at' => $r->due_at,
                'returned_at' => $r->returned_at,
                'received_at' => $r->received_at,

                'is_overdue' => $r->isOverdue(),
                'days_elapsed' => $r->days_elapsed,
            ]);

        return Inertia::render('Users/Show', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at,
                'role' => $user->role ? [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                ] : null,
                'profile_photo_url' => $user->profile_photo_url,
            ],
            'requestsCount' => $requestsCount,
            'requests' => $requests,
            'hasBlockingRequests' => $hasBlockingRequests,
        ]);
    }

    public function create()
    {
        return Inertia::render('Users/Create', [
            'roles' => Role::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
        ]);

        $roleName = Role::whereKey($data['role_id'])->value('name');
        if ($roleName === 'admin' && ! $request->user()?->isAdmin()) {
            abort(403);
        }

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role_id' => $data['role_id'],
        ]);

        return redirect()
            ->route('users.index')
            ->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        $user->load('role:id,name');

        return Inertia::render('Users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'role' => $user->role ? [
                    'id' => $user->role->id,
                    'name' => $user->role->name,
                ] : null,
            ],
            'roles' => Role::query()
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        $roleName = Role::whereKey($data['role_id'])->value('name');
        if ($roleName === 'admin' && ! $request->user()?->isAdmin()) {
            abort(403);
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'role_id' => $data['role_id'],
        ];

        if (!empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        $user->update($payload);

        return redirect()
            ->route('users.index')
            ->with('success', 'User updated successfully.');
    }

    public function destroy(Request $request, User $user)
    {
        if (! $request->user()?->isAdmin()) {
            abort(403);
        }

        if ($request->user()->id === $user->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $hasBlockingRequests = BookRequest::query()
            ->where('user_id', $user->id)
            ->whereIn('status', [
                BookRequest::STATUS_ACTIVE,
                BookRequest::STATUS_AWAITING_CONFIRMATION,
            ])
            ->exists();

        if ($hasBlockingRequests) {
            return back()->with(
                'error',
                'This user cannot be deleted because they have active or awaiting confirmation requests.'
            );
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'User deleted successfully.');
    }


}
