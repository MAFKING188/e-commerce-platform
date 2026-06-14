<?php

namespace App\Http\Controllers;

use App\Models\Order;
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

                // Calculate payouts for each partner involved in this order
                $partnerItems = [];
                foreach ($order->items as $item) {
                    foreach ($item->product->partners as $partner) {
                        if (!isset($partnerItems[$partner->id])) {
                            $partnerItems[$partner->id] = 0;
                        }
                        $partnerItems[$partner->id] += ($item->price * $item->quantity);
                    }
                }

                foreach ($partnerItems as $partnerId => $grossAmount) {
                    // Platform takes 10% commission
                    $netAmount = $grossAmount * 0.90;

                    \App\Models\Payout::firstOrCreate([
                        'order_id' => $order->id,
                        'partner_id' => $partnerId
                    ], [
                        'amount' => $netAmount,
                        'status' => 'pending'
                    ]);
                }
            });

            return back()->with('status', 'Order marked as completed and payouts generated.');
        }
        
        return back()->withErrors('Only paid orders can be marked as completed.');
    }
}
