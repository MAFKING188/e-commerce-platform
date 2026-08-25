<?php

namespace Modules\MarketplacePipeline\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\CatalogDelivery\Models\Product;
use Modules\IdentityAccess\Models\User;
use Modules\MarketplacePipeline\Mail\PaymentSuccess;
use Modules\MarketplacePipeline\Models\Order;
use Modules\MarketplacePipeline\Models\Payment;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Tests\TestCase;

class MoneyClusterTest extends TestCase
{
    use RefreshDatabase;

    private function makePendingOrderWithPayment(User $buyer): array
    {
        $order = Order::create([
            'user_id' => $buyer->id,
            'total_price' => 120.00,
            'status' => 'pending',
        ]);

        $payment = Payment::create([
            'order_id' => $order->id,
            'method' => 'paypal',
            'transaction_id' => 'PAYPAL-ORDER-1',
            'status' => 'pending',
            'amount' => 120.00,
        ]);

        return [$order, $payment];
    }

    private function fakePayPal(array $captureResponse): void
    {
        $provider = \Mockery::mock(PayPalClient::class);
        $provider->shouldReceive('setApiCredentials')->andReturnNull();
        $provider->shouldReceive('getAccessToken')->andReturn('ACCESS-TOKEN');
        $provider->shouldReceive('capturePaymentOrder')->andReturn($captureResponse);
        $this->app->instance(PayPalClient::class, $provider);
    }

    // ---------- capture guards ----------

    public function test_guest_cannot_hit_capture(): void
    {
        $this->get(route('paypal.capture', ['token' => 'PAYPAL-ORDER-1']))
            ->assertRedirect(route('login'));
    }

    public function test_unknown_token_is_rejected_before_external_call(): void
    {
        $buyer = User::factory()->create();

        $this->actingAs($buyer)
            ->get(route('paypal.capture', ['token' => 'NO-SUCH-PAYMENT']))
            ->assertRedirect()
            ->assertSessionHasErrors();
    }

    public function test_user_cannot_capture_another_users_payment(): void
    {
        Mail::fake();
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $this->makePendingOrderWithPayment($owner);

        $this->actingAs($attacker)
            ->get(route('paypal.capture', ['token' => 'PAYPAL-ORDER-1']))
            ->assertForbidden();

        $this->assertDatabaseHas('payments', ['transaction_id' => 'PAYPAL-ORDER-1', 'status' => 'pending']);
        Mail::assertNothingQueued();
    }

    public function test_capture_is_idempotent_for_already_paid_payments(): void
    {
        Mail::fake();
        $owner = User::factory()->create();
        [$order, $payment] = $this->makePendingOrderWithPayment($owner);
        $payment->update(['status' => 'paid']);

        $this->actingAs($owner)
            ->get(route('paypal.capture', ['token' => 'PAYPAL-ORDER-1']))
            ->assertRedirect()
            ->assertSessionHas('status');

        Mail::assertNothingQueued(PaymentSuccess::class);
    }

    public function test_successful_capture_marks_paid_and_emails_owner(): void
    {
        Mail::fake();
        $owner = User::factory()->create(['email_verified_at' => now()]);
        [$order] = $this->makePendingOrderWithPayment($owner);

        $this->fakePayPal([
            'status' => 'COMPLETED',
            'id' => 'PAYPAL-ORDER-1',
        ]);

        $this->actingAs($owner)
            ->get(route('paypal.capture', ['token' => 'PAYPAL-ORDER-1']))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('payments', ['transaction_id' => 'PAYPAL-ORDER-1', 'status' => 'paid']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
        Mail::assertQueued(PaymentSuccess::class, fn ($m) => $m->payment->order->user_id === $owner->id);
    }

    public function test_failed_paypal_capture_leaves_order_pending(): void
    {
        Mail::fake();
        $owner = User::factory()->create();
        [$order] = $this->makePendingOrderWithPayment($owner);

        $this->fakePayPal(['status' => 'DECLINED']);

        $this->actingAs($owner)
            ->get(route('paypal.capture', ['token' => 'PAYPAL-ORDER-1']))
            ->assertRedirect()
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
        $this->assertDatabaseHas('payments', ['transaction_id' => 'PAYPAL-ORDER-1', 'status' => 'pending']);
        Mail::assertNothingQueued();
    }

    public function test_duplicate_paypal_checkout_is_rejected(): void
    {
        $buyer = User::factory()->create();
        [$order] = $this->makePendingOrderWithPayment($buyer);

        // A pending PayPal payment already exists for this order — a second
        // checkout attempt (e.g. double-click) must be blocked before any
        // external PayPal call happens.
        $this->actingAs($buyer)
            ->post(route('paypal.store'), ['order_id' => $order->id])
            ->assertRedirect()
            ->assertSessionHasErrors();

        // Exactly one (the pre-existing) pending PayPal payment remains.
        $this->assertDatabaseCount('payments', 1);
    }

    // ---------- cancellation guards ----------

    public function test_buyer_can_cancel_own_pending_order_and_stock_is_restored(): void
    {
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['stock' => 3]);

        $order = Order::create(['user_id' => $buyer->id, 'total_price' => 50, 'status' => 'pending']);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 2, 'price' => 25]);
        $product->decrement('stock', 2); // simulate checkout decrement

        $this->actingAs($buyer)
            ->patch(route('orders.cancel', $order->id))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'stock' => 3]);
    }

    public function test_user_cannot_cancel_another_users_order(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();

        $order = Order::create(['user_id' => $owner->id, 'total_price' => 50, 'status' => 'pending']);

        // Ownership scoping in CheckoutService hides existence: 404-style redirect.
        $this->actingAs($attacker)
            ->patch(route('orders.cancel', $order->id))
            ->assertRedirect()
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending']);
    }

    public function test_paid_orders_cannot_be_cancelled(): void
    {
        $buyer = User::factory()->create();
        $order = Order::create(['user_id' => $buyer->id, 'total_price' => 50, 'status' => 'paid']);

        $this->actingAs($buyer)
            ->patch(route('orders.cancel', $order->id))
            ->assertRedirect()
            ->assertSessionHasErrors();

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
    }

    public function test_confirmation_page_shows_receipt_to_owner_only(): void
    {
        $buyer = User::factory()->create();
        $order = Order::create(['user_id' => $buyer->id, 'total_price' => 50, 'status' => 'pending']);
        $product = Product::factory()->create(['name' => 'Confirmation Piece']);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 50]);

        $this->actingAs($buyer)
            ->get(route('orders.confirmation', $order->id))
            ->assertOk()
            ->assertSee('your order is confirmed')
            ->assertSee('Confirmation Piece');

        $this->actingAs(User::factory()->create())
            ->get(route('orders.confirmation', $order->id))
            ->assertNotFound();
    }

    public function test_checkout_failure_surfaces_error_and_fresh_code(): void
    {
        Mail::fake();
        $buyer = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);
        $cart = \Modules\MarketplacePipeline\Models\Cart::create(['user_id' => $buyer->id]);
        $cart->items()->create(['product_id' => $product->id, 'quantity' => 2, 'price' => $product->price]);
        $product->update(['stock' => 1]); // insufficient at checkout time

        $code = \Modules\IdentityAccess\Services\OtpService::issue($buyer);

        $this->actingAs($buyer)
            ->post('/orders/store', [
                'recipient_name' => 'Test Buyer',
                'recipient_phone' => '+212600000000',
                'shipping_line1' => '1 Test Way',
                'shipping_city' => 'Testville',
                'shipping_country' => 'MA',
                'code' => $code,
            ])
            ->assertRedirect();

        $this->followingRedirects()
            ->get('/cart')
            ->assertOk()
            ->assertSee('We could not place your order')
            ->assertSee('fresh verification code');
    }

    private function verifiedPayload(User $user): array
    {
        $code = \Modules\IdentityAccess\Services\OtpService::issue($user);

        return [
            'recipient_name' => 'Test Buyer',
            'recipient_phone' => '+212600000000',
            'shipping_line1' => '1 Test Way',
            'shipping_city' => 'Testville',
            'shipping_country' => 'MA',
            'code' => $code,
        ];
    }
}
