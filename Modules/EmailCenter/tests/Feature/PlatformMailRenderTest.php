<?php

namespace Modules\EmailCenter\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\EmailCenter\Mail\PlatformMail;
use Modules\IdentityAccess\Models\User;
use Tests\TestCase;

class PlatformMailRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_mail_renders_markdown_and_preserves_subject(): void
    {
        Mail::fake();

        $user = User::factory()->create(['name' => 'Ada']);

        Mail::to($user->email)->send(new \Modules\EmailCenter\Mail\PlatformMail(
            'Hi {name}',
            '**bold** text',
            $user->name
        ));

        Mail::assertQueued(PlatformMail::class, function ($mail) use ($user) {
            // The subject is passed as-is (placeholder replacement happens at send time)
            $this->assertEquals('Hi {name}', $mail->envelope()->subject);
            // Body markdown should contain the rendered HTML via content() method
            $content = $mail->content();
            $this->assertStringContainsString('<strong>bold</strong>', $content->with['body']);
            return true;
        });
    }

    public function test_mail_uses_correct_view(): void
    {
        Mail::fake();

        $user = User::factory()->create();

        Mail::to($user->email)->send(new \Modules\EmailCenter\Mail\PlatformMail(
            'Test Subject',
            'Test body',
            $user->name
        ));

        Mail::assertQueued(PlatformMail::class, function ($mail) {
            $this->assertEquals('emailcenter::emails.platform', $mail->content()->markdown);
            return true;
        });
    }
}