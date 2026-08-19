<?php

namespace Modules\IdentityAccess\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\IdentityAccess\Models\User;

class PasswordResetMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;

    public $token;

    public function __construct(User $user, string $token)
    {
        $this->user = $user;
        $this->token = $token;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Reset Your Password | LUWI Collection',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'identityaccess::emails.password.reset',
            with: ['resetUrl' => url('/reset-password/' . $this->token)],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}