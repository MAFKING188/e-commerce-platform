<?php

namespace Modules\MarketplacePipeline\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'partner_id',
        'transaction_id',
        'status',
        'amount',
        'method',
        'proof_path',
        'validated_at',
        'validated_by',
        'validation_notes'
    ];

    public function partner()
    {
        return $this->belongsTo(\Modules\PartnerHub\Models\Partner::class, 'partner_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
