<?php

namespace Modules\CatalogDelivery\Models;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    protected $fillable = ['product_id', 'url'];

    public function getResolvedUrlAttribute(): string
    {
        if (str_starts_with($this->url, 'http')) {
            return $this->url;
        }

        return asset('storage/' . str_replace('storage/', '', ltrim($this->url, '/')));
    }
}
