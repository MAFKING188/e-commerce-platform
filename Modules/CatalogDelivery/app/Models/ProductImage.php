<?php

namespace Modules\CatalogDelivery\Models;

use Illuminate\Database\Eloquent\Model;
use Modules\CatalogDelivery\Services\ProductImageService;

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

    /**
     * Card-sized derivative for grid slots; falls back to the original
     * (or external URL) when no variant was generated.
     */
    public function getCardUrlAttribute(): string
    {
        if (str_starts_with($this->url, 'http')) {
            return $this->url;
        }

        $path = ltrim($this->url, '/');

        if (ProductImageService::variantExists($path)) {
            return asset('storage/' . ProductImageService::variantPathFor($path));
        }

        return $this->resolved_url;
    }
}