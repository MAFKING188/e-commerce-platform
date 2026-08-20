<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Models\User;
use Tests\TestCase;

class StatusGateMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $status): User
    {
        return User::create([
            'name' => $role . ' ' . $status,
            'email' => $role . '-' . $status . '@test.com',
            'password' => bcrypt('password'),
            'role' => $role,
            'status' => $status,
        ]);
    }

    public function test_pending_admin_is_denied_admin_routes(): void
    {
        $this->actingAs($this->makeUser('admin', 'pending'))
            ->get('/admin/dashboard')
            ->assertRedirect();
    }

    public function test_suspended_admin_is_denied_admin_routes(): void
    {
        $this->actingAs($this->makeUser('admin', 'suspended'))
            ->get('/admin/dashboard')
            ->assertRedirect();
    }

    public function test_active_admin_reaches_admin_routes(): void
    {
        $this->actingAs($this->makeUser('admin', 'active'))
            ->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_pending_partner_is_denied_partner_routes(): void
    {
        $this->actingAs($this->makeUser('partner', 'pending'))
            ->get('/partner/dashboard')
            ->assertRedirect();
    }
}