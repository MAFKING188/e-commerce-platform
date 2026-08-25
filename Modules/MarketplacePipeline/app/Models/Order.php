<?php

namespace Modules\MarketplacePipeline\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\IdentityAccess\Models\User;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'total_price',
        'status',
        'recipient_name',
        'recipient_phone',
        'shipping_line1',
        'shipping_line2',
        'shipping_city',
        'shipping_state',
        'shipping_zip',
        'shipping_country',
        'delivery_notes',
    ];

    public function getShippingAddressAttribute(): string
    {
        $parts = array_filter([
            $this->shipping_line1,
            $this->shipping_line2,
            trim(($this->shipping_city ?? '') . ($this->shipping_state ? ', ' . $this->shipping_state : '')),
            implode(' - ', array_filter([$this->shipping_zip, $this->shipping_country])),
        ]);

        return implode(', ', $parts);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }
}
