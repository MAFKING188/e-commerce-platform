<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Services\OtpService;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function loginViaChallenge(string $email, string $password): void
    {
        $this->post('/accessaccount', ['email' => $email, 'password' => $password])
            ->assertRedirect('/2fa/challenge');
        $this->assertNotNull(session('2fa.pending'));
    }

    public function test_admin_login_requires_email_code_without_enrollment(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        $this->loginViaChallenge($admin->email, 'password');
        $this->get('/2fa/challenge')->assertOk();
    }

    public function test_partner_login_requires_email_code_without_enrollment(): void
    {
        $partner = User::factory()->create(['status' => 'active', 'role' => 'partner']);
        $this->loginViaChallenge($partner->email, 'password');
        $this->get('/2fa/challenge')->assertOk();
    }

    public function test_challenge_sign_out_uses_post_form_not_get_link(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        $this->post('/accessaccount', ['email' => $admin->email, 'password' => 'password']);
        $response = $this->get('/2fa/challenge');
        $response->assertOk()
            ->assertSee('<form method="POST" action="' . route('logout'), false)
            ->assertDontSee('href="' . url('/logout') . '"', false);
    }

    public function test_admin_cannot_reach_admin_pages_before_challenge(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        $this->post('/accessaccount', ['email' => $admin->email, 'password' => 'password']);
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_admin_challenge_with_valid_code_logs_in(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        $this->post('/accessaccount', ['email' => $admin->email, 'password' => 'password']);
        $code = OtpService::issue($admin);
        $this->post('/2fa/challenge/verify', ['code' => $code])->assertRedirect('/');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_challenge_with_invalid_code_fails_and_keeps_pending(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        $this->post('/accessaccount', ['email' => $admin->email, 'password' => 'password']);
        $this->post('/2fa/challenge/verify', ['code' => '000000'])
            ->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_five_invalid_codes_invalidate_session(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        $this->post('/accessaccount', ['email' => $admin->email, 'password' => 'password']);
        foreach (range(1, 5) as $i) {
            $this->post('/2fa/challenge/verify', ['code' => '000000']);
        }
        $this->assertGuest();
        $this->assertNull(session('2fa.pending'));
    }

    public function test_buyer_without_2fa_logs_in_directly(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->post('/accessaccount', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/');
        $this->assertAuthenticatedAs($user);
    }

    public function test_resend_issues_new_code(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        $this->post('/accessaccount', ['email' => $admin->email, 'password' => 'password']);
        $this->post('/2fa/challenge/resend')->assertSessionHasNoErrors();
    }

    /* ---------- buyer opt-in ---------- */

    public function test_buyer_can_enable_email_codes(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->actingAs($user);
        $this->post('/profile/settings/twofa/enable-email', ['password' => 'password'])
            ->assertSessionHasNoErrors();
        $code = OtpService::issue($user);
        $this->post('/profile/settings/twofa/confirm', ['code' => $code])
            ->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertTrue($user->twoFactorEnabled());
    }

    public function test_buyer_opt_in_then_login_requires_challenge(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $user->forceFill(['two_factor_type' => 'email', 'two_factor_confirmed_at' => now()])->save();
        $this->post('/accessaccount', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/2fa/challenge');
    }

    public function test_buyer_can_disable_email_codes(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $user->forceFill(['two_factor_type' => 'email', 'two_factor_confirmed_at' => now()])->save();
        $this->actingAs($user);
        $code = OtpService::issue($user);
        $this->post('/profile/settings/twofa/disable', ['password' => 'password', 'code' => $code])
            ->assertSessionHasNoErrors();
        $user->refresh();
        $this->assertFalse($user->twoFactorEnabled());
    }
}