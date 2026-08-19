<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Modules\IdentityAccess\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    private array $payload = [
        'name' => 'New Member',
        'email' => 'newmember@test.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'user',
        'phone' => '+33 6 12 34 56 78',
        'country' => 'FR',
        'newsletter_optin' => 1,
    ];

    public function test_signup_stores_phone_country_and_newsletter_optin(): void
    {
        $this->post('/createaccount', $this->payload);

        $user = User::where('email', 'newmember@test.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('+33 6 12 34 56 78', $user->phone);
        $this->assertSame('FR', $user->country);
        $this->assertTrue($user->newsletter_optin);
    }

    public function test_signup_defaults_newsletter_to_off(): void
    {
        $payload = $this->payload;
        unset($payload['newsletter_optin']);

        $this->post('/createaccount', $payload);

        $user = User::where('email', 'newmember@test.com')->first();
        $this->assertFalse($user->newsletter_optin);
    }

    public function test_signup_requires_phone_and_country(): void
    {
        $payload = $this->payload;
        unset($payload['phone'], $payload['country']);

        $this->post('/createaccount', $payload)->assertSessionHasErrors(['phone', 'country']);
        $this->assertNull(User::where('email', 'newmember@test.com')->first());
    }

    public function test_signup_requires_matching_password_confirmation(): void
    {
        $payload = $this->payload;
        $payload['password_confirmation'] = 'different123';

        $this->post('/createaccount', $payload)->assertSessionHasErrors('password');
        $this->assertNull(User::where('email', 'newmember@test.com')->first());
    }

    public function test_signup_sets_currency_session_from_country(): void
    {
        $payload = $this->payload;
        $payload['country'] = 'MA';

        $this->post('/createaccount', $payload);

        $this->assertSame('MAD', session('currency'));
    }

    public function test_signup_with_unmapped_country_falls_back_to_default_currency(): void
    {
        $payload = $this->payload;
        $payload['country'] = 'JP';

        $this->post('/createaccount', $payload);

        $this->assertSame(config('currency.default'), session('currency'));
    }

    public function test_signup_redirects_to_verify_email_with_unverified_account(): void
    {
        $this->post('/createaccount', $this->payload)->assertRedirect(route('verify-email'));

        $user = User::where('email', 'newmember@test.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);
    }

    public function test_verify_email_with_valid_code_marks_verified(): void
    {
        $this->post('/createaccount', $this->payload);

        $user = User::where('email', 'newmember@test.com')->first();
        $code = \Modules\IdentityAccess\Services\OtpService::issue($user);
        $this->post('/verify-email', ['code' => $code])->assertRedirect('/');
        $user->refresh();
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_unverified_login_redirects_to_verify_email(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user', 'email_verified_at' => null]);
        $this->post('/accessaccount', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('verify-email'));
    }

    public function test_verify_email_resend_route_exists(): void
    {
        $this->post('/verify-email/resend')->assertRedirect('/login');
    }
}
