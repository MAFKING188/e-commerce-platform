<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\CatalogDelivery\Database\Seeders\CatalogInventory;

/**
 * Replace random seed pricing (a $2,999 toothbrush set) with realistic
 * per-category pricing. Deterministic per product name.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('products')->exists()) {
            return;
        }

        $categories = DB::table('categories')->pluck('id', 'name');

        DB::table('products')->orderBy('id')->get()->each(function ($product) use ($categories) {
            $categoryName = $categories->search($product->category_id);

            if ($categoryName === false) {
                return;
            }

            DB::table('products')
                ->where('id', $product->id)
                ->update(['price' => CatalogInventory::priceFor($categoryName, $product->name)]);
        });
    }

    public function down(): void
    {
        // Original prices were random; restoring them is meaningless.
    }
};
