<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_toggle_adds_and_removes_a_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);

        $this->actingAs($user)
            ->post('/wishlist/toggle', ['product_id' => $product->id])
            ->assertOk()
            ->assertJson(['action' => 'added']);

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($user)
            ->post('/wishlist/toggle', ['product_id' => $product->id])
            ->assertOk()
            ->assertJson(['action' => 'removed']);

        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_archive_page_lists_wishlist_products(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);
        Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);

        $this->actingAs($user)
            ->get('/archive')
            ->assertOk()
            ->assertSee($product->name);
    }
}