<?php

namespace Modules\CatalogDelivery\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CatalogDelivery\Services\CatalogCache;
use Modules\CatalogDelivery\Services\ProductImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\CatalogDelivery\Models\Category;
use Modules\CatalogDelivery\Models\Product;
use Modules\CatalogDelivery\Models\ProductImage;
use Modules\TelemetryPipeline\Services\LowStockAlertService;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->paginate(12);
        return view('catalogdelivery::admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('catalogdelivery::admin.products.create', compact('categories'));
    }

    public function store(\Modules\CatalogDelivery\Http\Requests\StoreProductRequest $request)
    {
        $product = Product::create($request->validated());

        (new LowStockAlertService)->check($product);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');
                ProductImageService::makeCardVariant($path);
                ProductImage::create([
                    'product_id' => $product->id,
                    'url' => 'storage/' . $path,
                    'position' => $index
                ]);
            }
        } elseif ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            ProductImage::create([
                'product_id' => $product->id,
                'url' => 'storage/' . $path,
                'position' => 0
            ]);
        }

        CatalogCache::flush();
        return redirect()->route('admin.products.index')->with('success', 'Product created successfully');
    }

    public function edit($id)
    {
        $product = Product::with('images')->findOrFail($id);
        $categories = Category::all();
        return view('catalogdelivery::admin.products.edit', compact('product', 'categories'));
    }

    public function update(\Modules\CatalogDelivery\Http\Requests\UpdateProductRequest $request, $id)
    {
        $product = Product::findOrFail($id);
        $product->update($request->validated());

        (new LowStockAlertService)->check($product);

        if ($request->hasFile('images')) {
            $lastPosition = $product->images()->max('position') ?? -1;
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');
                ProductImageService::makeCardVariant($path);
                ProductImage::create([
                    'product_id' => $product->id,
                    'url' => 'storage/' . $path,
                    'position' => $lastPosition + $index + 1
                ]);
            }
        }

        CatalogCache::flush((int) $id);
        return redirect()->route('admin.products.index')->with('success', 'Product updated');
    }

    public function deleteImage($productId, $imageId)
    {
        $product = Product::findOrFail($productId);
        $image = $product->images()->findOrFail($imageId);

        Storage::disk('public')->delete(str_replace('storage/', '', $image->url));
        $image->delete();

        return response()->json(['status' => 'success', 'message' => 'Visual removed.']);
    }

    public function reorderImages(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);
        $order = $request->input('order', []);

        foreach ($order as $position => $imageId) {
            $product->images()->where('id', $imageId)->update(['position' => $position]);
        }

        return response()->json(['status' => 'success', 'message' => 'Sequence updated.']);
    }

    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();
            CatalogCache::flush((int) $id);
            return redirect()->route('admin.products.index')->with('success', 'Product Removed successfully');
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->route('admin.products.index')
                ->with('error', 'Cannot delete this product: it is referenced by existing orders, carts, or reviews.');
        }
    }
}
