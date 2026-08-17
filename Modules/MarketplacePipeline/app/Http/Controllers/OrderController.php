<?php

namespace Modules\MarketplacePipeline\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\MarketplacePipeline\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderConfirmed;
use App\Mail\OrderCancelled;
use Modules\MarketplacePipeline\Services\CheckoutService;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with('items.product')
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('marketplacepipeline::orders.index', compact('orders'));
    }

    public function store(Request $request, CheckoutService $checkout)
    {
        try {
            $order = $checkout->checkout(auth()->user());

            $order->load('items.product');
            Mail::to(auth()->user())->send(new OrderConfirmed($order));

            return redirect()->route('orders.index')->with('status', 'Order placed successfully');
        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    /**
     * TODO: BUSINESS LOGIC - ORDER CANCELLATION
     * Implement a method to cancel an order.
     * Requirement: If an order is cancelled, you MUST restore the stock to the products.
     * Hint: Use a DB::transaction(), loop through $order->items, and increment product stock.
     */
    public function cancel($id, CheckoutService $checkout)
    {
        try {
            $checkout->cancel(auth()->user(), (int) $id);

            $order = Order::with('items.product')->findOrFail($id);
            Mail::to(auth()->user())->send(new OrderCancelled($order));

            return redirect()->route('orders.index')->with('status', 'Order cancelled successfully');
        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage());
        }
    }
}