<?php

namespace Modules\MarketplacePipeline\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\MarketplacePipeline\Models\Order;
use Modules\MarketplacePipeline\Models\OrderItem;
use Modules\MarketplacePipeline\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderConfirmed;
use App\Mail\OrderCancelled;
use Modules\CatalogDelivery\Models\Product;
use Modules\MarketplacePipeline\Models\Payment;

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

    public function store(Request $request)
    {
        $cart = Cart::with('items')
            ->where('user_id', auth()->id())
            ->first();

        // 🚨 Prevent empty checkout
        if (!$cart || $cart->items->isEmpty()) {
            return back()->withErrors('Cart is empty');
        }

        try {

            DB::beginTransaction();

            $total = 0;

            /**
             * Store locked product rows here so we can:
             * - avoid duplicate queries
             * - guarantee consistent pricing
             * - prevent race conditions
             */
            $products = [];

            // 🔒 Validate stock BEFORE creating order
            foreach ($cart->items as $item) {

                /*
                 * 💡 SENIOR TIP: RACE CONDITION VULNERABILITY
                 * TODO: Use ->lockForUpdate() on the product query to prevent overselling
                 * when multiple users buy the last item simultaneously.
                 */

                $product = Product::where('id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                // 🚨 Prevent null product crash
                if (!$product) {
                    throw new \Exception(
                        'Product not found'
                    );
                }

                // Save locked product instance
                $products[$item->product_id] = $product;

                // 🚨 Prevent overselling
                if ($product->stock < $item->quantity) {
                    throw new \Exception(
                        'Insufficient stock for ' . $product->name
                    );
                }

                // 💰 Calculate total using LOCKED product price
                $total += $product->price * $item->quantity;
            }

            // 🧾 Create order
            $order = Order::create([
                'user_id' => auth()->id(),
                'total_price' => $total,
                'status' => 'pending'
            ]);

            // 📦 Move cart items → order items
            foreach ($cart->items as $item) {

                /**
                 * Reuse locked product
                 * Prevents:
                 * - duplicate DB queries
                 * - inconsistent prices
                 * - stale product data
                 */
                $product = $products[$item->product_id];

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,

                    /**
                     * Use locked product price
                     * instead of stale eager-loaded relation
                     */
                    'price' => $product->price
                ]);

                // 🔻 Reduce stock
                $product->decrement('stock', $item->quantity);
            }

            // 🧹 Clear cart AFTER everything succeeds
            $cart->items()->delete();

            /**
             * Optional:
             * Delete cart itself after checkout
             *
             * Uncomment if desired:
             *
             * $cart->delete();
             */

            DB::commit();

            /**
             * 🎯 MISSION 5: THE MESSENGER (Email Confirmation)
             * TODO: Use the Mail facade to send the OrderConfirmed mailable.
             * Requirement: Pass the $order object to the mailable.
             * Hint: Mail::to(auth()->user())->send(new \App\Mail\OrderConfirmed($order));
             */

            // Load relations for email template
            $order->load('items.product');

            /**
             * Queue mail instead of blocking request
             * Requires queue setup
             */
            Mail::to(auth()->user())
                ->send(new OrderConfirmed($order));

            return redirect()
                ->route('orders.index')
                ->with('status', 'Order placed successfully');

        } catch (\Exception $e) {

            DB::rollBack();

            return back()->withErrors($e->getMessage());
        }
    }

    /**
     * TODO: BUSINESS LOGIC - ORDER CANCELLATION
     * Implement a method to cancel an order.
     * Requirement: If an order is cancelled, you MUST restore the stock to the products.
     * Hint: Use a DB::transaction(), loop through $order->items, and increment product stock.
     */
    public function cancel($id)
    {
        try {

            DB::beginTransaction();

            /**
             * Lock order row to prevent race conditions
             * where another process modifies the order
             * during cancellation.
             */
            $order = Order::where('user_id', auth()->id())
                ->lockForUpdate()
                ->with('items.product')
                ->findOrFail($id);

            // Prevent cancelling twice
            if ($order->status === 'cancelled') {

                DB::rollBack();

                return back()->withErrors(
                    'Order already cancelled'
                );
            }

            // Optional: only allow cancelling pending orders
            if ($order->status !== 'pending') {

                DB::rollBack();

                return back()->withErrors(
                    'Only pending orders can be cancelled'
                );
            }

            // Restore stock safely
            foreach ($order->items as $item) {

                /**
                 * Lock product row before incrementing
                 * to avoid concurrent stock corruption
                 */
                $product = Product::where('id', $item->product_id)
                    ->lockForUpdate()
                    ->first();

                // Skip missing products safely
                if (!$product) {
                    continue;
                }

                $product->increment(
                    'stock',
                    $item->quantity
                );
            }

            // Update status
            $order->update([
                'status' => 'cancelled'
            ]);

            // 🧹 Void any pending payments for this order
            Payment::where('order_id', $order->id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            DB::commit();

            // 📧 Trigger Cancellation Email
            Mail::to(auth()->user())->send(new OrderCancelled($order));

            return redirect()
                ->route('orders.index')
                ->with(
                    'status',
                    'Order cancelled successfully'
                );

        } catch (\Exception $e) {

            DB::rollBack();

            return back()->withErrors(
                $e->getMessage()
            );
        }
    }
}