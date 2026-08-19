<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Modules\IdentityAccess\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Modules\IdentityAccess\Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    private function fakeGoogleUser(string $email, string $googleId = 'g-1', string $name = 'G Ogle'): void
    {
        $abstract = new \Laravel\Socialite\Two\User();
        $abstract->map(['id' => $googleId, 'name' => $name, 'email' => $email, 'avatar' => 'https://example.com/avatar.png']);
        $abstract->token = 'token';
        $abstract->refreshToken = 'refresh';

        Socialite::shouldReceive('driver')->with('google')->andReturn(
            new class($abstract) {
                public $user;

                public function __construct($user)
                {
                    $this->user = $user;
                }

                public function redirect()
                {
                    return redirect('/auth/google/callback');
                }

                public function user()
                {
                    return $this->user;
                }
            }
        );
    }

    public function test_google_login_creates_new_account(): void
    {
        $this->fakeGoogleUser('new.person@example.com');

        $response = $this->get('/auth/google/redirect');
        $response->assertRedirect('/auth/google/callback');

        $this->get('/auth/google/callback')->assertRedirect('/');

        $user = User::where('email', 'new.person@example.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('g-1', $user->google_id);
        $this->assertNull($user->password);
        $this->assertSame('user', $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_login_links_existing_account(): void
    {
        $user = User::factory()->create(['status' => 'active', 'email' => 'existing@example.com', 'password' => 'password']);
        $this->fakeGoogleUser('existing@example.com', 'g-99');

        $this->get('/auth/google/callback')->assertRedirect('/');

        $this->assertSame('g-99', $user->fresh()->google_id);
        $this->assertAuthenticatedAs($user);
    }

    public function test_google_login_with_2fa_goes_to_challenge(): void
    {
        $user = User::factory()->create([
            'status' => 'active', 'email' => 'twofa@example.com',
            'two_factor_secret' => 'ABCDEFGHIJKLMNOP',
            'two_factor_type' => 'totp',
            'two_factor_confirmed_at' => now(),
        ]);
        $this->fakeGoogleUser('twofa@example.com');

        $this->get('/auth/google/callback')->assertRedirect(route('2fa.challenge'));
        $this->assertGuest();
        $this->assertSame($user->id, session('2fa.pending'));
    }

    public function test_google_login_with_conflicting_link_rejected(): void
    {
        $user = User::factory()->create(['status' => 'active', 'email' => 'linked@example.com', 'google_id' => 'g-original']);
        $this->fakeGoogleUser('linked@example.com', 'g-other');

        $this->get('/auth/google/callback')->assertRedirect(route('login'));
        $this->assertGuest();
        $this->assertSame('g-original', $user->fresh()->google_id);
    }
}