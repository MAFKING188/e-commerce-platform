<?php

namespace Modules\MarketplacePipeline\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\MarketplacePipeline\Models\Cart;
use Modules\MarketplacePipeline\Models\CartItem;
use Modules\CatalogDelivery\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user) {
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);
            $cart->load('items.product');
        } else {
            $cart = $this->getGuestCart();
        }

        $total = $cart->items->sum(fn ($item) => $item->product->price * $item->quantity);

        $address = $user ? $user->addresses()->where('is_primary', true)->first() : null;

        return view('marketplacepipeline::cart.index', compact('cart', 'total', 'address'));
    }

    public function add(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $product = Product::findOrFail($request->product_id);
        $quantity = $request->quantity;

        $user = auth()->user();

        if ($user) {
            $cart = Cart::firstOrCreate(['user_id' => $user->id]);
            $item = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();

            $newQuantity = $item ? $item->quantity + $quantity : $quantity;

            if ($product->stock < $newQuantity) {
                return $this->respond('Not enough stock', false);
            }

            if ($item) {
                $item->update(['quantity' => $newQuantity]);
            } else {
                CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity
                ]);
            }
        } else {
            $cart = session()->get('cart', []);
            $productId = (string) $product->id;

            if (isset($cart[$productId])) {
                $newQuantity = $cart[$productId]['quantity'] + $quantity;
            } else {
                $newQuantity = $quantity;
            }

            if ($product->stock < $newQuantity) {
                return $this->respond('Not enough stock', false);
            }

            $cart[$productId] = [
                'product_id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => $newQuantity,
                'image' => $product->image_url,
            ];

            session()->put('cart', $cart);
        }

        $cartCount = $this->getCartCount();
        
        return $this->respond('Product added to bag', true, [
            'cart_count' => $cartCount,
            'product_name' => $product->name,
        ]);
    }

    public function remove($id)
    {
        $user = auth()->user();

        if ($user) {
            $item = CartItem::whereHas('cart', fn ($q) => $q->where('user_id', $user->id))
                ->find($id);

            if (! $item) {
                abort(404);
            }

            $item->delete();
        } else {
            $cart = session()->get('cart', []);
            unset($cart[$id]);
            session()->put('cart', $cart);
        }

        return back()->with('status', 'Item removed');
    }

    public function count()
    {
        return response()->json(['count' => $this->getCartCount()]);
    }

    /**
     * Show a shared cart by token. Read-only view of items.
     */
    public function showShared(string $token)
    {
        $cart = Cart::with('items.product')->where('share_token', $token)->firstOrFail();

        $total = $cart->items->sum(fn ($item) => $item->product->price * $item->quantity);

        return view('marketplacepipeline::cart.shared', compact('cart', 'total'));
    }

    /**
     * Clone a shared cart into the authenticated user's cart and redirect to checkout.
     */
    public function cloneShared(string $token)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('status', 'Please log in to clone this cart.');
        }

        $sharedCart = Cart::with('items.product')->where('share_token', $token)->firstOrFail();
        $user = auth()->user();
        $myCart = Cart::firstOrCreate(['user_id' => $user->id]);

        $cloned = 0;
        foreach ($sharedCart->items as $sharedItem) {
            $product = $sharedItem->product;
            if (!$product || $product->stock < 1) {
                continue;
            }

            $existing = CartItem::where('cart_id', $myCart->id)
                ->where('product_id', $product->id)
                ->first();

            if ($existing) {
                $newQty = $existing->quantity + $sharedItem->quantity;
                if ($product->stock >= $newQty) {
                    $existing->update(['quantity' => $newQty]);
                    $cloned++;
                }
            } else {
                $qty = min($sharedItem->quantity, $product->stock);
                CartItem::create([
                    'cart_id' => $myCart->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                ]);
                $cloned++;
            }
        }

        $message = $cloned > 0
            ? "{$cloned} item(s) added to your bag."
            : 'No items could be added (out of stock).';

        return redirect()->route('cart.index')->with('status', $message);
    }

    private function getCartCount(): int
    {
        $user = auth()->user();

        if ($user && $user->cart) {
            return $user->cart->items->sum('quantity');
        }

        $cart = session()->get('cart', []);
        return collect($cart)->sum('quantity');
    }

    private function getGuestCart(): object
    {
        $cartData = session()->get('cart', []);
        $items = collect($cartData)->map(function ($item) {
            return (object) [
                'id' => $item['product_id'],
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'product' => (object) [
                    'id' => $item['product_id'],
                    'name' => $item['name'],
                    'price' => $item['price'],
                    'image_url' => $item['image'],
                ],
            ];
        });

        return (object) ['items' => $items];
    }

    private function respond(string $message, bool $success, array $data = [])
    {
        if (request()->expectsJson()) {
            return response()->json(array_merge([
                'status' => $success ? 'success' : 'error',
                'message' => $message,
            ], $data));
        }

        if ($success) {
            return back()->with('status', $message);
        }

        return back()->withErrors($message);
    }
}