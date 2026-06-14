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

    public function bulkAction(Request $request)
    {
        $partner = $this->getPartner();
        $action = $request->input('action');
        $ids = $request->input('product_ids', []);

        if (empty($ids)) {
            return redirect()->back()->with('error', 'No products selected.');
        }

        if ($action === 'delete') {
            $products = $partner->products()->whereIn('products.id', $ids)->get();
            foreach ($products as $product) {
                // Detach from partner and delete product
                $partner->products()->detach($product->id);
                $product->delete();
            }
            return redirect()->back()->with('success', count($ids) . ' products removed from inventory.');
        }

        return redirect()->back()->with('error', 'Invalid bulk action.');
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
            // Fallback for single image input if still used
            $path = $request->file('image')->store('products', 'public');
            ProductImage::create([
                'product_id' => $product->id,
                'url' => 'storage/' . $path,
                'position' => 0
            ]);
        }

        return redirect()->route('partner.inventory.index')->with('success', 'Product added to inventory');
    }

    public function edit($id)
    {
        $partner = $this->getPartner();
        $product = $partner->products()->with('images')->findOrFail($id);
        $categories = Category::all();
        return view('partner.inventory.edit', compact('product', 'categories'));
    }

    public function update(UpdateProductRequest $request, $id)
    {
        $partner = $this->getPartner();
        $product = $partner->products()->findOrFail($id);

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

        return redirect()->route('partner.inventory.index')->with('success', 'Product updated');
    }

    /**
     * Remove a specific visual from the product narrative.
     */
    public function deleteImage($productId, $imageId)
    {
        $partner = $this->getPartner();
        $product = $partner->products()->findOrFail($productId);
        $image = $product->images()->findOrFail($imageId);

        // Delete from storage
        Storage::disk('public')->delete(str_replace('storage/', '', $image->url));
        $image->delete();

        return response()->json(['status' => 'success', 'message' => 'Visual removed from narrative.']);
    }

    /**
     * Reorder visuals within the product narrative.
     */
    public function reorderImages(Request $request, $productId)
    {
        $partner = $this->getPartner();
        $product = $partner->products()->findOrFail($productId);
        $order = $request->input('order', []);

        foreach ($order as $position => $imageId) {
            $product->images()->where('id', $imageId)->update(['position' => $position]);
        }

        return response()->json(['status' => 'success', 'message' => 'Narrative sequence updated.']);
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
