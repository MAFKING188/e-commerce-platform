<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Services\OtpService;
use Tests\TestCase;

class ChallengeResendTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_without_2fa_can_resend_challenge_code(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'status' => 'active', 'two_factor_type' => null]);
        session(['2fa.pending' => $user->id, '2fa.attempts' => 0, '2fa.pending_method' => 'email']);
        Cache::forget('2fa:resend:' . $user->id);

        $this->post('/2fa/challenge/resend')
            ->assertSessionHas('status')
            ->assertRedirect();
    }
}
