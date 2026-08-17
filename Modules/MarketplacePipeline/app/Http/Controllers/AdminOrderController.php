<?php

namespace Modules\MarketplacePipeline\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\MarketplacePipeline\Models\Order;
use Modules\MarketplacePipeline\Services\PayoutService;

class AdminOrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('user')->latest()->paginate(15);
        return view('marketplacepipeline::admin.orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with(['user', 'items.product'])->findOrFail($id);
        return view('marketplacepipeline::admin.orders.show', compact('order'));
    }

    public function complete($id, PayoutService $payouts)
    {
        $order = Order::with('items.product.partners')->findOrFail($id);

        if ($order->status === 'paid') {
            \DB::transaction(function () use ($order, $payouts) {
                $order->update(['status' => 'completed']);
                $payouts->settle($order);
            });

            return back()->with('status', 'Order marked as completed and payouts generated.');
        }

        return back()->withErrors('Only paid orders can be marked as completed.');
    }
}
