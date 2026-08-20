<?php

namespace Modules\CatalogDelivery\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\CatalogDelivery\Models\Category;
use Modules\CatalogDelivery\Models\Product;
use Modules\CatalogDelivery\Models\ProductImage;

class ProductSeeder extends Seeder
{
    public function run()
    {
        foreach (CatalogInventory::CATALOG as $catName => $items) {
            $category = Category::updateOrCreate(['name' => $catName]);

            foreach ($items as [$name, $_]) {
                $product = Product::create([
                    'name' => $name,
                    'price' => rand(300, 3500),
                    'category_id' => $category->id,
                    'stock' => rand(10, 50),
                    'description' => "Experience the pinnacle of LUWI craftsmanship. The {$name} is a masterclass in modern design.",
                ]);

                ProductImage::create([
                    'product_id' => $product->id,
                    'url' => CatalogInventory::imageFor($name)
                        ?? "https://picsum.photos/seed/" . \Illuminate\Support\Str::slug($name) . "/800/600",
                ]);
            }
        }
    }
}