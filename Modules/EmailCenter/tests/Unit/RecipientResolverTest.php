<?php

namespace Modules\EmailCenter\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\EmailCenter\Services\RecipientResolver;
use Modules\IdentityAccess\Models\User;
use Modules\MarketplacePipeline\Models\Order;
use Modules\CatalogDelivery\Models\Product;
use Modules\PartnerHub\Models\Partner;
use Tests\TestCase;

class RecipientResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_newsletter_group_excludes_opted_out_users(): void
    {
        $optedIn = User::factory()->create(['status' => 'active', 'email_verified_at' => now(), 'newsletter_optin' => true]);
        $optedOut = User::factory()->create(['status' => 'active', 'email_verified_at' => now(), 'newsletter_optin' => false]);

        $recipients = RecipientResolver::resolveForAdmin('newsletter');

        $this->assertTrue($recipients->contains('id', $optedIn->id));
        $this->assertFalse($recipients->contains('id', $optedOut->id));
    }

    public function test_members_group_is_role_user_only(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'email_verified_at' => now(), 'role' => 'admin']);
        $partner = User::factory()->create(['status' => 'active', 'email_verified_at' => now(), 'role' => 'partner']);
        $member = User::factory()->create(['status' => 'active', 'email_verified_at' => now(), 'role' => 'user']);

        $recipients = RecipientResolver::resolveForAdmin('members');

        $this->assertTrue($recipients->contains('id', $member->id));
        $this->assertFalse($recipients->contains('id', $admin->id));
        $this->assertFalse($recipients->contains('id', $partner->id));
    }

    public function test_individual_user_ids_excludes_inactive_and_unverified(): void
    {
        $activeVerified = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $inactive = User::factory()->create(['status' => 'pending', 'email_verified_at' => now()]);
        $unverified = User::factory()->create(['status' => 'active', 'email_verified_at' => null]);

        $recipients = RecipientResolver::resolveForAdmin(null, [$activeVerified->id, $inactive->id, $unverified->id]);

        $this->assertTrue($recipients->contains('id', $activeVerified->id));
        $this->assertFalse($recipients->contains('id', $inactive->id));
        $this->assertFalse($recipients->contains('id', $unverified->id));
    }

    public function test_resolve_for_partner_returns_only_actual_buyers(): void
    {
        $partnerUser = User::factory()->create(['role' => 'partner', 'status' => 'active']);
        $partner = \Modules\PartnerHub\Models\Partner::create(['user_id' => $partnerUser->id, 'name' => 'Test Partner']);

        $buyer = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $nonBuyer = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);

        $product = \Modules\CatalogDelivery\Models\Product::factory()->create();
        $product->partners()->attach($partner->id);

        $order = \Modules\MarketplacePipeline\Models\Order::create([
            'user_id' => $buyer->id,
            'total_price' => 100,
            'status' => 'completed',
        ]);
        $order->items()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 100]);

        $recipients = RecipientResolver::resolveForPartner($partnerUser->id);

        $this->assertTrue($recipients->contains('id', $buyer->id));
        $this->assertFalse($recipients->contains('id', $nonBuyer->id));
    }

    public function test_resolve_for_partner_returns_empty_when_no_orders(): void
    {
        $partnerUser = User::factory()->create(['role' => 'partner', 'status' => 'active']);
        \Modules\PartnerHub\Models\Partner::create(['user_id' => $partnerUser->id, 'name' => 'Empty Partner']);

        $recipients = RecipientResolver::resolveForPartner($partnerUser->id);

        $this->assertTrue($recipients->isEmpty());
    }

    public function test_replace_placeholders_swaps_name_and_email(): void
    {
        $user = User::factory()->create(['name' => 'John Doe', 'email' => 'john@example.com']);

        $result = RecipientResolver::replacePlaceholders('Hello {name}, your email is {email}', $user);

        $this->assertEquals('Hello John Doe, your email is john@example.com', $result);
    }

    public function test_replace_placeholders_leaves_unknown_braces_intact(): void
    {
        $user = User::factory()->create(['name' => 'John', 'email' => 'john@example.com']);

        $result = RecipientResolver::replacePlaceholders('Hello {name}, {unknown} and {email}', $user);

        $this->assertEquals('Hello John, {unknown} and john@example.com', $result);
    }
}