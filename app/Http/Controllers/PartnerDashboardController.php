<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\Order;
use Illuminate\Http\Request;

class PartnerDashboardController extends Controller
{
    /**
     * Display the partner-specific dashboard.
     */
    public function index()
    {
        $partner = Partner::where('user_id', auth()->id())->firstOrFail();
        
        // 📊 Metrics
        $inventoryCount = $partner->products()->count();
        
        // Recent orders containing this partner's products
        $recentOrders = Order::whereHas('items.product.partners', function($q) use ($partner) {
            $q->where('partners.id', $partner->id);
        })->latest()->take(5)->get();

        return view('partner.dashboard', compact('partner', 'inventoryCount', 'recentOrders'));
    }
}
