<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\CatalogDelivery\Database\Seeders\CatalogInventory;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('products')->exists()) {
            return;
        }

        DB::table('products')->orderBy('id')->get()->each(function ($product) {
            $url = CatalogInventory::imageFor($product->name)
                ?? "https://picsum.photos/seed/" . \Illuminate\Support\Str::slug($product->name) . "/800/600";

            $existing = DB::table('product_images')->where('product_id', $product->id)->first();
            if ($existing) {
                DB::table('product_images')->where('id', $existing->id)->update(['url' => $url]);
            } else {
                DB::table('product_images')->insert([
                    'product_id' => $product->id,
                    'url' => $url,
                    'position' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
    }
};