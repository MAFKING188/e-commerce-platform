<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vendor extends Model
{
    protected $fillable = [
        'name',
        'description',
        'contact_info',
        'website'
    ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'vendor_products');
    }
}
