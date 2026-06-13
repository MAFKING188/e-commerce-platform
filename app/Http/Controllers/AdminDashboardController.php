<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    /**
     * Display the Administrative Command Center.
     * Centralizes statistics and recent activity for platform management.
     */
    public function index()
    {
        // 📊 Aggregate Statistics
        $stats = [
            'revenue' => Order::where('status', 'completed')->sum('total_price'),
            'active_orders' => Order::where('status', 'pending')->count(),
            'catalog_size' => Product::count(),
            'total_members' => User::count(),
            'low_stock_count' => Product::where('stock', '<', 5)->count(),
            'pending_reviews' => \App\Models\Review::where('status', 'pending')->count(),
            'pending_users' => User::where('status', 'pending')->count(),
        ];

        // 🕒 Pulse: Recent Acquisitions
        $recentOrders = Order::with('user')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentOrders'));
    }
}
