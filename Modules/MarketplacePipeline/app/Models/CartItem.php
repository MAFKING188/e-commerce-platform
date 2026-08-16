<?php

namespace Modules\MarketplacePipeline\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\CatalogDelivery\Models\Product;

class CartItem extends Model
{
    protected $fillable = [
    'cart_id',
    'product_id',
    'quantity'
];
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
