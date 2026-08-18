<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Modules\IdentityAccess\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Tests\TestCase;

class UserDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_chip_maps_all_statuses(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->assertSame(['label' => 'Active', 'tone' => 'ok'], $user->statusChip());

        $user->update(['status' => 'pending']);
        $this->assertSame(['label' => 'Pending', 'tone' => 'warn'], $user->fresh()->statusChip());

        $user->update(['status' => 'suspended']);
        $this->assertSame(['label' => 'Suspended', 'tone' => 'danger'], $user->fresh()->statusChip());
    }

    public function test_member_number_is_zero_padded_to_six_digits(): void
    {
        $user = User::factory()->create();
        $this->assertSame('Member #' . str_pad((string) $user->id, 6, '0', STR_PAD_LEFT), $user->memberNumber());
    }

    public function test_phone_is_fillable_and_saved(): void
    {
        $user = User::factory()->create();
        $user->update(['phone' => '+33 6 12 34 56 78']);
        $this->assertSame('+33 6 12 34 56 78', $user->fresh()->phone);
    }
}
