<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\IdentityAccess\Mail\UserStatusUpdated;
use Modules\IdentityAccess\Models\User;
use Tests\TestCase;

class AdminUserEditTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_edit_page_for_member(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $member = User::factory()->create(['role' => 'user', 'status' => 'pending']);

        $this->actingAs($admin)
            ->get(route('admin.users.edit', $member->id))
            ->assertOk()
            ->assertSee($member->email)
            ->assertSee('Pending Confirmation');
    }

    public function test_edit_page_update_changes_role_and_status(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $member = User::factory()->create(['role' => 'user', 'status' => 'pending']);

        $this->actingAs($admin)
            ->put(route('admin.users.update', $member->id), [
                'role' => 'partner',
                'status' => 'active',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $member->id, 'role' => 'partner', 'status' => 'active']);
        $this->assertDatabaseHas('partners', ['user_id' => $member->id]);
        Mail::assertSent(UserStatusUpdated::class, 1);
    }

    public function test_non_admin_is_redirected_home(): void
    {
        $member = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($member)
            ->get(route('admin.users.edit', $member->id))
            ->assertRedirect(route('home'));
    }
}