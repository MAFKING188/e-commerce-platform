<?php

namespace Modules\MarketplacePipeline\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CatalogDelivery\Models\Product;
use Modules\IdentityAccess\Models\User;
use Modules\MarketplacePipeline\Models\Cart;
use Modules\MarketplacePipeline\Models\CartItem;
use Modules\MarketplacePipeline\Tests\TestCase;

class CartOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_remove_another_users_cart_item(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $product = Product::factory()->create();
        $cart = Cart::create(['user_id' => $owner->id]);
        $item = CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id]);

        $this->actingAs($attacker)
            ->delete("/cart/remove/{$item->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
    }
}