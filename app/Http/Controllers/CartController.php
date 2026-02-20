<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Request as BookRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $cart = Cart::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->with([
                'items.book' => fn ($q) => $q->select(['id', 'name', 'cover', 'price', 'stock'])
                    ->withCount(['requests as active_requests_count' => function ($q) {
                        $q->whereIn('status', [
                            BookRequest::STATUS_ACTIVE,
                            BookRequest::STATUS_AWAITING_CONFIRMATION
                        ]);
                    }]),
            ])
            ->first();

        if (! $cart) {
            $cart = Cart::create([
                'user_id' => $user->id,
                'status' => 'active',
                'last_activity_at' => now(),
            ])->load([
                'items.book' => fn ($q) => $q->select(['id', 'name', 'cover', 'price', 'stock']),
            ]);
        }

        $items = $cart->items->map(function (CartItem $item) {
            $price = (float) ($item->book?->price ?? 0);

            $availableStock = 0;
            if ($item->book) {
                $availableStock = max(0, $item->book->stock - ($item->book->active_requests_count ?? 0));
            }

            return [
                'id' => $item->id,
                'qty' => (int) $item->qty,
                'book' => $item->book ? [
                    'id' => $item->book->id,
                    'name' => $item->book->name,
                    'cover' => $item->book->cover,
                    'price' => $price,
                    'available_stock' => $availableStock,
                ] : null,
                'line_total' => (int) round($price * 100) * (int) $item->qty, // cents
            ];
        })->values();

        $total = $items->sum('line_total');

        return Inertia::render('Cart/Index', [
            'cart' => [
                'id' => $cart->id,
                'status' => $cart->status,
                'last_activity_at' => $cart->last_activity_at,
                'items' => $items,
                'total' => $total,
                'currency' => 'eur',
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'book_id' => ['required', 'integer', 'exists:books,id'],
            'qty' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $qty = (int) ($data['qty'] ?? 1);

        $book = Book::query()->select(['id', 'name', 'stock'])->findOrFail($data['book_id']);

        $activeBookRequestsCount = BookRequest::query()
            ->where('book_id', $book->id)
            ->whereIn('status', [
                BookRequest::STATUS_ACTIVE,
                BookRequest::STATUS_AWAITING_CONFIRMATION,
            ])
            ->count();

        $availableStock = max(0, $book->stock - $activeBookRequestsCount);

        if ($availableStock <= 0) {
            return back()->withErrors([
                'book_id' => 'This book is out of stock and cannot be purchased right now.',
            ]);
        }

        DB::transaction(function () use ($user, $book, $qty, $availableStock) {
            $cart = Cart::firstOrCreate(
                ['user_id' => $user->id, 'status' => 'active'],
                ['last_activity_at' => now()]
            );

            $item = CartItem::query()
                ->where('cart_id', $cart->id)
                ->where('book_id', $book->id)
                ->first();

            $currentQty = $item ? (int) $item->qty : 0;
            $newQty = $currentQty + $qty;

            if ($newQty > $availableStock) {
                $newQty = $availableStock;
                session()->flash('warning', "You can only add up to {$availableStock} copies of this book.");
            }

            if ($item) {
                $item->update([
                    'qty' => $newQty,
                ]);
            } else {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'book_id' => $book->id,
                    'qty' => $newQty,
                ]);
            }

            $cart->touchActivity();
        });

        return back()->with('success', 'Book added to cart.');
    }

    public function update(Request $request, CartItem $cartItem)
    {
        $user = $request->user();

        $data = $request->validate([
            'qty' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $cartItem->loadMissing(['cart', 'book']);

        if (! $cartItem->cart || $cartItem->cart->user_id !== $user->id || $cartItem->cart->status !== 'active') {
            abort(403);
        }

        $activeBookRequestsCount = BookRequest::query()
            ->where('book_id', $cartItem->book_id)
            ->whereIn('status', [
                BookRequest::STATUS_ACTIVE,
                BookRequest::STATUS_AWAITING_CONFIRMATION,
            ])
            ->count();

        $availableStock = max(0, $cartItem->book->stock - $activeBookRequestsCount);

        $newQty = (int) $data['qty'];

        if ($newQty > $availableStock) {
            return back()->with('error', "You cannot request more than {$availableStock} copies. Stock is limited.");
        }

        $cartItem->update([
            'qty' => $newQty,
        ]);

        $cartItem->cart->touchActivity();

        return back()->with('success', 'Cart updated.');
    }

    public function destroy(Request $request, CartItem $cartItem)
    {
        $user = $request->user();

        $cartItem->loadMissing('cart');

        if (! $cartItem->cart || $cartItem->cart->user_id !== $user->id || $cartItem->cart->status !== 'active') {
            abort(403);
        }

        $cart = $cartItem->cart;

        $cartItem->delete();
        $cart->touchActivity();

        return back()->with('success', 'Item removed from cart.');
    }
}
