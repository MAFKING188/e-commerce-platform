<?php

namespace Modules\MarketplacePipeline\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\CatalogDelivery\Models\Product;
use Modules\IdentityAccess\Models\User;
use Modules\MarketplacePipeline\Mail\OrderShipped;
use Modules\MarketplacePipeline\Models\Order;
use Modules\PartnerHub\Models\Partner;
use Tests\TestCase;

class PartnerFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private function makePaidOrderForPartner(User $partnerUser): Order
    {
        $partner = Partner::create(['user_id' => $partnerUser->id, 'name' => 'Ship Artisan']);
        $buyer = User::factory()->create(['email_verified_at' => now()]);
        $product = Product::factory()->create();
        $product->partners()->attach($partner->id);

        $order = Order::create(['user_id' => $buyer->id, 'total_price' => 80, 'status' => 'paid']);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 80]);

        return $order;
    }

    public function test_partner_can_ship_own_paid_order_and_buyer_is_notified(): void
    {
        Mail::fake();
        $partnerUser = User::factory()->create(['role' => 'partner', 'status' => 'active']);
        $order = $this->makePaidOrderForPartner($partnerUser);

        $this->actingAs($partnerUser)
            ->patch(route('partner.orders.ship', $order->id))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'shipped']);
        Mail::assertQueued(OrderShipped::class, fn ($m) => $m->order->id === $order->id);
    }

    public function test_only_paid_orders_can_be_shipped(): void
    {
        Mail::fake();
        $partnerUser = User::factory()->create(['role' => 'partner', 'status' => 'active']);
        $order = $this->makePaidOrderForPartner($partnerUser);
        $order->update(['status' => 'pending']);

        $this->actingAs($partnerUser)
            ->patch(route('partner.orders.ship', $order->id))
            ->assertRedirect()
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
        Mail::assertNothingQueued(OrderShipped::class);
    }

    public function test_partner_cannot_ship_an_others_partners_order(): void
    {
        Mail::fake();
        $otherPartner = User::factory()->create(['role' => 'partner', 'status' => 'active']);
        $order = $this->makePaidOrderForPartner($otherPartner);

        $intruder = User::factory()->create(['role' => 'partner', 'status' => 'active']);
        Partner::create(['user_id' => $intruder->id, 'name' => 'Intruder Atelier']);

        $this->actingAs($intruder)
            ->patch(route('partner.orders.ship', $order->id))
            ->assertNotFound();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
        Mail::assertNothingQueued(OrderShipped::class);
    }

    public function test_admin_can_complete_a_shipped_order(): void
    {
        $partnerUser = User::factory()->create(['role' => 'partner', 'status' => 'active']);
        $order = $this->makePaidOrderForPartner($partnerUser);
        $order->update(['status' => 'shipped']);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->post(route('admin.orders.complete', $order->id))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
        $this->assertDatabaseHas('payouts', ['order_id' => $order->id]);
    }
}