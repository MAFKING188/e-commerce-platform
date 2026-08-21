<?php

namespace Modules\EmailCenter\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class PlatformMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        protected string $mailSubject,
        protected string $bodyMarkdown,
        protected string $recipientName
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emailcenter::emails.platform',
            with: [
                'body' => Str::markdown($this->bodyMarkdown),
                'name' => $this->recipientName,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}