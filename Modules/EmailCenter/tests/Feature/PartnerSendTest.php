<?php

namespace Modules\EmailCenter\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\CatalogDelivery\Models\Product;
use Modules\EmailCenter\Mail\PlatformMail;
use Modules\IdentityAccess\Models\User;
use Modules\MarketplacePipeline\Models\Order;
use Modules\PartnerHub\Models\Partner;
use Tests\TestCase;

class PartnerSendTest extends TestCase
{
    use RefreshDatabase;

    private function makePartnerWithBuyer(): array
    {
        $partnerUser = User::factory()->create(['role' => 'partner', 'status' => 'active']);
        $partner = Partner::create(['user_id' => $partnerUser->id, 'name' => 'Test Artisan']);

        $buyer = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $product = Product::factory()->create();
        $product->partners()->attach($partner->id);

        $order = Order::create(['user_id' => $buyer->id, 'total_price' => 100, 'status' => 'completed']);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 100]);

        return [$partnerUser, $buyer];
    }

    public function test_partner_can_email_own_buyer(): void
    {
        Mail::fake();
        [$partnerUser, $buyer] = $this->makePartnerWithBuyer();

        $this->actingAs($partnerUser)
            ->post(route('partner.email.send'), [
                'subject' => 'Hi {name}',
                'body' => 'Your order from {name}!',
                'user_ids' => [$buyer->id],
            ])
            ->assertRedirect();

        Mail::assertQueued(PlatformMail::class, 1);

        $log = \Modules\EmailCenter\Models\EmailLog::first();
        $this->assertEquals('partner', $log->sender_role);
        $this->assertEquals($buyer->email, $log->recipient_email);
    }

    public function test_partner_cannot_email_non_buyers(): void
    {
        Mail::fake();
        [$partnerUser] = $this->makePartnerWithBuyer();
        $nonBuyer = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);

        $this->actingAs($partnerUser)
            ->post(route('partner.email.send'), [
                'subject' => 'Spam attempt',
                'body' => 'Body',
                'user_ids' => [$nonBuyer->id],
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        Mail::assertNothingSent();
        $this->assertDatabaseCount('email_center_logs', 0);
    }

    public function test_compose_shows_empty_state_without_buyers(): void
    {
        $partnerUser = User::factory()->create(['role' => 'partner', 'status' => 'active']);
        Partner::create(['user_id' => $partnerUser->id, 'name' => 'No Orders Artisan']);

        $this->actingAs($partnerUser)
            ->get(route('partner.email.compose'))
            ->assertOk()
            ->assertSee('No buyers yet');
    }

    public function test_non_partner_is_redirected_home(): void
    {
        Mail::fake();
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)
            ->post(route('partner.email.send'), ['subject' => 'x', 'body' => 'y', 'user_ids' => [1]])
            ->assertRedirect(route('home'));

        Mail::assertNothingSent();
    }
}