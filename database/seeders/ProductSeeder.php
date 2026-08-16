<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\CatalogDelivery\Models\Category;
use Modules\CatalogDelivery\Models\Product;
use Modules\CatalogDelivery\Models\ProductImage;

class ProductSeeder extends Seeder
{
    public function run()
    {
        // 1. Direct verified Unsplash URLs (No redirects, 100% Uptime)
        $inventory = [
            'Electronics' => [
                ['name' => 'Aether Pro Laptop', 'id' => 'photo-1496181133206-80ce9b88a853'],
                ['name' => 'Chronos Gold Watch', 'id' => 'photo-1523275335684-37898b6baf30'],
                ['name' => 'Zenith Studio Cam', 'id' => 'photo-1516035069371-29a1b244cc32'],
                ['name' => 'Nova Mobile 12', 'id' => 'photo-1511707171634-5f897ff02aa9'],
                ['name' => 'Vector Pods Max', 'id' => 'photo-1505740420928-5e560c06d30e'],
            ],
            'Clothing' => [
                ['name' => 'Imperial Silk Suit', 'id' => 'photo-1594932224010-77f3ad36bc3d'],
                ['name' => 'Vanguard Leather Boots', 'id' => 'photo-1549298916-b41d501d3772'],
                ['name' => 'Elysian Evening Gown', 'id' => 'photo-1539008835158-a3f2d226a26a'],
                ['name' => 'Nomad Leather Carryall', 'id' => 'photo-1584917865442-de89df76afd3'],
                ['name' => 'Aura Linen Set', 'id' => 'photo-1521572267360-ee0c2909d518'],
            ],
            'Home & Kitchen' => [
                ['name' => 'Nordic Pine Sofa', 'id' => 'photo-1555041469-a586c61ea9bc'],
                ['name' => 'Eclipse Sphere Lamp', 'id' => 'photo-1507473885765-e6ed057f782c'],
                ['name' => 'Studio Oak Chair', 'id' => 'photo-1592078615290-033ee584e267'],
                ['name' => 'Minimalist Coffee Maker', 'id' => 'photo-1517668808822-9ebb02f2a0e6'],
                ['name' => 'Ceramic Bloom Vase', 'id' => 'photo-1578500494198-246f612d3b3d'],
            ],
            'Books' => [
                ['name' => 'The Art of Minimalism', 'id' => 'photo-1589998059171-988d887df646'],
                ['name' => 'Architectural Digest', 'id' => 'photo-1507842217343-583bb7270b66'],
                ['name' => 'Luxury Living Vol. 1', 'id' => 'photo-1544947950-fa07a98d237f'],
            ],
        ];

        foreach ($inventory as $catName => $items) {
            $category = Category::where('name', $catName)->first();

            if (!$category) continue;

            // Generate 15 products for each of the 4 categories (Total 60)
            for ($i = 0; $i < 15; $i++) {
                $blueprint = $items[$i % count($items)];
                
                $product = Product::create([
                    'name' => "{$blueprint['name']} " . ($i + 1),
                    'price' => rand(300, 3500),
                    'category_id' => $category->id,
                    'stock' => rand(10, 50),
                    'description' => "Experience the pinnacle of LUWI craftsmanship. The {$blueprint['name']} is a masterclass in modern design."
                ]);

                // Direct high-res link
                ProductImage::create([
                    'product_id' => $product->id,
                    'url' => "https://images.unsplash.com/{$blueprint['id']}?auto=format&fit=crop&w=800&q=80"
                ]);
            }
        }
    }
}
