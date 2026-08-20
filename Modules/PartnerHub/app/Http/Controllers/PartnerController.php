<?php

namespace Modules\PartnerHub\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\PartnerHub\Models\Partner;
use Modules\CatalogDelivery\Models\Product;
use Modules\IdentityAccess\Models\User;
use Modules\PartnerHub\Http\Requests\StorePartnerRequest;
use Modules\PartnerHub\Http\Requests\UpdatePartnerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PartnerController extends Controller
{
    /**
     * Display the Partner Ecosystem.
     */
    public function index()
    {
        $partners = Partner::withCount('products')->latest()->get();
        return view('partnerhub::admin.partners.index', compact('partners'));
    }

    public function create()
    {
        $users = User::all();
        return view('partnerhub::admin.partners.create', compact('users'));
    }

    /**
     * Securely establish a new partner relationship.
     */
    public function store(StorePartnerRequest $request)
    {
        try {
            Log::info('Partner Store Request Data: ', $request->validated());
            $data = $request->validated();
            Partner::create($data);
            $user = User::findOrFail($data['user_id']);
            $user->forceFill(['role' => 'partner', 'status' => 'active'])->save();
            return redirect()->route('admin.partners.index')->with('success', 'Partner established successfully');
        } catch (\Exception $e) {
            Log::error("Partner Creation Error: " . $e->getMessage());
            return back()->withInput()->with('error', 'Unable to initialize partner registry.');
        }
    }

    public function edit($id)
    {
        $partner = Partner::findOrFail($id);
        return view('partnerhub::admin.partners.edit', compact('partner'));
    }

    /**
     * Refine partner metadata with strict validation.
     */
    public function update(UpdatePartnerRequest $request, $id)
    {
        try {
            $partner = Partner::findOrFail($id);
            $partner->update($request->validated());
            return redirect()->route('admin.partners.index')->with('success', 'Partner profiles updated');
        } catch (\Exception $e) {
            Log::error("Partner Update Error: " . $e->getMessage());
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
            $partner = Partner::findOrFail($id);
            $partner->products()->detach(); // Clean pivot mappings
            $partner->delete();
            
            DB::commit();
            return redirect()->route('admin.partners.index')->with('success', 'Partner relationship terminated and mappings cleared');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Partner Deletion Error: " . $e->getMessage());
            return back()->with('error', 'Structural integrity error during partnership termination.');
        }
    }

    /**
     * Display partner inventory mapping panel.
     */
    public function show($id)
    {
        $partner = Partner::with('products.images')->findOrFail($id);
        
        // Optimize discovery: products not yet mapped to this partner
        $availableProducts = Product::whereDoesntHave('partners', function($q) use ($id) {
            $q->where('partner_id', $id);
        })->get();

        return view('partnerhub::admin.partners.show', compact('partner', 'availableProducts'));
    }

    /**
     * Securely map a product to a partner inventory.
     */
    public function addProduct(Request $request, $id)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id'
        ]);

        $partner = Partner::findOrFail($id);
        
        // Prevent duplicate mapping
        if ($partner->products()->where('product_id', $request->product_id)->exists()) {
            return back()->with('error', 'This piece is already mapped to this partner.');
        }

        $partner->products()->attach($request->product_id);

        return redirect()->back()->with('success', 'Product mapped to partner inventory');
    }

    /**
     * Sever product-partner mapping.
     */
    public function removeProduct($id, $productId)
    {
        $partner = Partner::findOrFail($id);
        $partner->products()->detach($productId);

        return redirect()->back()->with('success', 'Product removed from partner assignment');
    }
}
