<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Modules\IdentityAccess\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['status' => 'active', 'role' => 'user']);
    }

    private function enableTotp(User $user): void
    {
        $this->actingAs($user)->post('/profile/settings/twofa/enable-totp', ['password' => 'password'])
            ->assertRedirect();
    }

    public function test_enable_totp_requires_current_password(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post('/profile/settings/twofa/enable-totp', ['password' => 'wrong'])
            ->assertSessionHasErrors('password');

        $this->assertNull($user->fresh()->two_factor_secret);
    }

    public function test_enable_totp_then_confirm_with_valid_code(): void
    {
        $user = $this->user();
        $this->enableTotp($user);

        $secret = $user->fresh()->two_factor_secret;
        $this->assertNotNull($secret);
        $code = \PragmaRX\Google2FALaravel\Facade::getCurrentOtp($secret);

        $this->actingAs($user)->post('/profile/settings/twofa/confirm', ['code' => $code])
            ->assertRedirect();

        $fresh = $user->fresh();
        $this->assertSame('totp', $fresh->two_factor_type->value);
        $this->assertNotNull($fresh->two_factor_confirmed_at);
    }

    public function test_enable_totp_confirm_rejects_wrong_code(): void
    {
        $user = $this->user();
        $this->enableTotp($user);

        $this->actingAs($user)->post('/profile/settings/twofa/confirm', ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertNull($user->fresh()->two_factor_type);
    }

    public function test_confirm_locks_out_after_five_failures(): void
    {
        $user = $this->user();
        $this->enableTotp($user);

        for ($i = 0; $i < 5; $i++) {
            $this->actingAs($user)->post('/profile/settings/twofa/confirm', ['code' => '000000']);
        }

        $fresh = $user->fresh();
        $this->assertNull($fresh->two_factor_secret);
        $this->assertNull($fresh->two_factor_type);
    }

    public function test_disable_requires_password_and_clears_2fa(): void
    {
        $user = User::factory()->create([
            'status' => 'active', 'role' => 'user',
            'two_factor_secret' => 'ABCDEFGHIJKLMNOP',
            'two_factor_type' => 'totp',
            'two_factor_confirmed_at' => now(),
        ]);

        $this->actingAs($user)->post('/profile/settings/twofa/disable', ['password' => 'wrong'])
            ->assertSessionHasErrors('password');

        $this->actingAs($user)->post('/profile/settings/twofa/disable', ['password' => 'password'])
            ->assertRedirect();

        $fresh = $user->fresh();
        $this->assertNull($fresh->two_factor_secret);
        $this->assertNull($fresh->two_factor_type);
        $this->assertNull($fresh->two_factor_confirmed_at);
    }
}