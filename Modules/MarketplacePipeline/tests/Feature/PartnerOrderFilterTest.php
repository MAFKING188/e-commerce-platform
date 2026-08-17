<?php

namespace Modules\MarketplacePipeline\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CatalogDelivery\Models\Product;
use Modules\IdentityAccess\Models\User;
use Modules\MarketplacePipeline\Models\Order;
use Modules\MarketplacePipeline\Models\OrderItem;
use Modules\MarketplacePipeline\Tests\TestCase;
use Modules\PartnerHub\Models\Partner;

class PartnerOrderFilterTest extends TestCase
{
    use RefreshDatabase;

    private array $partner;

    private function setUpPartner(): void
    {
        $partnerUser = User::factory()->create(['role' => 'partner', 'status' => 'active']);
        $this->partner = [
            'user' => $partnerUser,
            'partner' => Partner::create(['name' => 'Atelier', 'user_id' => $partnerUser->id]),
        ];
    }

    private function orderFor(string $status): Order
    {
        $buyer = User::factory()->create(['status' => 'active']);

        $product = Product::factory()->create(['price' => 50, 'stock' => 5]);
        $product->partners()->attach($this->partner['partner']->id);

        $order = Order::create(['user_id' => $buyer->id, 'total_price' => 50, 'status' => $status]);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 50]);

        return $order;
    }

    public function test_status_filter_shows_only_matching_orders(): void
    {
        $this->setUpPartner();
        $paidOrder = $this->orderFor('paid');
        $pendingOrder = $this->orderFor('pending');

        $response = $this->actingAs($this->partner['user'])
            ->get(route('partner.orders.index', ['status' => 'paid']));

        $response->assertOk();
        $response->assertSee('#' . $paidOrder->id);
        $response->assertDontSee('#' . $pendingOrder->id);
    }

    public function test_search_filter_filters_by_order_id(): void
    {
        $this->setUpPartner();
        $paidOrder = $this->orderFor('paid');
        $pendingOrder = $this->orderFor('pending');

        $response = $this->actingAs($this->partner['user'])
            ->get(route('partner.orders.index', ['search' => $pendingOrder->id]));

        $response->assertOk();
        $response->assertSee('#' . $pendingOrder->id);
        $response->assertDontSee('#' . $paidOrder->id);
    }

    public function test_invalid_status_falls_back_to_unfiltered(): void
    {
        $this->setUpPartner();
        $paidOrder = $this->orderFor('paid');
        $pendingOrder = $this->orderFor('pending');

        $response = $this->actingAs($this->partner['user'])
            ->get(route('partner.orders.index', ['status' => 'nonsense']));

        $response->assertOk();
        $response->assertSee('#' . $paidOrder->id);
        $response->assertSee('#' . $pendingOrder->id);
    }

    public function test_show_displays_order_with_partner_items(): void
    {
        $this->setUpPartner();
        $order = $this->orderFor('paid');

        $response = $this->actingAs($this->partner['user'])
            ->get(route('partner.orders.show', $order->id));

        $response->assertOk();
        $response->assertSee('Order #' . $order->id);
        $response->assertSee('Items to Fulfill');
    }
}