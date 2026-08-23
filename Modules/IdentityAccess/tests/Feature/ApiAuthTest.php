<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Models\User;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    private function loginPayload(User $user): array
    {
        return ['email' => $user->email, 'password' => 'password'];
    }

    public function test_verified_active_user_receives_sanctum_token(): void
    {
        $user = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);

        $this->postJson('/api/login', $this->loginPayload($user))
            ->assertOk()
            ->assertJsonStructure(['access_token', 'token_type']);
    }

    public function test_pending_user_is_rejected(): void
    {
        $user = User::factory()->create(['status' => 'pending', 'email_verified_at' => now()]);

        $this->postJson('/api/login', $this->loginPayload($user))
            ->assertStatus(403)
            ->assertJsonMissing(['access_token']);
    }

    public function test_suspended_user_is_rejected(): void
    {
        $user = User::factory()->create(['status' => 'suspended', 'email_verified_at' => now()]);

        $this->postJson('/api/login', $this->loginPayload($user))
            ->assertStatus(403);
    }

    public function test_unverified_user_is_rejected(): void
    {
        $user = User::factory()->create(['status' => 'active', 'email_verified_at' => null]);

        $this->postJson('/api/login', $this->loginPayload($user))
            ->assertStatus(403);
    }

    public function test_bad_credentials_are_rejected(): void
    {
        $user = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong'])
            ->assertStatus(401);
    }

    public function test_public_registration_endpoint_no_longer_exists(): void
    {
        $this->postJson('/api/register', [
            'name' => 'Hole', 'email' => 'hole@test.com', 'password' => 'password123',
        ])->assertNotFound();
    }

    public function test_api_user_requires_token(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();

        $user = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);
        $this->actingAs($user, 'sanctum')
            ->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('email', $user->email);
    }

    public function test_catalog_endpoint_returns_paginated_products(): void
    {
        \Modules\CatalogDelivery\Models\Product::factory()->count(3)->create();

        $this->getJson('/api/catalog')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }
}