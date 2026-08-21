<?php

namespace Modules\EmailCenter\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\EmailCenter\Mail\PlatformMail;
use Modules\IdentityAccess\Models\User;
use Tests\TestCase;

class AdminSendTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    public function test_newsletter_group_sends_only_to_opted_in_users_and_logs(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $optedIn = User::factory()->create(['status' => 'active', 'email_verified_at' => now(), 'newsletter_optin' => true, 'name' => 'Opted In']);
        User::factory()->create(['status' => 'active', 'email_verified_at' => now(), 'newsletter_optin' => false]);

        $this->actingAs($admin)
            ->post(route('admin.email.send'), [
                'subject' => 'Hello {name}',
                'body' => 'Dear {name} <{email}>',
                'group' => 'newsletter',
            ])
            ->assertRedirect();

        Mail::assertQueued(PlatformMail::class, 1);
        Mail::assertQueued(PlatformMail::class, fn ($m) => $m->envelope()->subject === 'Hello Opted In');

        $log = \Modules\EmailCenter\Models\EmailLog::first();
        $this->assertNotNull($log);
        $this->assertEquals('sent', $log->status);
        $this->assertEquals($optedIn->email, $log->recipient_email);
        $this->assertStringContainsString($optedIn->name, $log->subject);
        $this->assertEquals('admin', $log->sender_role);
    }

    public function test_individual_user_ids_send(): void
    {
        Mail::fake();
        $admin = $this->admin();
        $target = User::factory()->create(['status' => 'active', 'email_verified_at' => now()]);

        $this->actingAs($admin)
            ->post(route('admin.email.send'), [
                'subject' => 'Direct',
                'body' => 'Body text',
                'user_ids' => [$target->id],
            ])
            ->assertRedirect();

        Mail::assertQueued(PlatformMail::class, 1);
        $this->assertDatabaseHas('email_center_logs', ['recipient_email' => $target->email, 'batch_id' => \Modules\EmailCenter\Models\EmailLog::first()->batch_id]);
    }

    public function test_cap_rejects_more_than_100_user_ids(): void
    {
        Mail::fake();
        $admin = $this->admin();
        // 101 existing users would be heavy; validation max:100 triggers before resolution
        $ids = range(1, 101);

        $this->actingAs($admin)
            ->post(route('admin.email.send'), [
                'subject' => 'Cap',
                'body' => 'Body',
                'user_ids' => $ids,
            ])
            ->assertSessionHasErrors('user_ids');

        Mail::assertNothingSent();
    }

    public function test_non_admin_is_redirected_home(): void
    {
        Mail::fake();
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)
            ->post(route('admin.email.send'), ['subject' => 'x', 'body' => 'y', 'group' => 'all'])
            ->assertRedirect(route('home'));

        Mail::assertNothingSent();
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->post(route('admin.email.send'), ['subject' => 'x', 'body' => 'y'])
            ->assertRedirect(route('login'));
    }

    public function test_requires_group_or_individuals(): void
    {
        Mail::fake();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->post(route('admin.email.send'), [
                'subject' => 'No recipients',
                'body' => 'Body',
            ])
            ->assertSessionHasErrors('group');

        Mail::assertNothingSent();
    }

    public function test_search_endpoint_returns_matching_active_verified_users(): void
    {
        $admin = $this->admin();
        $match = User::factory()->create(['name' => 'Zelda Hunt', 'status' => 'active', 'email_verified_at' => now()]);
        User::factory()->create(['name' => 'Nobody Here', 'status' => 'active', 'email_verified_at' => now()]);
        User::factory()->create(['name' => 'Zelda Pending', 'status' => 'pending', 'email_verified_at' => now()]);

        $response = $this->actingAs($admin)->getJson(route('admin.users.search', ['q' => 'Zelda']));
        $response->assertOk();

        $emails = collect($response->json())->pluck('email');
        $this->assertTrue($emails->contains($match->email));
        $this->assertCount(1, $emails);
    }
}