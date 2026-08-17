<?php

namespace Modules\TelemetryPipeline\Tests\Feature;

use Modules\IdentityAccess\Models\User;
use Modules\CatalogDelivery\Models\Product;
use Modules\PartnerHub\Models\Partner;
use Modules\TelemetryPipeline\Models\AuditLog;
use Modules\TelemetryPipeline\Models\EmailLog;
use Modules\TelemetryPipeline\Mail\LowStockAlert;
use Modules\TelemetryPipeline\Services\LowStockAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\TelemetryPipeline\Tests\TestCase;

class TelemetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_written_on_user_approve(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $user = User::factory()->create(['role' => 'partner', 'status' => 'pending']);

        $this->actingAs($admin)->post("/admin/users/{$user->id}/approve");

        $this->assertSame(1, AuditLog::where('action', 'admin.users.approve')->count());
    }

    public function test_email_log_written_on_mail_send(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Mail::raw('test body', fn ($message) => $message->to($user->email)->subject('Test'));

        $this->assertSame(1, EmailLog::where('recipient', $user->email)->count());
    }

    public function test_low_stock_alert_queued(): void
    {
        Mail::fake();

        $user = User::factory()->create(['role' => 'partner', 'status' => 'active']);
        $partner = Partner::create(['name' => 'Atelier', 'user_id' => $user->id]);
        $product = Product::factory()->create(['stock' => 2]);
        $product->partners()->attach($partner->id);

        (new LowStockAlertService)->check($product);

        Mail::assertQueued(LowStockAlert::class);
    }
}