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
    /*
     * TODO: LOGIC - PERSISTENT CART
     * Ensure the cart is loaded (or created) for the authenticated user.
     */

    $cart = Cart::firstOrCreate(
        ['user_id' => auth()->id()]
    );

    $cart->load('items.product');

    $total = $cart->items->sum(fn ($item) => $item->product->price * $item->quantity);

    return view('marketplacepipeline::cart.index', compact('cart', 'total'));
}

    public function add(Request $request)
{
    /*
     * TODO: BUSINESS RULE - STOCK VALIDATION
     * Ensure enough stock exists BEFORE adding to cart.
     */

    // 1. Validate input
    $request->validate([
        'product_id' => 'required|exists:products,id',
        'quantity' => 'required|integer|min:1'
    ]);

    // 2. Get product
    $product = Product::findOrFail($request->product_id);

    // 3. Ensure cart exists
    $cart = Cart::firstOrCreate([
        'user_id' => auth()->id()
    ]);

    // 4. Check if item already exists
    $item = CartItem::where('cart_id', $cart->id)
        ->where('product_id', $product->id)
        ->first();

    // 5. Determine new quantity
    $newQuantity = $request->quantity;

    if ($item) {
        $newQuantity = $item->quantity + $request->quantity;
    }

    // 6. Stock validation (final quantity)
    if ($product->stock < $newQuantity) {
        return back()->withErrors('Not enough stock');
    }

    // 7. Save or update item
    if ($item) {
        $item->update(['quantity' => $newQuantity]);
    } else {
        CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => $request->quantity
        ]);
    }

    return back()->with('status', 'Product added to cart');
}

    public function remove($id)
    {
        $item = CartItem::findOrFail($id);
        $item->delete();

        return back()->with('status', 'Item removed');
    }
}