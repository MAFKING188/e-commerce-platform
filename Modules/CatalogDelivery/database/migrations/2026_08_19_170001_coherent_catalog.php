<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\CatalogDelivery\Database\Seeders\CatalogInventory;
use Modules\CatalogDelivery\Models\Category;
use Modules\CatalogDelivery\Models\Product;
use Modules\CatalogDelivery\Models\ProductImage;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('categories')->exists()) {
            return;
        }

        $junkCategories = Category::whereNotIn('name', array_keys(CatalogInventory::CATALOG))->pluck('id');
        $home = Category::where('name', 'Home & Kitchen')->first();

        if ($home && $junkCategories->isNotEmpty()) {
            DB::table('products')->whereIn('category_id', $junkCategories)->update(['category_id' => $home->id]);
            Category::whereIn('id', $junkCategories)->delete();
        }

        foreach (CatalogInventory::CATALOG as $catName => $items) {
            $category = Category::where('name', $catName)->first();
            if (! $category) {
                continue;
            }

            $names = CatalogInventory::namesFor($catName);

            DB::table('products')
                ->where('category_id', $category->id)
                ->orderBy('id')
                ->get()
                ->each(function ($product, $i) use ($names) {
                    DB::table('products')->where('id', $product->id)->update(['name' => $names[$i % count($names)]]);
                });
        }

        foreach (['Beauty & Wellness', 'Sports & Outdoors', 'Toys & Games'] as $catName) {
            if (Category::where('name', $catName)->exists()) {
                continue;
            }

            $category = Category::create(['name' => $catName]);

            foreach (CatalogInventory::CATALOG[$catName] as [$name, $imageIdx]) {
                $product = Product::create([
                    'name' => $name,
                    'price' => rand(300, 3500),
                    'category_id' => $category->id,
                    'stock' => rand(10, 50),
                    'description' => "Experience the pinnacle of LUWI craftsmanship. The {$name} is a masterclass in modern design.",
                ]);

                ProductImage::create([
                    'product_id' => $product->id,
                    'url' => "https://images.unsplash.com/" . CatalogInventory::IMAGES[$imageIdx] . "?auto=format&fit=crop&w=800&q=80",
                ]);
            }
        }
    }

    public function down(): void
    {
    }
};