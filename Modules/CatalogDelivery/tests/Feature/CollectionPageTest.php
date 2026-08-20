<?php

namespace Modules\CatalogDelivery\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CatalogDelivery\Models\Product;
use Tests\TestCase;

class CollectionPageTest extends TestCase
{
    use RefreshDatabase;
    public function test_collection_page_renders_sections(): void
    {
        Product::factory()->count(10)->create();

        $this->get('/collection')
            ->assertOk()
            ->assertSee('New Arrivals.')
            ->assertSee('Featured Pieces.');
    }

    public function test_nav_collection_link_points_to_real_page(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('href="' . route('collection') . '"', false);
        $this->get(route('collection'))->assertOk();
    }

    public function test_footer_has_no_dead_hash_links(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $this->assertStringNotContainsString('href="#"', $response->getContent());
        $this->assertStringNotContainsString('href=\'#\'', $response->getContent());
    }
}