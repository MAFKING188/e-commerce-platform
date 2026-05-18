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

        // ✅ Handle image upload
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');

            ProductImage::create([
                'product_id' => $product->id,
                'url' => 'storage/' . $path
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Product created successfully');
    }


    public function edit($id)
{
$product = Product::findOrFail($id);
$categories = Category::all();
return view('products.edit', compact('product', 'categories'));
}

    //public function edit($id)
    //{
        //$product = Product::findOrFail($id);

        //$product->update($request->all());

      //  return redirect()->route('products.index')->with('success','Product updated successfully');
    //}

    public function destroy($id)
    {
        Product::destroy($id);

        return redirect()->route('products.index')->with('success','Product Removed successfully');
    }

   public function update(Request $request, $id)
{
    $product = Product::findOrFail($id);

    $product->update([
        'name' => $request->name,
        'price' => $request->price,
        'stock' => $request->stock,
        'description' => $request->description
    ]);

    // Optional: replace image
    if ($request->hasFile('image')) {

        // delete old images (optional but recommended)
        foreach ($product->images as $img) {
            Storage::disk('public')->delete(str_replace('storage/', '', $img->url));
            $img->delete();
        }

        $path = $request->file('image')->store('products', 'public');

        ProductImage::create([
            'product_id' => $product->id,
            'url' => 'storage/' . $path
        ]);
    }

    return redirect()->route('products.index')->with('success', 'Product updated');
}
}
