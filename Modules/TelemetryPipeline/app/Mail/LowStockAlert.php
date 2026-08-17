<?php

namespace Modules\TelemetryPipeline\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\CatalogDelivery\Models\Product;

class LowStockAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Product $product)
    {
    }

    public function build(): self
    {
        return $this->subject('Low stock: ' . $this->product->name)
            ->view('telemetrypipeline::emails.low-stock');
    }
}