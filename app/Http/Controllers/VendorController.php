<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\Product;
use App\Http\Requests\StoreVendorRequest;
use App\Http\Requests\UpdateVendorRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class VendorController extends Controller
{
    /**
     * Display the Partner Ecosystem.
     */
    public function index()
    {
        $vendors = Vendor::withCount('products')->latest()->get();
        return view('admin.vendors.index', compact('vendors'));
    }

    public function create()
    {
        return view('admin.vendors.create');
    }

    /**
     * Securely establish a new partner relationship.
     */
    public function store(StoreVendorRequest $request)
    {
        try {
            Vendor::create($request->validated());
            return redirect()->route('vendors.index')->with('success', 'Partner Vendor established successfully');
        } catch (\Exception $e) {
            Log::error("Vendor Creation Error: " . $e->getMessage());
            return back()->withInput()->with('error', 'Unable to initialize partner registry.');
        }
    }

    public function edit($id)
    {
        $vendor = Vendor::findOrFail($id);
        return view('admin.vendors.edit', compact('vendor'));
    }

    /**
     * Refine partner metadata with strict validation.
     */
    public function update(UpdateVendorRequest $request, $id)
    {
        try {
            $vendor = Vendor::findOrFail($id);
            $vendor->update($request->validated());
            return redirect()->route('vendors.index')->with('success', 'Vendor profiles updated');
        } catch (\Exception $e) {
            Log::error("Vendor Update Error: " . $e->getMessage());
            return back()->withInput()->with('error', 'Unable to refine partner metadata.');
        }
    }

    /**
     * Terminate partnership and clean up mappings.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $vendor = Vendor::findOrFail($id);
            $vendor->products()->detach(); // Clean pivot mappings
            $vendor->delete();
            
            DB::commit();
            return redirect()->route('vendors.index')->with('success', 'Vendor relationship terminated and mappings cleared');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Vendor Deletion Error: " . $e->getMessage());
            return back()->with('error', 'Structural integrity error during partnership termination.');
        }
    }

    /**
     * Display vendor inventory mapping panel.
     */
    public function show($id)
    {
        $vendor = Vendor::with('products.images')->findOrFail($id);
        
        // Optimize discovery: products not yet mapped to this vendor
        $availableProducts = Product::whereDoesntHave('vendors', function($q) use ($id) {
            $q->where('vendor_id', $id);
        })->get();

        return view('admin.vendors.show', compact('vendor', 'availableProducts'));
    }

    /**
     * Securely map a product to a vendor inventory.
     */
    public function addProduct(Request $request, $id)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $vendor = Vendor::findOrFail($id);
        
        // Prevent duplicate mapping
        if ($vendor->products()->where('product_id', $request->product_id)->exists()) {
            return back()->with('error', 'This piece is already mapped to this vendor.');
        }

        $vendor->products()->attach($request->product_id);

        return redirect()->back()->with('success', 'Product mapped to vendor inventory');
    }

    /**
     * Sever product-vendor mapping.
     */
    public function removeProduct($id, $productId)
    {
        $vendor = Vendor::findOrFail($id);
        $vendor->products()->detach($productId);

        return redirect()->back()->with('success', 'Product removed from vendor assignment');
    }
}
