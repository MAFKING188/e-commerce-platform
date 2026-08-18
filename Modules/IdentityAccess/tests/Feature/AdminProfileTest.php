<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Modules\IdentityAccess\Models\User;
use Modules\MarketplacePipeline\Models\Order;
use App\Models\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Tests\TestCase;

class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_profile_page_renders_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        Address::create(['user_id' => $admin->id, 'is_primary' => true, 'line1' => 'A', 'city' => 'B', 'country' => 'C']);
        $admin->update(['avatars' => 'admin.jpg']);
        Order::create(['user_id' => $admin->id, 'total_price' => 100, 'status' => 'completed']);

        $response = $this->actingAs($admin)->get('/admin/profile');

        $response->assertOk()
            ->assertSee('profile-header')
            ->assertSee('Verified Member')
            ->assertSee('profile-stats');
    }

    public function test_admin_profile_denied_for_buyers(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $this->actingAs($user)->get('/admin/profile')->assertRedirect();
    }
}