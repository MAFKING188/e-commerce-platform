<?php

namespace Modules\CatalogDelivery\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CatalogDelivery\Models\Category;
use Modules\CatalogDelivery\Models\Product;
use Modules\MarketplacePipeline\Models\Cart;
use Modules\MarketplacePipeline\Models\CartItem;
use Modules\IdentityAccess\Models\User;
use Tests\TestCase;

class FkSafeDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_product_in_a_cart_returns_friendly_error_not_500(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']))
            ->delete("/admin/products/{$product->id}")
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('error', 'Cannot delete this product: it is referenced by existing orders, carts, or reviews.');

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_deleting_a_category_with_products_returns_friendly_error_not_500(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']))
            ->delete("/admin/categories/{$category->id}")
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('error', 'Cannot delete this category: it still contains products.');

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}