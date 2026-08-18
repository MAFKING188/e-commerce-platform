<?php

namespace Modules\PartnerHub\Tests\Feature;

use Modules\IdentityAccess\Models\User;
use Modules\PartnerHub\Models\Partner;
use Modules\CatalogDelivery\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PartnerHub\Tests\TestCase;

class PartnerProfileShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_profile_view_renders_with_stats(): void
    {
        $user = User::factory()->create(['role' => 'partner', 'status' => 'active']);
        $partner = Partner::create([
            'user_id' => $user->id,
            'name' => 'Atelier Test',
            'description' => 'Handcrafted pieces',
            'contact_info' => 'contact@test.com',
            'website' => 'https://atelier-test.example',
        ]);
        $product = Product::factory()->create(['stock' => 5]);
        $partner->products()->attach($product->id);

        $response = $this->actingAs($user)->get('/partner/profile');

        $response->assertOk()
            ->assertSee('Atelier Test')
            ->assertSee('profile-stats')
            ->assertSee('artisan-profile')
            ->assertSee('profile-header')
            ->assertSee('contact@test.com')
            ->assertSee('atelier-test.example')
            ->assertSee('Identity')
            ->assertDontSee('No orders placed yet.');
    }

    public function test_partner_profile_denied_for_buyers(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $this->actingAs($user)->get('/partner/profile')->assertRedirect();
    }
}