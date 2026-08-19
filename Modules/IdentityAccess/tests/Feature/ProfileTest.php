<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Modules\IdentityAccess\Models\User;
use App\Models\Address;
use Modules\CatalogDelivery\Models\Product;
use Modules\IdentityAccess\Models\Wishlist;
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

    public function test_address_only_submission_from_security_page_saves_address(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $this->actingAs($user)->put('/profile/update', [
            'line1' => '88 Boulevard Mohammed V',
            'line2' => 'Etage 2',
            'city' => 'Casablanca',
            'state' => 'Casablanca-Settat',
            'zip' => '20000',
            'country' => 'Morocco',
        ])->assertRedirect()
            ->assertSessionHasNoErrors();

        $address = Address::where('user_id', $user->id)->where('is_primary', true)->first();
        $this->assertNotNull($address);
        $this->assertSame('88 Boulevard Mohammed V', $address->line1);
        $this->assertSame('Casablanca', $address->city);
        $this->assertSame('Morocco', $address->country);
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

    public function test_avatar_upload_stores_file_and_sets_url(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->post('/profile/avatar', [
            'avatar' => \Illuminate\Http\UploadedFile::fake()->image('me.jpg', 200, 200),
        ]);

        $response->assertRedirect();
        $this->assertNotNull($user->fresh()->avatars);
        $this->assertStringContainsString('/storage/avatars/', $user->fresh()->avatarUrl());
    }

    public function test_avatar_upload_rejects_invalid_type(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->post('/profile/avatar', [
            'avatar' => \Illuminate\Http\UploadedFile::fake()->create('evil.txt', 10),
        ]);

        $response->assertSessionHasErrors('avatar');
        $this->assertNull($user->fresh()->avatars);
    }

    public function test_password_change_requires_current_password_and_persists_new_hash(): void
    {
        $user = User::factory()->create(['password' => 'current-pass-123']);

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'wrong',
            'password' => 'new-pass-456',
            'password_confirmation' => 'new-pass-456',
        ]);
        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('current-pass-123', $user->fresh()->password));

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'current-pass-123',
            'password' => 'new-pass-456',
            'password_confirmation' => 'new-pass-456',
        ])->assertRedirect();

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('new-pass-456', $user->fresh()->password));
    }

    public function test_profile_page_renders_identity_signals_stats_and_quick_links(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $product = Product::factory()->create(['stock' => 5]);
        \Modules\MarketplacePipeline\Models\Order::create(['user_id' => $user->id, 'total_price' => 620, 'status' => 'completed']);
        Address::create(['user_id' => $user->id, 'is_primary' => true, 'line1' => 'A', 'city' => 'B', 'country' => 'C']);
        Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk()
            ->assertSee('Collector', false)
            ->assertSee('profile-stats')
            ->assertSee('profile-timeline')
            ->assertSee('Identity')
            ->assertSee('Member #')
            ->assertSee('/orders')
            ->assertSee('/profile/security')
            ->assertSee('/profile/settings')
            ->assertDontSee('No acquisitions recorded yet.');
    }

    public function test_security_page_renders_address_and_password_forms_for_all_roles(): void
    {
        foreach (['user', 'admin', 'partner'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);
            $response = $this->actingAs($user)->get('/profile/security');
            $response->assertOk()
                ->assertSee('Street Address')
                ->assertSee('Current Password')
                ->assertSee('Address & Security');
        }
    }

    public function test_settings_page_renders_and_phone_is_saved_via_profile_update(): void
    {
        $user = User::factory()->create(['role' => 'partner', 'status' => 'active']);

        $this->actingAs($user)->get('/profile/settings')
            ->assertOk()
            ->assertSee('Full Name')
            ->assertSee('Email Address')
            ->assertSee('Phone');

        $this->actingAs($user)->put('/profile/update', [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '+33 6 11 22 33 44',
        ])->assertRedirect();

        $this->assertSame('+33 6 11 22 33 44', $user->fresh()->phone);
    }

    public function test_security_and_settings_require_authentication(): void
    {
        $this->get('/profile/security')->assertRedirect('/login');
        $this->get('/profile/settings')->assertRedirect('/login');
    }
}