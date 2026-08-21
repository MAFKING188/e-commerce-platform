<?php

namespace Modules\EmailCenter\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\EmailCenter\Models\EmailTemplate;
use Modules\IdentityAccess\Models\User;
use Tests\TestCase;

class EmailTemplateCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_templates(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        EmailTemplate::factory()->count(3)->create();

        $this->actingAs($admin)
            ->get(route('admin.email-templates.index'))
            ->assertOk()
            ->assertSee('Email Templates');
    }

    public function test_admin_can_create_template(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->post(route('admin.email-templates.store'), [
                'name' => 'Test Template',
                'subject' => 'Test Subject',
                'body_markdown' => '# Hello {name}',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('email_templates', [
            'name' => 'Test Template',
            'created_by' => $admin->id,
        ]);
    }

    public function test_create_template_validation_errors(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->post(route('admin.email-templates.store'), [
                'name' => '',
                'subject' => '',
                'body_markdown' => '',
            ])
            ->assertSessionHasErrors(['name', 'subject', 'body_markdown']);
    }

    public function test_create_template_duplicate_name_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        EmailTemplate::create([
            'name' => 'Existing Template',
            'subject' => 'Subject',
            'body_markdown' => 'Body',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.email-templates.store'), [
                'name' => 'Existing Template',
                'subject' => 'Subject',
                'body_markdown' => 'Body',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_admin_can_edit_template(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $template = EmailTemplate::create([
            'name' => 'Original Name',
            'subject' => 'Original Subject',
            'body_markdown' => 'Original body',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.email-templates.update', $template->id), [
                'name' => 'Updated Name',
                'subject' => 'Updated Subject',
                'body_markdown' => 'Updated body',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseHas('email_templates', [
            'id' => $template->id,
            'name' => 'Updated Name',
            'subject' => 'Updated Subject',
            'body_markdown' => 'Updated body',
        ]);
    }

    public function test_admin_can_delete_template(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $template = EmailTemplate::create([
            'name' => 'To Delete',
            'subject' => 'Subject',
            'body_markdown' => 'Body',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.email-templates.destroy', $template->id))
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('email_templates', ['id' => $template->id]);
    }

    public function test_delete_template_keeps_log_rows(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $template = EmailTemplate::create([
            'name' => 'Template With Logs',
            'subject' => 'Subject',
            'body_markdown' => 'Body',
            'created_by' => $admin->id,
        ]);

        // Create a log entry referencing this template
        \Modules\EmailCenter\Models\EmailLog::create([
            'batch_id' => 'test-batch',
            'sender_user_id' => $admin->id,
            'sender_role' => 'admin',
            'recipient_email' => 'test@example.com',
            'template_id' => $template->id,
            'subject' => 'Test',
            'body_markdown' => 'Test body',
            'status' => 'sent',
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.email-templates.destroy', $template->id))
            ->assertRedirect();

        // Template deleted but log survives with template_id nulled
        $this->assertDatabaseMissing('email_templates', ['id' => $template->id]);
        $this->assertDatabaseHas('email_center_logs', [
            'batch_id' => 'test-batch',
            'template_id' => null,
        ]);
    }

    public function test_non_admin_cannot_access_template_routes(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($user)
            ->get(route('admin.email-templates.index'))
            ->assertRedirect(route('home'));

        $this->actingAs($user)
            ->post(route('admin.email-templates.store'), [])
            ->assertRedirect(route('home'));
    }
}