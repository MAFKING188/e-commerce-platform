<?php

namespace Modules\MarketplacePipeline\Mail;

use Modules\MarketplacePipeline\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderCompleted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Order Is Complete | LUWI Collection',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'marketplacepipeline::emails.orders.completed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
