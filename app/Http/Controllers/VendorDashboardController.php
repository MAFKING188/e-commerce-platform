<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Models\Order;
use Illuminate\Http\Request;

class VendorDashboardController extends Controller
{
    /**
     * Display the vendor-specific dashboard.
     */
    public function index()
    {
        $vendor = Vendor::where('user_id', auth()->id())->firstOrFail();
        
        // 📊 Metrics
        $inventoryCount = $vendor->products()->count();
        
        // Recent orders containing this vendor's products
        $recentOrders = Order::whereHas('orderItems.product.vendors', function($q) use ($vendor) {
            $q->where('vendors.id', $vendor->id);
        })->latest()->take(5)->get();

        return view('vendor.dashboard', compact('vendor', 'inventoryCount', 'recentOrders'));
    }
}
