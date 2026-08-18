<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Modules\IdentityAccess\Models\User;
use Modules\CatalogDelivery\Models\Product;
use Modules\CatalogDelivery\Models\Review;
use Modules\IdentityAccess\Models\Wishlist;
use Modules\MarketplacePipeline\Models\Order;
use App\Models\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Tests\TestCase;

class IdentitySignalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_tier_boundaries(): void
    {
        $plain = User::factory()->create(['status' => 'active']);
        Order::create(['user_id' => $plain->id, 'total_price' => 499, 'status' => 'completed']);
        $this->assertSame('Member', $plain->memberTier());

        $collector = User::factory()->create(['status' => 'active']);
        Order::create(['user_id' => $collector->id, 'total_price' => 500, 'status' => 'completed']);
        $this->assertSame('Collector', $collector->memberTier());

        $patron = User::factory()->create(['status' => 'active']);
        Order::create(['user_id' => $patron->id, 'total_price' => 2500, 'status' => 'completed']);
        $this->assertSame('Patron', $patron->memberTier());

        $benefactor = User::factory()->create(['status' => 'active']);
        Order::create(['user_id' => $benefactor->id, 'total_price' => 10000, 'status' => 'completed']);
        $this->assertSame('Benefactor', $benefactor->memberTier());
    }

    public function test_verification_requires_active_avatar_address_and_completed_order(): void
    {
        $user = User::factory()->create(['status' => 'active', 'avatars' => 'u.jpg']);
        Address::create(['user_id' => $user->id, 'is_primary' => true, 'line1' => 'A', 'city' => 'B', 'country' => 'C']);
        Order::create(['user_id' => $user->id, 'total_price' => 100, 'status' => 'completed']);
        $this->assertTrue($user->isVerifiedMember());

        $user->update(['status' => 'pending']);
        $this->assertFalse($user->fresh()->isVerifiedMember());
        $user->update(['status' => 'active']);
        $user->update(['avatars' => null]);
        $this->assertFalse($user->fresh()->isVerifiedMember());
    }

    public function test_activity_timeline_merges_orders_reviews_and_archives_sorted_desc(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $product = Product::factory()->create(['stock' => 5]);
        Order::create(['user_id' => $user->id, 'total_price' => 100, 'status' => 'completed']);
        Review::create(['user_id' => $user->id, 'product_id' => $product->id, 'rating' => 5, 'comment' => 'Gorgeous']);
        Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);

        $timeline = $user->activityTimeline();
        $this->assertCount(3, $timeline);
        $types = $timeline->pluck('type')->sort()->values()->all();
        $this->assertSame(['archive', 'order', 'review'], $types);
        $this->assertSame('order', $timeline->sortByDesc('at')->first()['type']);
    }
}