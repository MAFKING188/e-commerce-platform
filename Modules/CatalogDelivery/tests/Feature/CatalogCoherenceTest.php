<?php

namespace Modules\CatalogDelivery\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CatalogDelivery\Database\Seeders\CatalogInventory;
use Modules\CatalogDelivery\Models\Category;
use Modules\CatalogDelivery\Models\Product;
use Tests\TestCase;

class CatalogCoherenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_catalog_has_only_coherent_categories(): void
    {
        $names = Category::pluck('name')->all();

        foreach (array_keys(CatalogInventory::CATALOG) as $expected) {
            $this->assertContains($expected, $names);
        }

        foreach ($names as $name) {
            $this->assertContains($name, array_keys(CatalogInventory::CATALOG), "Unexpected category: {$name}");
        }
    }

    public function test_every_product_name_is_unique(): void
    {
        $names = Product::pluck('name');

        $this->assertSame(Product::count(), $names->unique()->count());
    }

    public function test_no_numbered_placeholder_names(): void
    {
        $names = Product::pluck('name');

        foreach ($names as $name) {
            if (! preg_match('/ \d{1,2}$/', (string) $name)) {
                continue;
            }
            $base = preg_replace('/ \d{1,2}$/', '', (string) $name);
            $this->assertNotContains($base, $names, "Placeholder-style duplicate base: {$name}");
        }
    }

    public function test_each_category_has_at_least_fifteen_products(): void
    {
        foreach (array_keys(CatalogInventory::CATALOG) as $categoryName) {
            $count = Category::where('name', $categoryName)->first()->products()->count();
            $this->assertGreaterThanOrEqual(15, $count, "{$categoryName} has too few products");
        }
    }

    public function test_every_product_has_an_image(): void
    {
        foreach (Product::with('images')->get() as $product) {
            $this->assertNotEmpty($product->images, "No image for: {$product->name}");
        }
    }

    public function test_no_image_is_shared_across_categories(): void
    {
        $byUrl = Product::with(['category', 'images'])->get()
            ->flatMap(fn ($p) => $p->images->map(fn ($img) => [$p->category->name, $img->url]))
            ->groupBy(fn ($row) => $row[1]);

        foreach ($byUrl as $url => $rows) {
            $this->assertSame(1, $rows->pluck(0)->unique()->count(), "Image shared across categories: {$url}");
        }
    }

    public function test_images_point_to_verified_unsplash_urls(): void
    {
        foreach (Product::with('images')->get() as $product) {
            foreach ($product->images as $img) {
                $this->assertStringStartsWith('https://images.unsplash.com/', $img->url, "Non-Unsplash image: {$product->name}");
            }
        }
    }
}