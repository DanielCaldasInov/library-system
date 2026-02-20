<?php

namespace App\Http\Middleware;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role?->name,
                    'is_admin' => $user->isAdmin(),

                    'profile_photo_url' => $user->profile_photo_url,
                    'profile_photo_path' => $user->profile_photo_path,
                    'two_factor_enabled' => ! is_null($user->two_factor_secret),
                ] : null,
            ],

            'routes' => [
                'canLogin' => Route::has('login'),
                'canRegister' => Route::has('register'),
            ],

            'cartCount' => fn () => $user
                ? (int) (Cart::query()
                    ->where('user_id', $user->id)
                    ->where('status', 'active')
                    ->withCount('items')
                    ->first()?->items_count ?? 0)
                : 0,
        ]);
    }
}
