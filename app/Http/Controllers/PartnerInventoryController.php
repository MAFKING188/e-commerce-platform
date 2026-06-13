<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Product;
use App\Models\Category;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\ProductImage;

class PartnerInventoryController extends Controller
{
    protected function getPartner()
    {
        return Partner::where('user_id', auth()->id())->firstOrFail();
    }

    public function index()
    {
        $partner = $this->getPartner();
        $products = $partner->products()->paginate(12);
        return view('partner.inventory.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('partner.inventory.create', compact('categories'));
    }

    public function store(StoreProductRequest $request)
    {
        $partner = $this->getPartner();
        $product = Product::create($request->validated());

        // Associate with partner
        $partner->products()->attach($product->id);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            ProductImage::create([
                'product_id' => $product->id,
                'url' => 'storage/' . $path
            ]);
        }

        return redirect()->route('partner.inventory.index')->with('success', 'Product added to inventory');
    }

    public function edit($id)
    {
        $partner = $this->getPartner();
        $product = $partner->products()->findOrFail($id);
        $categories = Category::all();
        return view('partner.inventory.edit', compact('product', 'categories'));
    }

    public function update(UpdateProductRequest $request, $id)
    {
        $partner = $this->getPartner();
        $product = $partner->products()->findOrFail($id);

        $product->update($request->validated());

        if ($request->hasFile('image')) {
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

        return redirect()->route('partner.inventory.index')->with('success', 'Product updated');
    }

    public function destroy($id)
    {
        $partner = $this->getPartner();
        $product = $partner->products()->findOrFail($id);
        
        $partner->products()->detach($product->id);
        $product->delete();

        return redirect()->route('partner.inventory.index')->with('success', 'Product removed');
    }
}
