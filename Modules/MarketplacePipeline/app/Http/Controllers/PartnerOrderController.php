<?php

namespace Modules\MarketplacePipeline\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\PartnerHub\Models\Partner;
use Modules\MarketplacePipeline\Models\Order;
use Modules\MarketplacePipeline\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Modules\MarketplacePipeline\Mail\OrderCompleted;
use Modules\MarketplacePipeline\Mail\OrderShipped;
use Modules\MarketplacePipeline\Mail\PaymentValidated;
use Modules\MarketplacePipeline\Mail\PaymentRejected;

class PartnerOrderController extends Controller
{
    /** @var list<string> */
    protected array $statuses = ['pending', 'pending_payment', 'paid', 'shipped', 'completed', 'cancelled'];

    protected function getPartner()
    {
        return Partner::where('user_id', auth()->id())->firstOrFail();
    }

    public function index(Request $request)
    {
        $partner = $this->getPartner();

        // Fetch orders that contain at least one item owned by this partner
        $orders = Order::whereHas('items.product.partners', function ($q) use ($partner) {
            $q->where('partners.id', $partner->id);
        })
            ->with(['items.product', 'payments.partner', 'user'])
            ->when($this->filteredStatus($request), fn ($q, $status) => $q->where('orders.status', $status))
            ->when($this->filteredSearch($request), fn ($q, $search) => $q->where('orders.id', $search))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('marketplacepipeline::partner.orders.index', [
            'orders' => $orders,
            'statuses' => $this->statuses,
        ]);
    }

    protected function filteredStatus(Request $request): ?string
    {
        $status = $request->query('status');

        return is_string($status) && in_array($status, $this->statuses, true) ? $status : null;
    }

    protected function filteredSearch(Request $request): ?int
    {
        $search = trim((string) $request->query('search', ''));

        return $search !== '' && ctype_digit($search) ? (int) $search : null;
    }

    public function show($id)
    {
        $partner = $this->getPartner();
        
        // Ensure the order actually contains items from this partner
        $order = $partner->orders()->where('orders.id', $id)->firstOrFail();
        $order->load(['items.product.partners', 'payments.partner']);

        // Only items supplied by this partner belong to the partner's fulfillment view
        $partnerItems = $order->items->filter(
            fn ($item) => $item->product->partners->contains('id', $partner->id)
        );
        $partnerSubtotal = $partnerItems->sum(fn ($item) => $item->price * $item->quantity);

        // Only payments belonging to this partner
        $partnerPayments = $order->payments->filter(
            fn ($p) => $p->partner_id === $partner->id
        );

        return view('marketplacepipeline::partner.orders.show', compact('order', 'partnerItems', 'partnerSubtotal', 'partnerPayments'));
    }

    /**
     * Mark order as completed (delivered). Restricted to orders that
     * actually contain this partner's items. Buyer is notified by email.
     */
    public function complete($id)
    {
        $partner = $this->getPartner();

        $order = $partner->orders()->where('orders.id', $id)->firstOrFail();

        if (!in_array($order->status, ['paid', 'shipped'])) {
            return back()->withErrors('Only paid or shipped orders can be marked as completed.');
        }

        DB::transaction(function () use ($order) {
            $order->update(['status' => 'completed']);
        });

        Mail::to($order->user)->queue(new OrderCompleted($order));

        (new \Modules\TelemetryPipeline\Services\TelemetryService)->log('partner.orders.completed', ['order_id' => $order->id]);

        return back()->with('status', 'Order marked as completed — the buyer has been notified and payment released to you.');
    }

    /**
     * Fulfillment transition: paid → shipped, restricted to orders that
     * actually contain this partner's items. Buyer is notified by email.
     */
    public function ship($id)
    {
        $partner = $this->getPartner();

        $order = $partner->orders()->where('orders.id', $id)->firstOrFail();

        if ($order->status !== 'paid') {
            return back()->withErrors('Only paid orders can be marked as shipped.');
        }

        \DB::transaction(function () use ($order) {
            $order->update(['status' => 'shipped']);
        });

        Mail::to($order->user)->queue(new OrderShipped($order->load('items.product')));

        (new \Modules\TelemetryPipeline\Services\TelemetryService)->log('partner.orders.shipped', ['order_id' => $order->id]);

        return back()->with('status', 'Order marked as shipped — the collector has been notified.');
    }

    /**
     * Validate bank transfer payment: approve or reject (order level).
     */
    public function validatePayment($id, Request $request)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'reason' => 'required_if:action,reject',
        ]);

        $partner = $this->getPartner();
        
        // Ensure the order actually contains items from this partner
        $order = $partner->orders()->where('orders.id', $id)->firstOrFail();
        $order->load(['items.product.partners', 'payments.partner']);

        if ($order->status !== 'pending_payment') {
            return back()->withErrors('Only pending payment orders can be validated.');
        }

        // Find this partner's payment(s)
        $partnerPayment = $order->payments->first(
            fn ($p) => $p->partner_id === $partner->id && $p->status === 'pending'
        );

        if (!$partnerPayment) {
            return back()->withErrors('No pending payment found for your account on this order.');
        }

        if ($request->action === 'approve') {
            DB::transaction(function () use ($partnerPayment, $order) {
                $partnerPayment->update([
                    'status' => 'paid',
                    'validated_at' => now(),
                    'validated_by' => auth()->id(),
                ]);

                // Check if ALL bank transfer payments for this order are paid
                $allPaid = $order->payments()
                    ->where('method', 'bank_transfer')
                    ->where('status', 'paid')
                    ->count() === $order->payments()->where('method', 'bank_transfer')->count();

                if ($allPaid) {
                    $order->update(['status' => 'paid']);
                }
            });

            Mail::to($order->user)->send(new PaymentValidated($order));

            return back()->with('status', 'Payment approved — order confirmed.');
        }

        // Reject action
        $reason = $request->reason;

        DB::transaction(function () use ($partnerPayment, $reason) {
            $partnerPayment->update([
                'status' => 'rejected',
                'validation_notes' => $reason,
            ]);
        });

        Mail::to($order->user)->send(new PaymentRejected($order, $reason));

        return back()->with('status', 'Payment rejected. User notified to re-upload proof.');
    }

    /**
     * Validate bank transfer payment for a specific vendor payment.
     */
    public function validatePaymentForPayment(Payment $payment, Request $request)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'reason' => 'required_if:action,reject',
        ]);

        $partner = $this->getPartner();
        
        // Ensure the payment belongs to this partner
        if ($payment->partner_id !== $partner->id) {
            abort(403);
        }

        // Only validate pending bank transfer payments
        if ($payment->method !== 'bank_transfer' || $payment->status !== 'pending') {
            return back()->withErrors('Only pending bank transfer payments can be validated.');
        }

        $order = $payment->order;

        if ($request->action === 'approve') {
            DB::transaction(function () use ($payment, $order) {
                $payment->update([
                    'status' => 'paid',
                    'validated_at' => now(),
                    'validated_by' => auth()->id(),
                ]);
                
                // Check if all vendor payments for this order are paid
                $allPaid = $order->payments()
                    ->where('method', 'bank_transfer')
                    ->where('status', 'paid')
                    ->count() === $order->payments()->where('method', 'bank_transfer')->count();
                
                if ($allPaid) {
                    $order->update(['status' => 'paid']);
                }
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
