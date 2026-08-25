<?php

namespace Modules\MarketplacePipeline\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentRejected extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $reason;

    /**
     * Create a new message.
     *
     * @param  mixed  $order
     * @param  string  $reason
     */
    public function __construct($order, $reason)
    {
        $this->order = $order;
        $this->reason = $reason;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("Action needed: Payment proof rejected for Order #{$this->order->id}")
            ->view('marketplacepipeline::emails.rejected')
            ->with(['order' => $this->order, 'reason' => $this->reason]);
    }
}