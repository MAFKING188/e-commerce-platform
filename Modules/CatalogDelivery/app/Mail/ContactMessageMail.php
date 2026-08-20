<?php

namespace Modules\CatalogDelivery\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\CatalogDelivery\Models\ContactMessage;

class ContactMessageMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public ContactMessage $contactMessage;

    public function __construct(ContactMessage $contactMessage)
    {
        $this->contactMessage = $contactMessage;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Contact Inquiry: ' . $this->contactMessage->full_name,
            replyTo: [new Address($this->contactMessage->email, $this->contactMessage->full_name)],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'catalogdelivery::emails.contact-message',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}