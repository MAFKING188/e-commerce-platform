<?php

namespace Modules\CatalogDelivery\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\CatalogDelivery\Mail\ContactMessageMail;
use Modules\CatalogDelivery\Models\ContactMessage;
use Modules\IdentityAccess\Models\User;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_submit_contact_inquiry_persisted_and_queued(): void
    {
        Mail::fake();

        $this->post('/contact', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'message' => 'I would love to know more about the Studio collection.',
        ])->assertRedirect('/contact');

        $this->assertDatabaseHas('contact_messages', [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'message' => 'I would love to know more about the Studio collection.',
        ]);

        $message = ContactMessage::first();
        $this->assertNotNull($message->ip_address);
        $this->assertNotNull($message->user_agent);

        Mail::assertQueued(ContactMessageMail::class, fn ($mail) => $mail->hasTo(config('shop.contact_email')));
    }

    public function test_contact_submission_requires_valid_fields(): void
    {
        $this->post('/contact', [
            'first_name' => '',
            'last_name' => '',
            'email' => 'not-an-email',
            'message' => 'short',
        ])->assertSessionHasErrors(['first_name', 'last_name', 'email', 'message']);

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_contact_page_renders_for_guest(): void
    {
        $this->get('/contact')
            ->assertOk()
            ->assertSee('Submit Inquiry');
    }

    public function test_admin_can_list_and_delete_contact_messages(): void
    {
        $message = ContactMessage::create([
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane@example.com',
            'message' => 'A thoughtful inquiry about the collection.',
        ]);

        $admin = User::factory()->create(['status' => 'active', 'role' => 'admin']);
        $this->actingAs($admin)
            ->get('/admin/contacts')
            ->assertOk()
            ->assertSee('Jane')
            ->assertSee('jane@example.com');

        $this->actingAs($admin)
            ->delete('/admin/contacts/' . $message->id)
            ->assertRedirect();
        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_contact_admin_routes_are_gated_to_admins(): void
    {
        $user = User::factory()->create(['status' => 'active', 'role' => 'user']);
        $this->actingAs($user)->get('/admin/contacts')->assertRedirect();
    }

    public function test_contact_message_mail_reply_to_uses_valid_rfc2822_address(): void
    {
        $message = ContactMessage::create([
            'first_name' => 'Prod',
            'last_name' => 'QA',
            'email' => 'qa-prod@example.com',
            'message' => 'Address validation regression check.',
        ]);

        $mail = new ContactMessageMail($message);
        $replyTo = $mail->envelope()->replyTo;

        $this->assertCount(1, $replyTo);
        $this->assertSame('qa-prod@example.com', $replyTo[0]->address);
        $this->assertSame('Prod QA', $replyTo[0]->name);

        $rendered = $mail->render();
        $this->assertStringContainsString('Prod QA', $rendered);
    }
}