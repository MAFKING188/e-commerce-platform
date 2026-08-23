<?php

namespace Modules\CatalogDelivery\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\CatalogDelivery\Services\ProductImageService;
use Modules\PartnerHub\Models\Partner;
use Modules\CatalogDelivery\Models\Product;
use Modules\CatalogDelivery\Models\Category;
use Modules\CatalogDelivery\Http\Requests\StoreProductRequest;
use Modules\CatalogDelivery\Http\Requests\UpdateProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\CatalogDelivery\Models\ProductImage;
use Modules\TelemetryPipeline\Services\LowStockAlertService;

class PartnerInventoryController extends Controller
{
    protected function getPartner()
    {
        return Partner::where('user_id', auth()->id())->firstOrFail();
    }

    public function index(Request $request)
    {
        $partner = $this->getPartner();

        $search = mb_substr(trim((string) $request->query('search', '')), 0, 60);
        $stockFilter = $request->query('stock');

        $products = $partner->products()
            ->when($search !== '', fn ($q) => $q->where('products.name', 'like', '%' . $search . '%'))
            ->when($stockFilter === 'low', fn ($q) => $q->where('products.stock', '<', 5))
            ->when($stockFilter === 'in', fn ($q) => $q->where('products.stock', '>=', 5))
            ->paginate(12)
            ->withQueryString();

        return view('catalogdelivery::partner.inventory.index', compact('products'));
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
            try {
                $products = $partner->products()->whereIn('products.id', $ids)->get();
                foreach ($products as $product) {
                    // Detach from partner and delete product
                    $partner->products()->detach($product->id);
                    $product->delete();
                }
                return redirect()->back()->with('success', count($ids) . ' products removed from inventory.');
            } catch (\Illuminate\Database\QueryException $e) {
                return redirect()->back()->with('error', 'Some products could not be removed because they are referenced by orders, carts, or reviews.');
            }
        }

        return redirect()->back()->with('error', 'Invalid bulk action.');
    }

    public function create()
    {
        $categories = Category::all();
        return view('catalogdelivery::partner.inventory.create', compact('categories'));
    }

    public function store(StoreProductRequest $request)
    {
        $partner = $this->getPartner();
        $product = Product::create($request->validated());

        (new LowStockAlertService)->check($product);

        // Associate with partner
        $partner->products()->attach($product->id);

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
        return view('catalogdelivery::partner.inventory.edit', compact('product', 'categories'));
    }

    public function update(UpdateProductRequest $request, $id)
    {
        $partner = $this->getPartner();
        $product = $partner->products()->findOrFail($id);

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

        try {
            $partner->products()->detach($product->id);
            $product->delete();
        } catch (\Illuminate\Database\QueryException $e) {
            return redirect()->route('partner.inventory.index')
                ->with('error', 'Cannot delete this product: it is referenced by existing orders, carts, or reviews.');
        }

        return redirect()->route('partner.inventory.index')->with('success', 'Product removed');
    }
}
