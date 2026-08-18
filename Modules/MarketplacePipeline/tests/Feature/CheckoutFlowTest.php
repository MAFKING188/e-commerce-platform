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

    private function deliveryPayload(): array
    {
        return [
            'recipient_name' => 'Jane Doe',
            'recipient_phone' => '+1 555 0100',
            'shipping_line1' => 'Luxury Street 12',
            'shipping_city' => 'Milan',
            'shipping_country' => 'Italy',
        ];
    }

    private function makeCartWith(User $user, int $quantity): void
    {
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $this->product->id, 'quantity' => $quantity]);
    }

    public function test_checkout_creates_order_decrements_stock_and_clears_cart(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->product = Product::factory()->create(['price' => 50, 'stock' => 3]);
        $this->makeCartWith($user, 2);

        $this->actingAs($user)
            ->post('/orders/store', $this->deliveryPayload())
            ->assertRedirect(route('orders.index'));

        $this->assertSame(1, Order::count());
        $this->assertSame(100.0, (float) Order::first()->total_price);
        $this->assertSame(1, $this->product->fresh()->stock);
        $this->assertSame(0, CartItem::count());
    }

    public function test_checkout_persists_delivery_details(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->product = Product::factory()->create(['price' => 20, 'stock' => 5]);
        $this->makeCartWith($user, 1);

        $this->actingAs($user)
            ->post('/orders/store', $this->deliveryPayload())
            ->assertRedirect(route('orders.index'));

        $order = Order::first();
        $this->assertSame('Jane Doe', $order->recipient_name);
        $this->assertSame('+1 555 0100', $order->recipient_phone);
        $this->assertSame('Luxury Street 12', $order->shipping_line1);
        $this->assertSame('Milan', $order->shipping_city);
        $this->assertSame('Italy', $order->shipping_country);
        $this->assertSame('Luxury Street 12, Milan, Italy', $order->shipping_address);
    }

    public function test_checkout_requires_delivery_fields(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->product = Product::factory()->create(['price' => 20, 'stock' => 5]);
        $this->makeCartWith($user, 1);

        $response = $this->actingAs($user)->post('/orders/store', [
            'recipient_name' => 'Jane Doe',
            'shipping_city' => 'Milan',
        ]);

        $response->assertSessionHasErrors(['recipient_phone', 'shipping_line1', 'shipping_country']);
        $this->assertSame(0, Order::count());
    }

    public function test_checkout_rejects_insufficient_stock(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->product = Product::factory()->create(['price' => 10, 'stock' => 1]);
        $this->makeCartWith($user, 2);

        $response = $this->actingAs($user)->post('/orders/store', $this->deliveryPayload());

        $response->assertSessionHasErrors();
        $this->assertSame(0, Order::count());
        $this->assertSame(1, $this->product->fresh()->stock);
    }
}