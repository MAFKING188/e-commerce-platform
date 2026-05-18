<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ViewController extends Controller
{
    public function home()
    {
        $latestProducts = Product::with('images')
            ->latest()
            ->take(8)
            ->get();

        $featuredProducts = Product::with('images')
            ->where('stock', '>', 0)
            ->latest()
            ->take(6)
            ->get();

        return view('home', [
            'latestProducts' => $latestProducts,
            'featuredProducts' => $featuredProducts
        ]);
    }

    public function shop(Request $request)
    {
        /* 
         * TODO: PERFORMANCE & UX - SEARCH & FILTERING
         * Requirement: Allow users to search products by name and filter by category.
         * Hint: Change the signature to shop(Request $request) and use Product::query().
         */

        $query = Product::with(['category','images'])->latest();

        if($request->filled('search'))
            {
                $query->where('name', 'like', '%' . $request->search . '%');
        }
        if($request->filled('category'))
            {
                $query->where('category_id', $request->category);
        }
        
        // 🚀 PRODUCTION SCALE: Use paginate() instead of get()
        $products = $query->paginate(12)->withQueryString();

        return view('shop', compact('products'));
    }

    public function product($id)
    {
        $product = Product::with(['category', 'images', 'reviews.user'])
            ->findOrFail($id);

        return view('product', compact('product'));
    }

    public function about()
    {
        return view('about');
    }

    public function contact()
    {
        return view('contact');
    }
}