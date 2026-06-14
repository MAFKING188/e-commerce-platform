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
        
        // Calculate total revenue and items sold specifically for this partner's items
        $allOrderItems = \App\Models\OrderItem::whereHas('product.partners', function($q) use ($partner) {
            $q->where('partners.id', $partner->id);
        })->get();

        $totalRevenue = $allOrderItems->sum(function($item) {
            return $item->price * $item->quantity;
        });

        $itemsSold = $allOrderItems->sum('quantity');

        $pendingPayout = $partner->payouts()->where('status', 'pending')->sum('amount');
        
        // Recent orders containing this partner's products
        $recentOrders = Order::whereHas('items.product.partners', function($q) use ($partner) {
            $q->where('partners.id', $partner->id);
        })->latest()->take(5)->get();

        // 📈 Time-Series Analytics: Daily Sales (Last 30 Days)
        $salesData = \App\Models\OrderItem::whereHas('product.partners', function($q) use ($partner) {
                $q->where('partners.id', $partner->id);
            })
            ->whereHas('order', function($q) {
                $q->where('status', 'completed')
                  ->where('created_at', '>=', now()->subDays(30));
            })
            ->selectRaw('DATE(created_at) as date, SUM(price * quantity) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $chartData = [
            'labels' => $salesData->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('M d')),
            'values' => $salesData->pluck('total')
        ];

        return view('partner.dashboard', compact('partner', 'inventoryCount', 'recentOrders', 'totalRevenue', 'itemsSold', 'pendingPayout', 'chartData'));
    }
}
