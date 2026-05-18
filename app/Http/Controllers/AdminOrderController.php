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
        $order = Order::findOrFail($id);
        if ($order->status === 'paid') {
            $order->update(['status' => 'completed']);
            return back()->with('status', 'Order marked as completed/shipped.');
        }
        return back()->withErrors('Only paid orders can be marked as completed.');
    }
}
