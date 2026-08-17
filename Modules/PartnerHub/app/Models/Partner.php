<?php

namespace Modules\PartnerHub\Models;

use Modules\MarketplacePipeline\Models\Order;
use Modules\MarketplacePipeline\Models\Payout;
use Illuminate\Database\Eloquent\Model;
use Modules\CatalogDelivery\Models\Product;
use Modules\IdentityAccess\Models\User;

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
        return Order::whereHas('items.product.partners', function ($q) {
            $q->where('partners.id', $this->id);
        });
    }
}
