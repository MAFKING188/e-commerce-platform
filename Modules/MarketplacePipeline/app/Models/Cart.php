<?php

namespace Modules\MarketplacePipeline\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\IdentityAccess\Models\User;

class Cart extends Model
{
    protected $table = 'carts';
    protected $fillable = [
    'user_id'
];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }
}
