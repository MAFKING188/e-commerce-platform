<?php

namespace Modules\MarketplacePipeline\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Modules\MarketplacePipeline\Mail\PaymentSuccess;
use Modules\MarketplacePipeline\Models\Order;
use Modules\MarketplacePipeline\Models\Payment;
use Srmklive\PayPal\Services\PayPal as PayPalClient;

class PaymentController extends Controller
{
    /**
     * 🎯 Mission 6: The Vault (PayPal Integration)
     */
    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id'
        ]);

        $order = Order::findOrFail($request->order_id);

        // 🚨 Prevent paying another user's order
        if ($order->user_id !== auth()->id()) {
            abort(403);
        }

        // 🚨 Prevent payment for invalid order states
        if ($order->status === 'paid') {
            return back()->withErrors('Order is already paid.');
        }

        if ($order->status === 'cancelled') {
            return back()->withErrors('Cannot pay for a cancelled order.');
        }

        if ($order->status !== 'pending') {
            return back()->withErrors('Order status must be pending to proceed with payment.');
        }

        // Initialize PayPal Provider
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();

        // Create PayPal Order
        $response = $provider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => route('paypal.capture'),
                "cancel_url" => route('paypal.cancel'),
            ],
            "purchase_units" => [
                [
                    "amount" => [
                        "currency_code" => "USD",
                        "value" => number_format($order->total_price, 2, '.', '')
                    ]
                ]
            ]
        ]);

        // 🚨 Handle PayPal API failure
        if (!isset($response['id'])) {
            Log::error('PayPal API Failure during order creation', [
                'order_id' => $order->id,
                'response' => $response
            ]);
            return back()->withErrors('PayPal API error: ' . ($response['message'] ?? 'Unable to initiate transaction.'));
        }

        // Save pending payment
        Payment::create([
            'order_id' => $order->id,
            'method' => 'paypal',
            'transaction_id' => $response['id'],
            'status' => 'pending',
            'amount' => $order->total_price
        ]);

        // Redirect user to approval URL
        foreach ($response['links'] as $link) {
            if ($link['rel'] === 'approve') {
                return redirect($link['href']);
            }
        }

        return back()->withErrors('Unable to redirect to PayPal.');
    }

    /**
     * Capture flow (PayPal return URL). The `token` query param carries the
     * PayPal order id, which is also our pending payments.transaction_id — so
     * every guard runs BEFORE any external API call.
     */
    public function capture(Request $request)
    {
        $payment = Payment::with('order.user')
            ->where('transaction_id', (string) $request->query('token'))
            ->first();

        if (! $payment) {
            Log::warning('PayPal Capture: no matching payment record', ['token' => (string) $request->query('token')]);
            return redirect()->route('orders.index')->withErrors('Payment record not found.');
        }

        // 🔒 Ownership gate: only the buyer may complete this flow.
        if ($payment->order->user_id !== auth()->id()) {
            abort(403);
        }

        // 🔁 Idempotency: PayPal can re-deliver the return URL; never double-settle.
        if ($payment->status === 'paid') {
            return redirect()
                ->route('orders.index')
                ->with('status', 'Payment was already completed.');
        }

        $provider = app(PayPalClient::class);
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();

        $response = $provider->capturePaymentOrder((string) $request->query('token'));

        if (! isset($response['status']) || $response['status'] !== 'COMPLETED') {
            Log::error('PayPal Capture Failure', ['payment_id' => $payment->id, 'response' => $response]);
            return redirect()->route('orders.index')->withErrors('Payment verification failed.');
        }

        DB::transaction(function () use ($payment) {
            $payment->update(['status' => 'paid']);
            $payment->order->update(['status' => 'paid']);
        });

        // 📧 Receipt goes to the order owner (not whoever clicked last).
        Mail::to($payment->order->user)->send(new PaymentSuccess($payment));

        return redirect()
            ->route('orders.index')
            ->with('status', 'Payment completed successfully');
    }
}