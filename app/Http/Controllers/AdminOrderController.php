<?php

namespace App\Http\Controllers;

use Modules\MarketplacePipeline\Models\Order;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('user')->latest()->paginate(15);
        return view('admin.orders.index', compact('orders'));
    }

    public function show($id)
    {
        $order = Order::with(['user', 'items.product'])->findOrFail($id);
        return view('admin.orders.show', compact('order'));
    }

    public function complete($id)
    {
        $order = Order::with('items.product.partners')->findOrFail($id);
        
        if ($order->status === 'paid') {
            \DB::transaction(function () use ($order) {
                $order->update(['status' => 'completed']);

                // Calculate payouts for each partner involved in this order.
                // A product's line value is split equally among its partners.
                $partnerItems = [];
                foreach ($order->items as $item) {
                    $partners = $item->product->partners;
                    if ($partners->isEmpty()) {
                        continue;
                    }
                    $lineValue = $item->price * $item->quantity;
                    $share = $lineValue / $partners->count();
                    foreach ($partners as $partner) {
                        $partnerItems[$partner->id] = ($partnerItems[$partner->id] ?? 0) + $share;
                    }
                }

                foreach ($partnerItems as $partnerId => $grossAmount) {
                    // Platform takes commission_rate (default 10%) commission
                    $netAmount = $grossAmount * (1 - config('shop.commission_rate'));

                    \Modules\MarketplacePipeline\Models\Payout::updateOrCreate(
                        ['order_id' => $order->id, 'partner_id' => $partnerId],
                        ['amount' => $netAmount, 'status' => 'pending']
                    );
                }
            });

            return back()->with('status', 'Order marked as completed and payouts generated.');
        }
        
        return back()->withErrors('Only paid orders can be marked as completed.');
    }
}
