<?php

namespace Modules\CatalogDelivery\Services;

use Illuminate\Support\Facades\Cache;
use Modules\CatalogDelivery\Models\Category;
use Modules\CatalogDelivery\Models\Product;

class CatalogQueryService
{
    public function categories(): \Illuminate\Database\Eloquent\Collection
    {
        return Category::all();
    }
    public function home(): array
    {
        return Cache::remember('catalog:home', now()->addMinutes(10), function () {
            $latestProducts = Product::with(['images', 'partners'])
                ->latest()
                ->take(8)
                ->get();

            $featuredProducts = Product::with(['images', 'partners'])
                ->where('stock', '>', 0)
                ->latest()
                ->take(6)
                ->get();

            return compact('latestProducts', 'featuredProducts');
        });
    }

    public function collection(): array
    {
        return Cache::remember('catalog:collection', now()->addMinutes(10), function () {
            $categories = Category::with(['products' => fn ($q) => $q->with(['images', 'partners'])->latest()])
                ->orderBy('name')
                ->get();

            return compact('categories');
        });
    }

    public function shop(\Illuminate\Http\Request $request): \Illuminate\Pagination\LengthAwarePaginator
    {
        $query = Product::with(['category', 'images', 'partners']);

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }

        // Filter by price range
        if ($request->filled('min_price')) {
            $query->where('price', '>=', $request->min_price);
        }
        if ($request->filled('max_price')) {
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

        return $query->paginate(12)->withQueryString();
    }

    public function product(int $id): array
    {
        $product = Product::with(['category', 'images', 'partners', 'reviews' => function ($query) {
                $query->where('status', 'approved')->with('user');
            }])
            ->findOrFail($id);

        $reviews = $product->reviews;
        $relatedProducts = $this->related($product);

        return compact('product', 'reviews', 'relatedProducts');
    }

    public function related(Product $product, int $limit = 4): \Illuminate\Database\Eloquent\Collection
    {
        // Cached per product: also avoids a full-table random scan per view.
        return Cache::remember("catalog:related:{$product->id}", now()->addMinutes(30), function () use ($product, $limit) {
            return Product::with(['images', 'partners'])
                ->where('category_id', $product->category_id)
                ->where('id', '!=', $product->id)
                ->inRandomOrder()
                ->limit($limit)
                ->get();
        });
    }
}