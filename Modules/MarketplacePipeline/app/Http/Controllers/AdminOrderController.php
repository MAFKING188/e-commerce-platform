<?php

namespace Modules\MarketplacePipeline\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\MarketplacePipeline\Models\Order;
use Modules\MarketplacePipeline\Services\PayoutService;
use Modules\TelemetryPipeline\Services\TelemetryService;

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

        if (in_array($order->status, ['paid', 'shipped'], true)) {
            \DB::transaction(function () use ($order, $payouts) {
                $order->update(['status' => 'completed']);
                $payouts->settle($order);
                (new TelemetryService)->log('admin.orders.complete', ['order_id' => $order->id]);
            });

            return back()->with('status', 'Order marked as completed and payouts generated.');
        }

        return back()->withErrors('Only paid orders can be marked as completed.');
    }
}
