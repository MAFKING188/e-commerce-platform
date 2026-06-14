<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
