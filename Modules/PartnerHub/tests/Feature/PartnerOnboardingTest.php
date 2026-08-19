<?php

namespace Modules\PartnerHub\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PartnerHub\Models\Partner;
use Modules\PartnerHub\Tests\TestCase;
use Modules\IdentityAccess\Models\User;

class PartnerOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_signup_creates_partner_record(): void
    {
        $this->post('/createaccount', [
            'name' => 'New Artisan',
            'email' => 'newartisan@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+212 6 00 00 00 00',
            'country' => 'MA',
            'role' => 'partner'
        ]);

        $user = User::where('email', 'newartisan@test.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('partner', $user->role);
        $this->assertSame('pending', $user->status);

        $partner = Partner::where('user_id', $user->id)->first();
        $this->assertNotNull($partner, 'Partner record must be created at signup');
        $this->assertSame('New Artisan', $partner->name);
    }

    public function test_regular_signup_does_not_create_partner_record(): void
    {
        $this->post('/createaccount', [
            'name' => 'Regular Shopper',
            'email' => 'shopper@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'phone' => '+212 6 00 00 00 00',
            'country' => 'MA',
            'role' => 'user'
        ]);

        $user = User::where('email', 'shopper@test.com')->first();
        $this->assertNull(Partner::where('user_id', $user->id)->first());
    }

    public function test_admin_approval_of_existing_partner_user_creates_partner_record(): void
    {
        $user = User::create([
            'name' => 'Legacy Partner',
            'email' => 'legacy@test.com',
            'password' => bcrypt('password123'),
            'role' => 'partner',
            'status' => 'pending'
        ]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active'
        ]);

        $this->actingAs($admin)->post("/admin/users/{$user->id}/approve");

        $this->assertSame('active', $user->fresh()->status);
        $partner = Partner::where('user_id', $user->id)->first();
        $this->assertNotNull($partner, 'Approved partner must get a Partner record');
        $this->assertSame('Legacy Partner', $partner->name);
    }

    public function test_admin_role_change_to_partner_creates_partner_record(): void
    {
        $user = User::create([
            'name' => 'Promoted User',
            'email' => 'promoted@test.com',
            'password' => bcrypt('password123'),
            'role' => 'user',
            'status' => 'active'
        ]);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin2@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'status' => 'active'
        ]);

        $this->actingAs($admin)->put("/admin/users/{$user->id}", [
            'role' => 'partner',
            'status' => 'active'
        ]);

        $partner = Partner::where('user_id', $user->id)->first();
        $this->assertNotNull($partner, 'Role change to partner must create a Partner record');
        $this->assertSame('Promoted User', $partner->name);
    }
}