<?php

namespace Tests\Feature;

use Modules\IdentityAccess\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_submit_a_review_for_moderation(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);

        $this->actingAs($user)
            ->post('/reviews', [
                'product_id' => $product->id,
                'rating' => 5,
                'comment' => 'Exceptional piece.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'status' => 'pending',
        ]);
    }

    public function test_guest_cannot_submit_a_review(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        $this->post('/reviews', [
            'product_id' => $product->id,
            'rating' => 5,
        ])->assertRedirect('/login');
    }
}