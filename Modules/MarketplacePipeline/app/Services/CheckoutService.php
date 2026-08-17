<?php

namespace Modules\MarketplacePipeline\Services;

use Modules\IdentityAccess\Models\User;
use Modules\MarketplacePipeline\Models\Order;
use Modules\MarketplacePipeline\Models\OrderItem;
use Modules\MarketplacePipeline\Models\Cart;
use Modules\MarketplacePipeline\Models\Payment;
use Modules\CatalogDelivery\Models\Product;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function checkout(User $user): Order
    {
        $cart = Cart::with('items')->where('user_id', $user->id)->first();

        if (!$cart || $cart->items->isEmpty()) {
            throw new \RuntimeException('Cart is empty');
        }

        return DB::transaction(function () use ($cart, $user) {
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
                'status' => 'pending',
            ]);

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