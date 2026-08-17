<?php

namespace Modules\MarketplacePipeline\Tests\Feature;

use Modules\IdentityAccess\Models\User;
use Modules\CatalogDelivery\Models\Product;
use Modules\MarketplacePipeline\Models\Cart;
use Modules\MarketplacePipeline\Models\CartItem;
use Modules\MarketplacePipeline\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\MarketplacePipeline\Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_creates_order_decrements_stock_and_clears_cart(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $product = Product::factory()->create(['price' => 50, 'stock' => 3]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($user)->post('/orders/store')->assertRedirect(route('orders.index'));

        $this->assertSame(1, Order::count());
        $this->assertSame(100.0, (float) Order::first()->total_price);
        $this->assertSame(1, $product->fresh()->stock);
        $this->assertSame(0, CartItem::count());
    }

    public function test_checkout_rejects_insufficient_stock(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $product = Product::factory()->create(['price' => 10, 'stock' => 1]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 2]);

        $response = $this->actingAs($user)->post('/orders/store');

        $response->assertSessionHasErrors();
        $this->assertSame(0, Order::count());
        $this->assertSame(1, $product->fresh()->stock);
    }
}