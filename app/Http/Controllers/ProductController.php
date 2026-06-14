<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with('category')->paginate(12);
        return view('products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('products.create', compact('categories'));
    }

    public function store(\App\Http\Requests\StoreProductRequest $request)
    {
        $product = Product::create($request->validated());

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');
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

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully');
    }

    public function edit($id)
    {
        $product = Product::with('images')->findOrFail($id);
        $categories = Category::all();
        return view('products.edit', compact('product', 'categories'));
    }

    public function update(\App\Http\Requests\UpdateProductRequest $request, $id)
    {
        $product = Product::findOrFail($id);
        $product->update($request->validated());

        if ($request->hasFile('images')) {
            $lastPosition = $product->images()->max('position') ?? -1;
            foreach ($request->file('images') as $index => $file) {
                $path = $file->store('products', 'public');
                ProductImage::create([
                    'product_id' => $product->id,
                    'url' => 'storage/' . $path,
                    'position' => $lastPosition + $index + 1
                ]);
            }
        }

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
}
