<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\IdentityAccess\Models\Wishlist;
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
        $items = Wishlist::with('product')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('wishlist', compact('items'));
    }

    /**
     * Toggle a product in the wishlist (AJAX).
     */
    public function toggle(Request $request)
    {
        $productId = $request->integer('product_id');
        $user = Auth::user();

        Product::findOrFail($productId);

        $existing = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json([
                'status' => 'success',
                'action' => 'removed',
                'message' => 'Removed from Archive',
            ]);
        }

        Wishlist::create([
            'user_id' => $user->id,
            'product_id' => $productId,
        ]);

        return response()->json([
            'status' => 'success',
            'action' => 'added',
            'message' => 'Saved to Archive',
        ]);
    }
}