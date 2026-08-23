<?php

namespace Modules\MarketplacePipeline\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\MarketplacePipeline\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Modules\MarketplacePipeline\Mail\OrderConfirmed;
use Modules\MarketplacePipeline\Mail\OrderCancelled;
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
        $delivery = $request->validate([
            'recipient_name' => 'required|string|max:120',
            'recipient_phone' => 'required|string|max:40',
            'shipping_line1' => 'required|string|max:255',
            'shipping_line2' => 'nullable|string|max:255',
            'shipping_city' => 'required|string|max:120',
            'shipping_state' => 'nullable|string|max:120',
            'shipping_zip' => 'nullable|string|max:20',
            'shipping_country' => 'required|string|max:120',
            'delivery_notes' => 'nullable|string|max:1000',
        ]);

        $user = auth()->user();
        $session = $request->session();

        if (! \Modules\IdentityAccess\Services\StepUpService::isVerified($session)) {
            $code = trim((string) $request->input('code'));

            if ($code === '' || ! \Modules\IdentityAccess\Services\OtpService::check($user, $code)) {
                \Modules\IdentityAccess\Services\StepUpService::begin($user, $session);
                return back()->withErrors(['code' => 'Enter the verification code sent to your email. A new code has been sent.']);
            }

            \Modules\IdentityAccess\Services\StepUpService::complete($session);
        }

        try {
            $order = $checkout->checkout($user, $delivery);

            $order->load('items.product');
            Mail::to($user)->send(new OrderConfirmed($order));

            return redirect()->route('orders.index')->with('status', 'Order placed successfully');
        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage());
        }
    }

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