<?php

namespace Modules\TelemetryPipeline\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\TelemetryPipeline\Tests\TestCase;

class RateLimiterTest extends TestCase
{
    use RefreshDatabase;

    public function test_auth_limiter_throttles_after_five_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/accessaccount', ['email' => 'x@y.z', 'password' => 'bad'])->assertStatus(302);
        }

        $this->post('/accessaccount', ['email' => 'x@y.z', 'password' => 'bad'])->assertStatus(429);
    }
}