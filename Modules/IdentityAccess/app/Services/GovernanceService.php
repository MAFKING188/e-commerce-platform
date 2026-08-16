<?php

namespace Modules\IdentityAccess\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Modules\IdentityAccess\Models\User;

class GovernanceService
{
    public function getDashboardMetrics(): array
    {
        // 📊 Aggregate Statistics
        $stats = [
            'revenue' => Order::where('status', 'completed')->sum('total_price'),
            'active_orders' => Order::where('status', 'pending')->count(),
            'catalog_size' => Product::count(),
            'total_members' => User::count(),
            'low_stock_count' => Product::where('stock', '<', 5)->count(),
            'pending_reviews' => Review::where('status', 'pending')->count(),
            'pending_users' => User::where('status', 'pending')->count(),
        ];

        // 🕒 Pulse: Recent Acquisitions
        $recentOrders = Order::with('user')
            ->latest()
            ->take(5)
            ->get();

        return compact('stats', 'recentOrders');
    }
}
