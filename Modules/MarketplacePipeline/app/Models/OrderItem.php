<?php

namespace Modules\MarketplacePipeline\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\CatalogDelivery\Models\Product;

class OrderItem extends Model
{
    protected $fillable = [
    'order_id',
    'product_id',
    'quantity',
    'price'
];
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
