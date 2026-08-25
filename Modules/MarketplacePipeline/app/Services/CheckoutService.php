<?php

namespace Modules\MarketplacePipeline\Services;

use Modules\IdentityAccess\Models\User;
use Modules\MarketplacePipeline\Models\Order;
use Modules\MarketplacePipeline\Models\OrderItem;
use Modules\MarketplacePipeline\Models\Cart;
use Modules\MarketplacePipeline\Models\Payment;
use Modules\CatalogDelivery\Models\Product;
use Modules\PartnerHub\Models\Partner;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function checkout(User $user, array $delivery = [], $paymentMethod = 'paypal'): Order
    {
        $cart = Cart::with('items')->where('user_id', $user->id)->first();

        if (!$cart || $cart->items->isEmpty()) {
            throw new \RuntimeException('Cart is empty');
        }

        // Determine order status based on payment method
        $orderStatus = $paymentMethod === 'bank_transfer' ? 'pending_payment' : 'pending';

        return DB::transaction(function () use ($cart, $user, $delivery, $paymentMethod, $orderStatus) {
            $total = 0;
            $products = [];

            foreach ($cart->items as $item) {
                $product = Product::where('id', $item->product_id)->lockForUpdate()->first();

                if (!$product) {
                    throw new \Exception('Product not found');
                }

                $products[$item->product_id] = $product;

                if ($product->stock < $item->quantity) {
                    throw new \Exception('Insufficient stock for ' . $product->name);
                }

                $total += $product->price * $item->quantity;
            }

            $order = Order::create([
                'user_id' => $user->id,
                'total_price' => $total,
                'status' => $orderStatus,
            ] + array_intersect_key($delivery, array_flip([
                'recipient_name',
                'recipient_phone',
                'shipping_line1',
                'shipping_line2',
                'shipping_city',
                'shipping_state',
                'shipping_zip',
                'shipping_country',
                'delivery_notes',
            ])));

            foreach ($cart->items as $item) {
                $product = $products[$item->product_id];

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $product->price,
                ]);

                $product->decrement('stock', $item->quantity);
            }

            $cart->items()->delete();

            // Create payment record(s) based on method
            if ($paymentMethod === 'bank_transfer') {
                // Group items by vendor and create separate payment per vendor
                $itemsByVendor = $cart->items->groupBy(function($item) {
                    return $item->product->partners->first()->id ?? 'unknown';
                });

                foreach ($itemsByVendor as $vendorId => $vendorItems) {
                    $vendor = Partner::find($vendorId);
                    $vendorTotal = $vendorItems->sum(fn($item) => $item->product->price * $item->quantity);

                    // Check if vendor has bank details
                    $bankDetail = $vendor ? $vendor->bankDetails : null;
                    if (!$bankDetail || !$bankDetail->is_active) {
                        throw new \Exception("Vendor {$vendor->name} has not configured bank details. Please use PayPal or contact the vendor.");
                    }

                    Payment::create([
                        'order_id' => $order->id,
                        'partner_id' => $vendorId,
                        'method' => 'bank_transfer',
                        'status' => 'pending',
                        'amount' => $vendorTotal,
                    ]);
                }
            }

            return $order;
        });
    }

    public function cancel(User $user, int $orderId): void
    {
        DB::transaction(function () use ($user, $orderId) {
            $order = Order::where('user_id', $user->id)
                ->lockForUpdate()
                ->with('items.product')
                ->findOrFail($orderId);

            if ($order->status === 'cancelled') {
                throw new \RuntimeException('Order already cancelled');
            }

            if ($order->status !== 'pending') {
                throw new \RuntimeException('Only pending orders can be cancelled');
            }

            foreach ($order->items as $item) {
                $product = Product::where('id', $item->product_id)->lockForUpdate()->first();

                if (!$product) {
                    continue;
                }

                $product->increment('stock', $item->quantity);
            }

            $order->update(['status' => 'cancelled']);

            Payment::where('order_id', $order->id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);
        });
    }
}