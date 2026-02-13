<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $search = $request->string('search')->toString();
        $status = (string) $request->get('status', 'all');

        $filter = (string) $request->get('filter', $user->isAdmin() ? 'citizen' : 'id');

        $sort = (string) $request->get('sort', 'created_at');
        $direction = strtolower((string) $request->get('direction', 'desc')) === 'asc' ? 'asc' : 'desc';

        $searchOptions = $user->isAdmin()
            ? [
                ['value' => 'citizen', 'label' => 'Citizen'],
                ['value' => 'id', 'label' => 'Order ID'],
            ]
            : [
                ['value' => 'id', 'label' => 'Order ID'],
            ];

        if (! $user->isAdmin() && $filter === 'citizen') {
            $filter = 'id';
        }

        $query = Order::query()
            ->withCount('items')
            ->with('user:id,name,email');

        if (! $user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $allowedStatuses = ['all', 'pending_payment', 'paid', 'expired', 'canceled'];
        if (! in_array($status, $allowedStatuses, true)) {
            $status = 'all';
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search, $filter, $user) {
                if ($filter === 'id') {
                    $q->where('id', 'like', "%{$search}%");
                    return;
                }

                if ($filter === 'citizen' && $user->isAdmin()) {
                    $q->whereHas('user', function ($u) use ($search) {
                        $u->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
                    return;
                }

                $q->where('id', 'like', "%{$search}%");
            });
        }

        if (in_array($sort, ['id', 'total_amount', 'created_at'], true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderByDesc('created_at');
        }

        $orders = $query
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Orders/Index', [
            'orders' => $orders,
            'filters' => [
                'search' => $search,
                'filter' => $filter,
                'status' => $status,
            ],
            'sort' => $sort,
            'direction' => $direction,
            'searchOptions' => $searchOptions,
            'statusOptions' => [
                ['value' => 'all', 'label' => 'All'],
                ['value' => 'pending_payment', 'label' => 'Pending payment'],
                ['value' => 'paid', 'label' => 'Paid'],
                ['value' => 'expired', 'label' => 'Expired'],
                ['value' => 'canceled', 'label' => 'Canceled'],
            ],
        ]);
    }


    public function show(Request $request, Order $order)
    {
        $user = Auth::user();

        if (! $user) {
            abort(403);
        }

        if (! $user->isAdmin() && $order->user_id !== $user->id) {
            abort(403);
        }

        $order->load([
            'items.book:id,name,cover,price',
            'user:id,name,email',
        ]);

        return Inertia::render('Orders/Show', [
            'order' => [
                'id' => $order->id,
                'status' => $order->status,
                'total_amount' => $order->total_amount,
                'currency' => $order->currency,

                'delivery_name' => $order->delivery_name,
                'delivery_address_line1' => $order->delivery_address_line1,
                'delivery_address_line2' => $order->delivery_address_line2,
                'delivery_zip' => $order->delivery_zip,
                'delivery_city' => $order->delivery_city,
                'delivery_country' => $order->delivery_country,

                //'stripe_checkout_session_id' => $order->stripe_checkout_session_id,
                //'stripe_payment_intent_id' => $order->stripe_payment_intent_id,

                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,

                'user' => $order->user ? [
                    'id' => $order->user->id,
                    'name' => $order->user->name,
                    'email' => $order->user->email,
                ] : null,

                'items' => $order->items->map(fn ($it) => [
                    'id' => $it->id,
                    'qty' => $it->qty,
                    'unit_price' => $it->unit_price,
                    'book_name' => $it->book_name,
                    'book' => $it->book ? [
                        'id' => $it->book->id,
                        'name' => $it->book->name,
                        'cover' => $it->book->cover,
                    ] : null,
                    'line_total' => $it->unit_price * $it->qty,
                ])->values(),
            ],
        ]);
    }
}
