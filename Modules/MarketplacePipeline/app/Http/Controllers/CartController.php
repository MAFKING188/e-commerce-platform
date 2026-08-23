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