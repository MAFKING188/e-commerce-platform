<?php

namespace Modules\IdentityAccess\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\IdentityAccess\Models\User;

class PasswordChangedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Password Was Changed | LUWI Collection',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'identityaccess::emails.password.changed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}