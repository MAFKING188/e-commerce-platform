<?php

namespace Modules\MarketplacePipeline\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CatalogDelivery\Models\Product;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Services\OtpService;
use Tests\TestCase;

class CheckoutStepUpTest extends TestCase
{
    use RefreshDatabase;

    private function setUpCart(User $user): Product
    {
        $product = Product::factory()->create(['stock' => 10]);
        $this->actingAs($user);
        $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);
        return $product;
    }

    private function payload(): array
    {
        return [
            'recipient_name' => 'QA Buyer',
            'recipient_phone' => '+212 600 000 000',
            'shipping_line1' => '1 Test Street',
            'shipping_city' => 'Casablanca',
            'shipping_country' => 'Morocco',
        ];
    }

    public function test_checkout_without_code_asks_for_code_and_does_not_create_order(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->setUpCart($user);
        $this->post('/orders/store', $this->payload())
            ->assertSessionHasErrors('code');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_with_valid_code_creates_order_and_marks_verified(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->setUpCart($user);
        $code = OtpService::issue($user);
        $this->post('/orders/store', $this->payload() + ['code' => $code])
            ->assertRedirect();
        $this->assertDatabaseCount('orders', 1);
        $this->assertNotNull(session('stepup.verified'));
    }

    public function test_verified_marker_bypasses_repeat_codes(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->setUpCart($user);
        $code = OtpService::issue($user);
        $this->post('/orders/store', $this->payload() + ['code' => $code]);
        $product = Product::latest('id')->first();
        $this->post('/cart/add', ['product_id' => $product->id, 'quantity' => 1]);
        $this->post('/orders/store', $this->payload())
            ->assertRedirect();
        $this->assertDatabaseCount('orders', 2);
    }

    public function test_invalid_code_sends_new_code_and_does_not_create_order(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->setUpCart($user);
        $this->post('/orders/store', $this->payload() + ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertDatabaseCount('orders', 0);
    }
}