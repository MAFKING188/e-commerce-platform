<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Modules\IdentityAccess\Models\User;
use App\Models\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_update_saves_all_primary_address_fields(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)->put('/profile/update', [
            'name' => $user->name,
            'email' => $user->email,
            'line1' => 'Luxury Street 12',
            'line2' => 'Apt 4B',
            'city' => 'Milan',
            'state' => 'Lombardy',
            'zip' => '20121',
            'country' => 'Italy',
        ])->assertRedirect();

        $address = Address::where('user_id', $user->id)->where('is_primary', true)->first();
        $this->assertNotNull($address);
        $this->assertSame('Luxury Street 12', $address->line1);
        $this->assertSame('Apt 4B', $address->line2);
        $this->assertSame('Milan', $address->city);
        $this->assertSame('Lombardy', $address->state);
        $this->assertSame('20121', $address->zip);
        $this->assertSame('Italy', $address->country);
    }

    public function test_profile_update_without_address_fields_leaves_address_untouched(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        Address::create([
            'user_id' => $user->id,
            'is_primary' => true,
            'line1' => 'Existing Street 1',
            'city' => 'Rome',
            'country' => 'Italy',
        ]);

        $this->actingAs($user)->put('/profile/update', [
            'name' => $user->name,
            'email' => $user->email,
        ])->assertRedirect();

        $this->assertDatabaseHas('addresses', [
            'user_id' => $user->id,
            'line1' => 'Existing Street 1',
            'city' => 'Rome',
        ]);
    }
}