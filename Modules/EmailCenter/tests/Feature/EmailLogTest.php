<?php

namespace Modules\EmailCenter\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CatalogDelivery\Models\Product;
use Modules\EmailCenter\Models\EmailLog;
use Modules\IdentityAccess\Models\User;
use Modules\MarketplacePipeline\Models\Order;
use Modules\PartnerHub\Models\Partner;
use Tests\TestCase;

class EmailLogTest extends TestCase
{
    use RefreshDatabase;

    private function logFor(User $sender, string $role, string $subject = 'Subject', string $status = 'sent'): EmailLog
    {
        return EmailLog::create([
            'batch_id' => 'batch-' . uniqid(),
            'sender_user_id' => $sender->id,
            'sender_role' => $role,
            'recipient_email' => 'recipient-' . uniqid() . '@test.com',
            'subject' => $subject,
            'body_markdown' => 'Body',
            'status' => $status,
        ]);
    }

    public function test_admin_sees_all_logs(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $partnerUser = User::factory()->create(['role' => 'partner', 'status' => 'active']);

        $this->logFor($admin, 'admin', 'Admin blast');
        $this->logFor($partnerUser, 'partner', 'Partner note');

        $response = $this->actingAs($admin)->get(route('admin.email.logs'));
        $response->assertOk()
            ->assertSee('Admin blast')
            ->assertSee('Partner note');
    }

    public function test_partner_sees_own_logs_only(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $partnerA = User::factory()->create(['role' => 'partner', 'status' => 'active']);
        $partnerB = User::factory()->create(['role' => 'partner', 'status' => 'active']);

        $this->logFor($partnerA, 'partner', 'A note');
        $this->logFor($partnerB, 'partner', 'B note');

        $this->actingAs($partnerA)
            ->get(route('partner.email.logs'))
            ->assertOk()
            ->assertSee('A note')
            ->assertDontSee('B note');

        // Admin's logs page is not accessible to partners
        $this->actingAs($partnerA)
            ->get(route('admin.email.logs'))
            ->assertRedirect(route('home'));
    }

    public function test_admin_status_filter_works(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->logFor($admin, 'admin', 'Sent one', 'sent');
        $this->logFor($admin, 'admin', 'Failed one', 'failed');

        $this->actingAs($admin)
            ->get(route('admin.email.logs', ['status' => 'failed']))
            ->assertOk()
            ->assertSee('Failed one')
            ->assertDontSee('Sent one');
    }

    public function test_admin_search_by_subject(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->logFor($admin, 'admin', 'Newsletter digest');
        $this->logFor($admin, 'admin', 'Order update');

        $this->actingAs($admin)
            ->get(route('admin.email.logs', ['q' => 'Newsletter']))
            ->assertOk()
            ->assertSee('Newsletter digest')
            ->assertDontSee('Order update');
    }
}