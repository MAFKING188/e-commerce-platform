<?php

namespace Modules\MarketplacePipeline\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentValidated extends Mailable
{
    use Queueable, SerializesModels;

    public $order;

    /**
     * Create a new message.
     *
     * @param  mixed  $order
     */
    public function __construct($order)
    {
        $this->order = $order;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("Payment approved — Order #{$this->order->id} confirmed")
            ->view('marketplacepipeline::emails.validated')
            ->with(['order' => $this->order]);
    }
}