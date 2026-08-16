<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\CatalogDelivery\Models\Product;

class Partner extends Model
{
    protected $fillable = [
        'name',
        'description',
        'contact_info',
        'website',
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'partner_products');
    }

    public function payouts()
    {
        return $this->hasMany(Payout::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_items', 'product_id', 'order_id')
                    ->join('partner_products', 'order_items.product_id', '=', 'partner_products.product_id')
                    ->where('partner_products.partner_id', $this->id)
                    ->distinct();
    }
}
