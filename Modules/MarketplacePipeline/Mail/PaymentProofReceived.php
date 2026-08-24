<?php

namespace Modules\MarketplacePipeline\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PaymentProofReceived extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $proofUrl;

    /**
     * Create a new message.
     *
     * @param  mixed  $order
     * @param  string  $proofUrl
     */
    public function __construct($order, $proofUrl = '')
    {
        $this->order = $order;
        $this->proofUrl = $proofUrl;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("Payment proof received for Order #{$this->order->id}")
            ->view('marketplacepipeline::emails.proof-received')
            ->with(['order' => $this->order, 'proofUrl' => $this->proofUrl]);
    }
}