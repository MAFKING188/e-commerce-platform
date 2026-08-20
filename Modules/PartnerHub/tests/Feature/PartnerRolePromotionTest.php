<?php

namespace Modules\PartnerHub\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Models\User;
use Tests\TestCase;

class PartnerRolePromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_establishing_a_partner_promotes_the_user_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $member = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($admin)
            ->post('/admin/partners', [
                'name' => 'Test Artisan',
                'description' => null,
                'contact_info' => 'contact@test.com',
                'website' => null,
                'user_id' => $member->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $member->id, 'role' => 'partner', 'status' => 'active']);
    }

    public function test_establishing_a_partner_with_an_admin_user_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->post('/admin/partners', [
                'name' => 'Admin Artisan',
                'description' => null,
                'contact_info' => null,
                'website' => null,
                'user_id' => $admin->id,
            ])
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseMissing('partners', ['name' => 'Admin Artisan']);
    }
}
