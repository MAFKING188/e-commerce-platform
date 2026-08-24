<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Modules\IdentityAccess\Mail\OtpMail;
use Modules\IdentityAccess\Models\User;
use Tests\TestCase;

class GoogleAuthStatusTest extends TestCase
{
    use RefreshDatabase;

    private function mockGoogle(string $email, ?string $googleId = 'google-1'): void
    {
        $user = (new SocialiteUser)->map([
            'id' => $googleId,
            'name' => 'Google User',
            'email' => $email,
            'avatar' => null,
        ]);
        Socialite::shouldReceive('driver->user')->andReturn($user);
    }

    public function test_suspended_user_with_linked_google_is_blocked(): void
    {
        $user = User::create([
            'name' => 'Suspended',
            'email' => 'suspended@test.com',
            'password' => null,
            'role' => 'user',
            'status' => 'suspended',
            'google_id' => 'google-1',
            'email_verified_at' => now(),
        ]);
        $this->mockGoogle($user->email);

        $this->get('/auth/google/callback')
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_active_admin_without_2fa_is_forced_through_challenge(): void
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => null,
            'role' => 'admin',
            'status' => 'active',
            'google_id' => 'google-1',
            'email_verified_at' => now(),
            'two_factor_type' => null,
        ]);
        $this->mockGoogle($user->email);

        $this->get('/auth/google/callback')
            ->assertRedirect(route('2fa.challenge'));
        Mail::assertQueued(OtpMail::class);
        $this->assertGuest();
    }

    public function test_new_google_registration_is_verified_and_welcomed(): void
    {
        Mail::fake();

        $this->mockGoogle('fresh.google@test.com');

        $this->get('/auth/google/callback')
            ->assertRedirect('/');

        $user = User::where('email', 'fresh.google@test.com')->first();
        $this->assertNotNull($user, 'Google user was created');
        $this->assertNotNull($user->email_verified_at, 'Google user must be verified at creation');
        $this->assertNull($user->password, 'Google-only account has no password');

        Mail::assertQueued(\Modules\IdentityAccess\Mail\WelcomeMember::class, fn ($m) => $m->user->email === 'fresh.google@test.com');
    }
}
