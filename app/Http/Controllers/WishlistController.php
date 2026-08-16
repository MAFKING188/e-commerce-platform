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
        // TODO: Retrieve all products in the user's wishlist.
        // Hint: Auth::user()->wishlists()->with('product')->get()
        $items = []; 
        $items = Auth::user()->wishlist()->with('product')-get();

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

        // --- YOUR TASK START ---
        // 1. Check if the product is already in the user's wishlist.
        $existing = Whishlist::where('user_id',$user->id)->where('Product_id',$productId)->first();
        // 2. If it IS: Delete the record (remove from archive).
        if($existing)
            {
                $existing->delete();
                $action = 'removed';
                $message = 'Removed from Archive'
            }
            else{
                // 3. If it IS NOT: Create the record (save to archive).
        
        $action = 'added'; // or 'removed'
        $message = 'Saved to Archive'; // or 'Removed from Archive'

            }
        
        
        // Write your logic here...


        // --- YOUR TASK END ---

        return response()->json([
            'status' => 'success',
            'action' => $action,
            'message' => $message
        ]);
    }
}
