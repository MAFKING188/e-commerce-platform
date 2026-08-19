<?php

namespace Modules\CatalogDelivery\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CatalogDelivery\Models\Category;
use Modules\CatalogDelivery\Models\Product;
use Modules\IdentityAccess\Models\User;
use Modules\PartnerHub\Models\Partner;
use Modules\CatalogDelivery\Tests\TestCase;

class PartnerInventoryCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_can_create_product(): void
    {
        $user = User::factory()->create(['role' => 'partner', 'status' => 'active']);
        $partner = Partner::create([
            'user_id' => $user->id,
            'name' => 'QA Atelier',
            'contact_info' => 'qa@test.com',
        ]);
        $category = Category::create(['name' => 'Home & Kitchen']);

        $this->actingAs($user)->post('/partner/inventory', [
            'name' => 'QA Test Vase',
            'price' => 129.99,
            'stock' => 25,
            'category_id' => $category->id,
            'description' => 'QA-created piece for actor testing.',
        ])->assertSessionHasNoErrors();

        $product = Product::where('name', 'QA Test Vase')->first();
        $this->assertNotNull($product);
        $this->assertSame(129.99, (float) $product->price);
        $this->assertSame(25, (int) $product->stock);
        $this->assertSame($category->id, $product->category_id);
        $this->assertTrue($partner->products()->where('products.id', $product->id)->exists());
    }
}