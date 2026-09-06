<?php

namespace Modules\MarketplacePipeline\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\IdentityAccess\Models\User;

class Cart extends Model
{
    protected $table = 'carts';
    protected $fillable = [
        'user_id',
        'share_token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function (Cart $cart) {
            if (empty($cart->share_token)) {
                $cart->share_token = Str::uuid()->toString();
            }
        });
    }
}
