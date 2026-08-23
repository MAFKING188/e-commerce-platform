<?php

namespace Modules\TelemetryPipeline\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Models\User;
use Modules\TelemetryPipeline\Models\AuditLog;
use Modules\TelemetryPipeline\Models\EmailLog;
use Tests\TestCase;

class TelemetryViewerTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'status' => 'active']);
    }

    public function test_admin_sees_audit_trail_with_actor_and_metadata(): void
    {
        $admin = $this->admin();
        AuditLog::create([
            'actor_id' => $admin->id,
            'action' => 'admin.users.update',
            'metadata' => ['user_id' => 42],
            'ip' => '10.0.0.1',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index'))
            ->assertOk()
            ->assertSee($admin->name)
            ->assertSee('admin.users.update')
            ->assertSee('user_id: 42');
    }

    public function test_audit_trail_filters_by_action(): void
    {
        $admin = $this->admin();
        AuditLog::create(['actor_id' => $admin->id, 'action' => 'admin.users.destroy', 'metadata' => null, 'ip' => null]);
        AuditLog::create(['actor_id' => $admin->id, 'action' => 'admin.orders.complete', 'metadata' => null, 'ip' => null]);

        $this->actingAs($admin)
            ->get(route('admin.audit-logs.index', ['q' => 'destroy']))
            ->assertOk()
            ->assertSee('admin.users.destroy')
            ->assertDontSee('admin.orders.complete');
    }

    public function test_admin_sees_outbound_mail_log_with_status_filter(): void
    {
        $admin = $this->admin();
        EmailLog::create(['recipient' => 'buyer@test.com', 'subject' => 'Your LUWI verification code', 'status' => 'sent']);
        EmailLog::create(['recipient' => 'other@test.com', 'subject' => 'Order confirmed', 'status' => 'failed']);

        $page = $this->actingAs($admin)->get(route('admin.outbound-mail.index'));
        $page->assertOk()->assertSee('Your LUWI verification code')->assertSee('Order confirmed');

        $this->actingAs($admin)
            ->get(route('admin.outbound-mail.index', ['status' => 'failed']))
            ->assertOk()
            ->assertDontSee('Your LUWI verification code')
            ->assertSee('Order confirmed');
    }

    public function test_non_admin_is_redirected_home_from_both_viewers(): void
    {
        $member = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($member)->get(route('admin.audit-logs.index'))->assertRedirect(route('home'));
        $this->actingAs($member)->get(route('admin.outbound-mail.index'))->assertRedirect(route('home'));
    }
}