<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\CatalogDelivery\Database\Seeders\CatalogInventory;

/**
 * Self-host the curated catalog images: product_images.url switches from
 * hotlinked Unsplash URLs to local storage paths
 * (products/curated/<slug>.jpg — files shipped per environment).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('products')->exists()) {
            return;
        }

        DB::table('products')->orderBy('id')->get()->each(function ($product) {
            $target = CatalogInventory::imageFor($product->name);

            if (! $target) {
                return;
            }

            DB::table('product_images')
                ->where('product_id', $product->id)
                ->where('url', '!=', $target)
                ->update(['url' => $target]);
        });
    }

    public function down(): void
    {
        if (! DB::table('products')->exists()) {
            return;
        }

        DB::table('products')->orderBy('id')->get()->each(function ($product) {
            $source = CatalogInventory::sourceUrlFor($product->name);

            if (! $source) {
                return;
            }

            DB::table('product_images')
                ->where('product_id', $product->id)
                ->update(['url' => $source]);
        });
    }
};
