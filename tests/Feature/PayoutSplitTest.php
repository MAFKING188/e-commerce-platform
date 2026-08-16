<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Partner;
use App\Models\Payout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutSplitTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_partner_product_splits_payout_equally(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $buyer = User::factory()->create(['status' => 'active']);
        $p1 = Partner::create(['name' => 'Atelier A', 'user_id' => User::factory()->create(['status' => 'active'])->id]);
        $p2 = Partner::create(['name' => 'Atelier B', 'user_id' => User::factory()->create(['status' => 'active'])->id]);

        $product = Product::factory()->create(['price' => 100, 'stock' => 5]);
        $product->partners()->attach([$p1->id, $p2->id]);

        $order = Order::create(['user_id' => $buyer->id, 'total_price' => 100, 'status' => 'paid']);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 100]);

        $this->actingAs($admin)->post("/admin/orders/{$order->id}/complete")->assertRedirect();

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame(2, Payout::where('order_id', $order->id)->count());

        foreach (Payout::where('order_id', $order->id)->get() as $payout) {
            $this->assertEqualsWithDelta(45.0, (float) $payout->amount, 0.001);
        }
    }
}