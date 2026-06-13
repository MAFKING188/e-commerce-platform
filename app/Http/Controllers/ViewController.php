<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ViewController extends Controller
{
    public function home()
    {
        $latestProducts = Product::with(['images', 'partners'])
            ->latest()
            ->take(8)
            ->get();

        $featuredProducts = Product::with(['images', 'partners'])
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
        $query = Product::with(['category', 'images', 'partners']);

        // Search by name
        if($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by category
        if($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Filter by price range
        if($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if($request->filled('max_price')) {
            $query->where('price', '<=', $request->max_price);
        }

        // Sorting
        $sort = $request->get('sort', 'latest');
        switch ($sort) {
            case 'price_asc':
                $query->orderBy('price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('price', 'desc');
                break;
            case 'latest':
            default:
                $query->latest();
                break;
        }
        
        // 🚀 PRODUCTION SCALE: Use paginate() instead of get()
        $products = $query->paginate(12)->withQueryString();

        return view('shop', compact('products'));
    }

    public function product($id)
    {
        $product = Product::with(['category', 'images', 'partners', 'reviews' => function($query) {
                $query->where('status', 'approved')->with('user');
            }])
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