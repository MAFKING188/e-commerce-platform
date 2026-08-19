<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Modules\IdentityAccess\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Tests\TestCase;
use Illuminate\Support\Facades\Mail;
use Modules\IdentityAccess\Mail\OtpMail;

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

    /* ---------- challenge flow ---------- */

    public function test_password_login_with_2fa_redirects_to_challenge(): void
    {
        $user = User::factory()->create([
            'status' => 'active', 'role' => 'user',
            'two_factor_secret' => 'ABCDEFGHIJKLMNOP',
            'two_factor_type' => 'totp',
            'two_factor_confirmed_at' => now(),
        ]);

        $response = $this->post('/accessaccount', ['email' => $user->email, 'password' => 'password']);
        $response->assertRedirect(route('2fa.challenge'));
        $this->assertGuest();
        $this->assertSame($user->id, session('2fa.pending'));
    }

    public function test_challenge_verify_with_totp_logs_in(): void
    {
        $secret = \PragmaRX\Google2FALaravel\Facade::generateSecretKey();
        $user = User::factory()->create([
            'status' => 'active', 'role' => 'user',
            'two_factor_secret' => $secret,
            'two_factor_type' => 'totp',
            'two_factor_confirmed_at' => now(),
        ]);
        $code = \PragmaRX\Google2FALaravel\Facade::getCurrentOtp($secret);

        $this->post('/accessaccount', ['email' => $user->email, 'password' => 'password']);
        $this->post('/2fa/challenge/verify', ['code' => $code])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
        $this->assertNull(session('2fa.pending'));
    }

    public function test_challenge_wrong_code_after_five_attempts_invalidates_session(): void
    {
        $user = User::factory()->create([
            'status' => 'active', 'role' => 'user',
            'two_factor_secret' => 'ABCDEFGHIJKLMNOP',
            'two_factor_type' => 'totp',
            'two_factor_confirmed_at' => now(),
        ]);

        $this->post('/accessaccount', ['email' => $user->email, 'password' => 'password']);

        for ($i = 0; $i < 4; $i++) {
            $this->post('/2fa/challenge/verify', ['code' => '000000'])->assertSessionHasErrors('code');
        }
        $this->post('/2fa/challenge/verify', ['code' => '000000'])->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertNull(session('2fa.pending'));
    }

    public function test_email_method_challenge_with_queued_code(): void
    {
        Mail::fake();
        $user = User::factory()->create([
            'status' => 'active', 'role' => 'user',
            'two_factor_type' => 'email',
            'two_factor_confirmed_at' => now(),
        ]);

        $this->post('/accessaccount', ['email' => $user->email, 'password' => 'password']);

        $code = '';
        Mail::assertQueued(OtpMail::class, function (OtpMail $mail) use (&$code) {
            $code = $mail->code;
            return true;
        });
        $this->assertNotSame('', $code);

        $this->post('/2fa/challenge/verify', ['code' => $code])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_resend_respects_cooldown(): void
    {
        Mail::fake();
        $user = User::factory()->create([
            'status' => 'active', 'role' => 'user',
            'two_factor_type' => 'email',
            'two_factor_confirmed_at' => now(),
        ]);

        $this->post('/accessaccount', ['email' => $user->email, 'password' => 'password']);
        $this->post('/2fa/challenge/resend')->assertRedirect();
        Mail::assertQueued(OtpMail::class, 2);

        $this->post('/2fa/challenge/resend');
        Mail::assertQueued(OtpMail::class, 2);
    }

    public function test_google_only_account_cannot_login_with_password(): void
    {
        $user = User::factory()->create([
            'status' => 'active', 'role' => 'user',
            'password' => null,
            'google_id' => 'g-123',
        ]);

        $this->post('/accessaccount', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    /* ---------- admin enforcement ---------- */

    public function test_admin_without_2fa_is_gated_from_admin_pages(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);

        $this->post('/accessaccount', ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect('/');
        $this->assertTrue(session('2fa.required'));

        $this->get('/admin/dashboard')->assertRedirect(route('profile.settings'));
        $this->get('/admin/users')->assertRedirect(route('profile.settings'));
        $this->get('/admin/products')->assertRedirect(route('profile.settings'));
        $this->get('/admin/orders')->assertRedirect(route('profile.settings'));
        $this->get('/admin/partners')->assertRedirect(route('profile.settings'));
    }

    public function test_admin_2fa_flag_cleared_after_enrollment(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);

        $this->post('/accessaccount', ['email' => $admin->email, 'password' => 'password']);
        $this->assertTrue(session('2fa.required'));

        $this->actingAs($admin)->post('/profile/settings/twofa/enable-totp', ['password' => 'password']);
        $secret = $admin->fresh()->two_factor_secret;
        $code = \PragmaRX\Google2FALaravel\Facade::getCurrentOtp($secret);
        $this->actingAs($admin)->post('/profile/settings/twofa/confirm', ['code' => $code])->assertRedirect();

        $this->assertNull(session('2fa.required'));
        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
    }

    public function test_acting_as_admin_unaffected_by_gate(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);

        $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
    }
}