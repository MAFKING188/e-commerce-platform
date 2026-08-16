<?php

namespace Modules\CatalogDelivery\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\IdentityAccess\Models\User;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'rating',
        'comment',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
