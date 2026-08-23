<?php

namespace Modules\CatalogDelivery\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\CatalogDelivery\Models\Product;
use Modules\CatalogDelivery\Services\CatalogCache;
use Modules\CatalogDelivery\Services\CatalogQueryService;
use Tests\TestCase;

class CatalogCacheTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Modules\CatalogDelivery\Database\Seeders\CategorySeeder::class);
        CatalogCache::flush();
    }

    public function test_home_is_cached_until_flushed(): void
    {
        $service = new CatalogQueryService();

        Product::factory()->create(['name' => 'First Pass Piece']);
        $this->assertStringContainsString('First Pass Piece', print_r($service->home(), true));

        // A later product does NOT appear while the cache is warm.
        Product::factory()->create(['name' => 'Second Pass Piece']);
        $this->assertStringNotContainsString('Second Pass Piece', print_r($service->home(), true));

        CatalogCache::flush();
        $this->assertStringContainsString('Second Pass Piece', print_r($service->home(), true));
    }

    public function test_related_cache_is_per_product(): void
    {
        $a = Product::factory()->create();
        $b = Product::factory()->create();

        $service = new CatalogQueryService();
        $first = $service->related($a);

        Cache::shouldReceive('forget')->once()->with('catalog:home');
        Cache::shouldReceive('forget')->once()->with('catalog:collection');
        Cache::shouldReceive('forget')->once()->with('catalog:related:' . $a->id);
        CatalogCache::flush($a->id);
    }
}