<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Models\User;
use Tests\TestCase;

class SendCodeButtonTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_email_code_issues_otp(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/profile/send-email-code')
            ->assertSessionHas('status');
    }

    public function test_send_password_code_issues_otp(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/profile/send-password-code')
            ->assertSessionHas('status');
    }

    public function test_send_disable_code_issues_otp(): void
    {
        $user = User::factory()->create(['two_factor_type' => 'email', 'two_factor_confirmed_at' => now()]);

        $this->actingAs($user)
            ->post('/profile/settings/twofa/send-disable-code')
            ->assertSessionHas('status');
    }
}