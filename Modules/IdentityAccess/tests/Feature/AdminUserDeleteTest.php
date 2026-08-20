<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Models\User;
use Modules\MarketplacePipeline\Models\Order;
use Tests\TestCase;

class AdminUserDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_purging_a_user_with_orders_returns_friendly_error_not_500(): void
    {
        $target = User::factory()->create();
        Order::create(['user_id' => $target->id, 'total_price' => 100, 'status' => 'pending']);

        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']))
            ->delete("/admin/users/{$target->id}")
            ->assertRedirect()
            ->assertSessionHasNoErrors()
            ->assertSessionHas('error', 'Cannot purge this member: they have orders or partner records. Suspend the account instead.');

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }
}