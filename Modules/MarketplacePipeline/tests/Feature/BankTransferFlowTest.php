<?php

namespace Modules\MarketplacePipeline\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Modules\CatalogDelivery\Models\Category;
use Modules\CatalogDelivery\Models\Product;
use Modules\IdentityAccess\Models\User;
use Modules\MarketplacePipeline\Mail\OrderCompleted;
use Modules\MarketplacePipeline\Models\Cart;
use Modules\MarketplacePipeline\Models\CartItem;
use Modules\MarketplacePipeline\Models\Order;
use Modules\MarketplacePipeline\Models\Payment;
use Modules\MarketplacePipeline\Services\CheckoutService;
use Modules\PartnerHub\Models\Partner;
use Modules\PartnerHub\Models\VendorBankDetail;
use Tests\TestCase;

class BankTransferFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makePartner(string $name): array
    {
        $user = User::factory()->create([
            'role' => 'partner',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        $partner = Partner::create([
            'user_id' => $user->id,
            'name' => $name,
            'contact_info' => $user->email,
        ]);

        VendorBankDetail::create([
            'partner_id' => $partner->id,
            'bank_details_image' => 'bankdetails/' . $partner->id . '.jpg',
            'is_active' => true,
        ]);

        return [$user, $partner];
    }

    private function makeProduct(Partner $partner, int $price): Product
    {
        $category = Category::create(['name' => 'Test Cat ' . $partner->id]);

        $product = Product::create([
            'name' => 'Piece by ' . $partner->name,
            'price' => $price,
            'category_id' => $category->id,
            'stock' => 5,
            'image_url' => 'https://example.com/p.jpg',
        ]);

        $product->partners()->attach($partner->id);

        return $product;
    }

    private function makeCart(User $buyer, array $products): Cart
    {
        $cart = Cart::create(['user_id' => $buyer->id]);

        foreach ($products as $product) {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => 1,
            ]);
        }

        return $cart;
    }

    public function test_multi_vendor_bank_transfer_full_flow(): void
    {
        Storage::fake('public');

        $buyer = User::factory()->create(['email_verified_at' => now()]);

        [$p1User, $p1] = $this->makePartner('Vendor One');
        [$p2User, $p2] = $this->makePartner('Vendor Two');

        $prod1 = $this->makeProduct($p1, 1000.00);
        $prod2 = $this->makeProduct($p2, 500.00);

        $this->makeCart($buyer, [$prod1, $prod2]);

        $delivery = [
            'recipient_name' => 'Mafking Luie',
            'recipient_phone' => '+260 900000000',
            'shipping_line1' => '123 Street',
            'shipping_city' => 'Lusaka',
            'shipping_country' => 'Zambia',
        ];

        // 1) Buyer checks out with bank transfer -> per-vendor payments created.
        $order = (new CheckoutService)->checkout($buyer, $delivery, 'bank_transfer');

        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending_payment']);
        $this->assertCount(2, $order->payments);
        $this->assertTrue(
            $order->payments->contains(fn ($pm) => $pm->partner_id === $p1->id && $pm->method === 'bank_transfer' && $pm->status === 'pending')
        );
        $this->assertTrue(
            $order->payments->contains(fn ($pm) => $pm->partner_id === $p2->id && $pm->method === 'bank_transfer' && $pm->status === 'pending')
        );

        $payment1 = $order->payments->where('partner_id', $p1->id)->first();
        $payment2 = $order->payments->where('partner_id', $p2->id)->first();

        // 2) Buyer uploads proof for each vendor payment.
        foreach ([$payment1, $payment2] as $payment) {
            $response = $this->actingAs($buyer)->post(
                route('payment.handle-upload-proof', $payment->id),
                ['proof_image' => UploadedFile::fake()->image('proof.jpg')]
            );
            $response->assertRedirect();
        }

        $payment1->refresh();
        $payment2->refresh();
        $this->assertNotNull($payment1->proof_path);
        $this->assertNotNull($payment2->proof_path);
        // Order stays pending_payment until vendors validate.
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending_payment']);

        // 3) Partner 1 validates -> paid, but order still pending_payment (partner 2 not done).
        $this->actingAs($p1User)->patch(route('partner.payments.validate', $payment1->id), ['action' => 'approve'])
            ->assertRedirect();
        $payment1->refresh();
        $this->assertSame('paid', $payment1->status);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending_payment']);

        // 4) Partner 2 validates -> all paid -> order becomes paid.
        $this->actingAs($p2User)->patch(route('partner.payments.validate', $payment2->id), ['action' => 'approve'])
            ->assertRedirect();
        $payment2->refresh();
        $this->assertSame('paid', $payment2->status);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);

        // 5) Partner 1 ships -> shipped.
        $this->actingAs($p1User)->patch(route('partner.orders.ship', $order->id))->assertRedirect();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'shipped']);

        // 6) Partner 1 completes -> completed.
        $this->actingAs($p1User)->patch(route('partner.orders.complete', $order->id))->assertRedirect();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
    }

    public function test_partner_cannot_validate_another_vendors_payment(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->create(['email_verified_at' => now()]);
        [$p1User, $p1] = $this->makePartner('Vendor One');
        [$p2User, $p2] = $this->makePartner('Vendor Two');
        $prod1 = $this->makeProduct($p1, 1000.00);
        $this->makeCart($buyer, [$prod1]);

        $order = (new CheckoutService)->checkout($buyer, [
            'recipient_name' => 'X', 'recipient_phone' => '1', 'shipping_line1' => 'a',
            'shipping_city' => 'c', 'shipping_country' => 'z',
        ], 'bank_transfer');

        $payment1 = $order->payments->where('partner_id', $p1->id)->first();

        // Partner 2 tries to validate Partner 1's payment -> 403.
        $this->actingAs($p2User)->patch(route('partner.payments.validate', $payment1->id), ['action' => 'approve'])
            ->assertForbidden();
        $payment1->refresh();
        $this->assertSame('pending', $payment1->status);
    }

    public function test_partner_index_shows_approve_for_pending_proof(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->create(['email_verified_at' => now()]);
        [$p1User, $p1] = $this->makePartner('Vendor One');
        $prod1 = $this->makeProduct($p1, 1000.00);
        $this->makeCart($buyer, [$prod1]);

        $order = (new CheckoutService)->checkout($buyer, [
            'recipient_name' => 'X', 'recipient_phone' => '1', 'shipping_line1' => 'a',
            'shipping_city' => 'c', 'shipping_country' => 'z',
        ], 'bank_transfer');

        $payment1 = $order->payments->where('partner_id', $p1->id)->first();

        // Upload proof so the partner sees an Approve action in the list.
        $this->actingAs($buyer)->post(
            route('payment.handle-upload-proof', $payment1->id),
            ['proof_image' => UploadedFile::fake()->image('proof.jpg')]
        );

        $this->actingAs($p1User)->get(route('partner.orders.index'))
            ->assertOk()
            ->assertSee('Approve')
            ->assertSee('View Proof');
    }

    public function test_partner_index_shows_mark_shipped_for_paid_order(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->create(['email_verified_at' => now()]);
        [$p1User, $p1] = $this->makePartner('Vendor One');
        $prod1 = $this->makeProduct($p1, 1000.00);
        $this->makeCart($buyer, [$prod1]);

        $order = (new CheckoutService)->checkout($buyer, [
            'recipient_name' => 'X', 'recipient_phone' => '1', 'shipping_line1' => 'a',
            'shipping_city' => 'c', 'shipping_country' => 'z',
        ], 'bank_transfer');

        $payment1 = $order->payments->where('partner_id', $p1->id)->first();
        $this->actingAs($buyer)->post(
            route('payment.handle-upload-proof', $payment1->id),
            ['proof_image' => UploadedFile::fake()->image('proof.jpg')]
        );
        $this->actingAs($p1User)->patch(route('partner.payments.validate', $payment1->id), ['action' => 'approve']);

        $this->actingAs($p1User)->get(route('partner.orders.index'))
            ->assertOk()
            ->assertSee('Mark Shipped')
            ->assertDontSee('Approve');
    }

    public function test_partner_index_shows_mark_completed_for_shipped_order_and_emails_buyer(): void
    {
        Storage::fake('public');
        Mail::fake();
        $buyer = User::factory()->create(['email_verified_at' => now()]);
        [$p1User, $p1] = $this->makePartner('Vendor One');
        $prod1 = $this->makeProduct($p1, 1000.00);
        $this->makeCart($buyer, [$prod1]);

        $order = (new CheckoutService)->checkout($buyer, [
            'recipient_name' => 'X', 'recipient_phone' => '1', 'shipping_line1' => 'a',
            'shipping_city' => 'c', 'shipping_country' => 'z',
        ], 'bank_transfer');

        $payment1 = $order->payments->where('partner_id', $p1->id)->first();
        $this->actingAs($buyer)->post(
            route('payment.handle-upload-proof', $payment1->id),
            ['proof_image' => UploadedFile::fake()->image('proof.jpg')]
        );
        $this->actingAs($p1User)->patch(route('partner.payments.validate', $payment1->id), ['action' => 'approve']);
        $this->actingAs($p1User)->patch(route('partner.orders.ship', $order->id));

        $this->actingAs($p1User)->get(route('partner.orders.index'))
            ->assertOk()
            ->assertSee('Mark Completed');

        // Completing notifies the buyer.
        $this->actingAs($p1User)->patch(route('partner.orders.complete', $order->id))
            ->assertRedirect();
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'completed']);
        Mail::assertQueued(OrderCompleted::class, fn ($m) => $m->order->id === $order->id);
    }

    public function test_buyer_can_reupload_proof_after_rejection(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->create(['email_verified_at' => now()]);
        [$p1User, $p1] = $this->makePartner('Vendor One');
        $prod1 = $this->makeProduct($p1, 1000.00);
        $this->makeCart($buyer, [$prod1]);

        $order = (new CheckoutService)->checkout($buyer, [
            'recipient_name' => 'X', 'recipient_phone' => '1', 'shipping_line1' => 'a',
            'shipping_city' => 'c', 'shipping_country' => 'z',
        ], 'bank_transfer');

        $payment1 = $order->payments->where('partner_id', $p1->id)->first();

        // Buyer uploads proof.
        $this->actingAs($buyer)->post(
            route('payment.handle-upload-proof', $payment1->id),
            ['proof_image' => UploadedFile::fake()->image('proof.jpg')]
        );
        // Vendor rejects it.
        $this->actingAs($p1User)->patch(route('partner.payments.validate', $payment1->id), [
            'action' => 'reject', 'reason' => 'Blurry screenshot',
        ]);
        $payment1->refresh();
        $this->assertSame('rejected', $payment1->status);

        // Buyer re-uploads -> resets to pending and clears rejection notes.
        $this->actingAs($buyer)->post(
            route('payment.handle-upload-proof', $payment1->id),
            ['proof_image' => UploadedFile::fake()->image('proof2.jpg')]
        );
        $payment1->refresh();
        $this->assertSame('pending', $payment1->status);
        $this->assertNull($payment1->validated_at);
        $this->assertNull($payment1->validation_notes);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'pending_payment']);

        // Vendor re-approves -> order paid.
        $this->actingAs($p1User)->patch(route('partner.payments.validate', $payment1->id), ['action' => 'approve']);
        $this->assertDatabaseHas('orders', ['id' => $order->id, 'status' => 'paid']);
    }

    public function test_partner_index_shows_ship_for_paid_paypal_order(): void
    {
        Storage::fake('public');
        $buyer = User::factory()->create(['email_verified_at' => now()]);
        [$p1User, $p1] = $this->makePartner('Vendor One');
        $prod1 = $this->makeProduct($p1, 1000.00);
        $this->makeCart($buyer, [$prod1]);

        // Simulate a captured PayPal order (single platform payment, already paid).
        $order = (new CheckoutService)->checkout($buyer, [
            'recipient_name' => 'X', 'recipient_phone' => '1', 'shipping_line1' => 'a',
            'shipping_city' => 'c', 'shipping_country' => 'z',
        ], 'paypal');
        Payment::create([
            'order_id' => $order->id,
            'method' => 'paypal',
            'status' => 'paid',
            'amount' => $order->total_price,
        ]);
        $order->update(['status' => 'paid']);

        // Seller can drive fulfillment for ANY paid order, regardless of method.
        $this->actingAs($p1User)->get(route('partner.orders.index'))
            ->assertOk()
            ->assertSee('Mark Shipped')
            ->assertDontSee('Approve');
    }
}
