<?php

namespace Modules\CatalogDelivery\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CatalogDelivery\Models\Product;
use Tests\TestCase;

class CollectionPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_collection_page_lists_every_category_as_a_section(): void
    {
        $this->get('/collection')
            ->assertOk()
            ->assertSee('id="electronics"', false)
            ->assertSee('id="beauty-wellness"', false)
            ->assertSee('Beauty &amp; Wellness', false);
    }

    public function test_collection_shows_all_products(): void
    {
        Product::factory()->count(12)->create();

        $response = $this->get('/collection');
        $response->assertOk();
        foreach (Product::pluck('name')->all() as $name) {
            $this->assertStringContainsString(e($name), $response->getContent());
        }
    }

    public function test_footer_links_point_to_homepage_sections(): void
    {
        $this->get('/')
            ->assertSee('href="' . route('home') . '#new-arrivals"', false)
            ->assertSee('href="' . route('home') . '#editor-choice"', false);
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