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

    /**
     * Validate bank transfer payment: approve or reject.
     */
    public function validatePayment($id, Request $request)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'reason' => 'required_if:action,reject',
        ]);

        $order = Order::with('payment')->findOrFail($id);

        // 🚨 Ownership gate: only admin may validate
        if (! auth()->user()->is('admin')) {
            abort(403);
        }

        $payment = $order->payment;

        if ($request->action === 'approve') {
            DB::transaction(function () use ($payment, $order) {
                $payment->update([
                    'status' => 'paid',
                    'validated_at' => now(),
                    'validated_by' => auth()->id(),
                ]);
                $order->update(['status' => 'paid']);
            });

            // Send email to user
            Mail::to($order->user)->send(new PaymentValidated($order));

            return back()->with('status', 'Payment approved — order confirmed.');
        }

        // Reject action
        $reason = $request->reason;

        DB::transaction(function () use ($payment, $order, $reason) {
            $payment->update([
                'status' => 'rejected',
                'validation_notes' => $reason,
            ]);
            // Order status stays pending_payment
        });

        // Send email to user
        Mail::to($order->user)->send(new PaymentRejected($order, $reason));

        return back()->with('status', 'Payment rejected. User notified to re-upload proof.');
    }
}
