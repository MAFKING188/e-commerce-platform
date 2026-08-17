<?php

namespace Modules\TelemetryPipeline\Services;

use Modules\PartnerHub\Models\Partner;
use Modules\MarketplacePipeline\Models\Order;
use Modules\MarketplacePipeline\Models\OrderItem;
use Carbon\Carbon;

class AnalyticsService
{
    public function partnerDashboard(Partner $partner): array
    {
        $inventoryCount = $partner->products()->count();

        $allOrderItems = OrderItem::whereHas('product.partners', function ($q) use ($partner) {
            $q->where('partners.id', $partner->id);
        })->get();

        $totalRevenue = $allOrderItems->sum(fn ($item) => $item->price * $item->quantity);
        $itemsSold = $allOrderItems->sum('quantity');

        $pendingPayout = $partner->payouts()->where('status', 'pending')->sum('amount');

        $recentOrders = Order::whereHas('items.product.partners', function ($q) use ($partner) {
            $q->where('partners.id', $partner->id);
        })->latest()->take(5)->get();

        $salesData = OrderItem::whereHas('product.partners', function ($q) use ($partner) {
                $q->where('partners.id', $partner->id);
            })
            ->whereHas('order', function ($q) {
                $q->where('status', 'completed')
                  ->where('created_at', '>=', now()->subDays(30));
            })
            ->selectRaw('DATE(created_at) as date, SUM(price * quantity) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $chartData = [
            'labels' => $salesData->pluck('date')->map(fn ($d) => Carbon::parse($d)->format('M d')),
            'values' => $salesData->pluck('total'),
        ];

        return compact('inventoryCount', 'totalRevenue', 'itemsSold', 'pendingPayout', 'recentOrders', 'chartData');
    }
}