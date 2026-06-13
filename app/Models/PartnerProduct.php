<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerProduct extends Model
{
    protected $fillable = ['partner_id', 'product_id'];

    public function partner()
    {
        return $this->belongsTo(Partner::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
