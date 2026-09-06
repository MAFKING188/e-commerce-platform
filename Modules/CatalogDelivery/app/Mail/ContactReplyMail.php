<?php

namespace Modules\CatalogDelivery\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\CatalogDelivery\Models\ContactMessage;
use Modules\CatalogDelivery\Models\ContactReply;

class ContactReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public ContactMessage $contactMessage,
        public ContactReply $reply,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name', 'SmartShop')),
            subject: 'Re: Your inquiry to SmartShop',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'catalogdelivery::emails.contact-reply',
            with: [
                'customerName' => $this->contactMessage->full_name,
                'replyBody' => $this->reply->body,
                'adminName' => $this->reply->admin_name,
                'originalMessage' => $this->contactMessage->message,
                'originalDate' => $this->contactMessage->created_at->format('M d, Y'),
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
