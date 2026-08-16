<?php

namespace Modules\MarketplacePipeline\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\PartnerHub\Models\Partner;

class Payout extends Model
{
    protected $fillable = [
        'partner_id',
        'order_id',
        'amount',
        'status',
        'transaction_reference',
        'processed_at'
    ];

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
