<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /**
     * Centralized Image Resolution Logic
     * Handles: Absolute URLs, Local Storage, and Semantic Fallbacks
     */
    public function getImageUrlAttribute()
    {
        $firstImage = $this->images->first();

        if (!$firstImage || empty($firstImage->url)) {
            return 'https://images.unsplash.com/photo-1441984904996-e0b6ba687e12?w=800';
        }

        $url = $firstImage->url;

        if (str_starts_with($url, 'http')) {
            return $url;
        }

        // Standardize local path: strip any leading 'storage/' then re-add it via asset()
        $path = str_replace('storage/', '', ltrim($url, '/'));
        return asset('storage/' . $path);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function cartItem()
    {
        return $this->hasMany(CartItem::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function reviews()
    {
        return $this->HasMany(Review::class);
    }
}
