<?php

namespace Modules\CatalogDelivery\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CatalogDelivery\Models\Product;
use Modules\CatalogDelivery\Tests\TestCase;
use Modules\IdentityAccess\Models\User;
use Modules\PartnerHub\Models\Partner;

class PartnerInventoryFilterTest extends TestCase
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

    private function productFor(string $name, int $stock): Product
    {
        $product = Product::factory()->create(['name' => $name, 'stock' => $stock]);
        $this->partner['partner']->products()->attach($product->id);

        return $product;
    }

    public function test_search_filter_filters_by_product_name(): void
    {
        $this->setUpPartner();
        $oak = $this->productFor('Oak Chair', 10);
        $linen = $this->productFor('Linen Throw', 10);

        $response = $this->actingAs($this->partner['user'])
            ->get(route('partner.inventory.index', ['search' => 'Oak']));

        $response->assertOk();
        $response->assertSee($oak->name);
        $response->assertDontSee($linen->name);
    }

    public function test_stock_filter_shows_only_low_stock_products(): void
    {
        $this->setUpPartner();
        $low = $this->productFor('Limited Edition', 2);
        $healthy = $this->productFor('Evergreen', 50);

        $response = $this->actingAs($this->partner['user'])
            ->get(route('partner.inventory.index', ['stock' => 'low']));

        $response->assertOk();
        $response->assertSee($low->name);
        $response->assertDontSee($healthy->name);
    }

    public function test_unknown_stock_filter_falls_back_to_unfiltered(): void
    {
        $this->setUpPartner();
        $low = $this->productFor('Limited Edition', 2);
        $healthy = $this->productFor('Evergreen', 50);

        $response = $this->actingAs($this->partner['user'])
            ->get(route('partner.inventory.index', ['stock' => 'bogus']));

        $response->assertOk();
        $response->assertSee($low->name);
        $response->assertSee($healthy->name);
    }
}