<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Enums\TwoFactorType;
use Modules\IdentityAccess\Models\User;
use Tests\TestCase;

class TwoFactorSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_have_email_verified_at_column(): void
    {
        $user = User::factory()->create();
        $this->assertNull($user->email_verified_at);
    }

    public function test_two_factor_type_enum_has_only_email(): void
    {
        $cases = array_map(fn ($c) => $c->value, TwoFactorType::cases());
        $this->assertEquals(['email'], $cases);
    }

    public function test_is_partner_detects_partner_role(): void
    {
        $partner = User::factory()->create(['role' => 'partner']);
        $user = User::factory()->create(['role' => 'user']);
        $this->assertTrue($partner->isPartner());
        $this->assertFalse($user->isPartner());
    }
}