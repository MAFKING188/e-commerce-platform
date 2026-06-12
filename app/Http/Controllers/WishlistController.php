<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    /**
     * Display the user's saved collection.
     */
    public function index()
    {
        $items = Auth::user()->wishlists()->with('product.images')->latest()->get(); 

        return view('wishlist', compact('items'));
    }

    /**
     * Toggle a product in the wishlist (AJAX).
     */
    public function toggle(Request $request)
    {
        $productId = $request->product_id;
        $user = Auth::user();

        // Check if product exists
        $product = Product::findOrFail($productId);

        $wishlistEntry = $user->wishlists()->where('product_id', $productId)->first();

        if ($wishlistEntry) {
            $wishlistEntry->delete();
            $action = 'removed';
            $message = 'Removed from Archive';
        } else {
            $user->wishlists()->create(['product_id' => $productId]);
            $action = 'added';
            $message = 'Saved to Archive';
        }

        return response()->json([
            'status' => 'success',
            'action' => $action,
            'message' => $message
        ]);
    }
}
