<?php

namespace Modules\MarketplacePipeline\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\MarketplacePipeline\Models\Order;
use Modules\MarketplacePipeline\Models\Payment;
use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Support\Facades\Mail;
use Modules\MarketplacePipeline\Mail\PaymentSuccess;
use Illuminate\Support\Facades\Log;

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
     * 💡 SENIOR TIP: WEBHOOKS & PERSISTENCE
     */
    public function capture(Request $request)
    {
        // Initialize PayPal
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();

        // Capture payment from PayPal
        $response = $provider->capturePaymentOrder($request->token);

        // Verify successful payment
        if (isset($response['status']) && $response['status'] === 'COMPLETED') {

            // Find payment record
            $payment = Payment::where('transaction_id', $response['id'])->first();

            if (!$payment) {
                Log::error('PayPal Capture Error: Payment record not found', ['transaction_id' => $response['id']]);
                return redirect()->route('orders.index')->withErrors('Payment record not found.');
            }

            // Update payment status
            $payment->update(['status' => 'paid']);

            // Update order status
            $payment->order->update(['status' => 'paid']);

            // 📧 Trigger Payment Success Email
            Mail::to(auth()->user())->send(new PaymentSuccess($payment));

            return redirect()
                ->route('orders.index')
                ->with('status', 'Payment completed successfully');
        }

        Log::error('PayPal Capture Failure', ['response' => $response]);
        return redirect()->route('orders.index')->withErrors('Payment verification failed.');
    }
}
