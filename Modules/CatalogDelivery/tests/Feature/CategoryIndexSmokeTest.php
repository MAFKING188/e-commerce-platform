<?php

namespace Modules\CatalogDelivery\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CatalogDelivery\Tests\TestCase;

class CategoryIndexSmokeTest extends TestCase
{
    use RefreshDatabase;
    public function test_admin_categories_index_renders_200_with_product_counts()
    {
        $admin = \Modules\IdentityAccess\Models\User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $category = \Modules\CatalogDelivery\Models\Category::factory()->create();
        \Modules\CatalogDelivery\Models\Product::factory()->count(2)->create(['category_id' => $category->id]);

        $this->withoutVite();

        $response = $this->actingAs($admin)->get(route('admin.categories.index'));
        $response->assertStatus(200);
        $response->assertSee('2 items mapped');
    }
}