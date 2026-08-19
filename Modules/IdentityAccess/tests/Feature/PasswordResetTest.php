<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Modules\IdentityAccess\Mail\PasswordChangedMail;
use Modules\IdentityAccess\Mail\PasswordResetMail;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email' => 'resetme@test.com']);
    }

    public function test_forgot_password_page_renders(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertSee('Reset Password');
    }

    public function test_forgot_password_sends_reset_link_and_queues_email(): void
    {
        Mail::fake();

        $this->post('/forgot-password', ['email' => 'resetme@test.com'])
            ->assertSessionHasNoErrors();

        Mail::assertQueued(PasswordResetMail::class, fn ($mail) => $mail->hasTo('resetme@test.com'));
    }

    public function test_forgot_password_with_unknown_email_does_not_leak(): void
    {
        Mail::fake();

        $this->post('/forgot-password', ['email' => 'nobody@test.com'])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status');

        Mail::assertNothingQueued();
    }

    public function test_reset_password_page_renders_with_token(): void
    {
        $token = Password::broker()->createToken($this->user);

        $this->get('/reset-password/' . $token)
            ->assertOk()
            ->assertSee('Set New Password');
    }

    public function test_reset_with_valid_token_updates_password_and_logs_in(): void
    {
        $token = Password::broker()->createToken($this->user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'resetme@test.com',
            'password' => 'brandnewpass123',
            'password_confirmation' => 'brandnewpass123',
        ])->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($this->user);
        $this->assertTrue(password_verify('brandnewpass123', $this->user->fresh()->password));
    }

    public function test_reset_with_mismatched_confirmation_is_rejected(): void
    {
        $token = Password::broker()->createToken($this->user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'resetme@test.com',
            'password' => 'brandnewpass123',
            'password_confirmation' => 'different123',
        ])->assertSessionHasErrors('password');

        $this->assertTrue(password_verify('password', $this->user->fresh()->password));
    }

    public function test_reset_with_wrong_email_is_rejected(): void
    {
        $token = Password::broker()->createToken($this->user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'wrong@test.com',
            'password' => 'brandnewpass123',
            'password_confirmation' => 'brandnewpass123',
        ])->assertSessionHasErrors('email');
    }

    public function test_reset_queues_password_changed_alert(): void
    {
        Mail::fake();
        $token = Password::broker()->createToken($this->user);

        $this->post('/reset-password', [
            'token' => $token,
            'email' => 'resetme@test.com',
            'password' => 'brandnewpass123',
            'password_confirmation' => 'brandnewpass123',
        ]);

        Mail::assertQueued(PasswordChangedMail::class, fn ($mail) => $mail->hasTo('resetme@test.com'));
    }

    public function test_settings_password_change_queues_changed_alert(): void
    {
        Mail::fake();

        $this->actingAs($this->user)->put('/profile/password', [
            'current_password' => 'password',
            'password' => 'freshpass123',
            'password_confirmation' => 'freshpass123',
        ])->assertSessionHas('success');

        Mail::assertQueued(PasswordChangedMail::class, fn ($mail) => $mail->hasTo('resetme@test.com'));
    }
}