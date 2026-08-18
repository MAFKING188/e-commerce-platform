<?php

namespace Modules\CatalogDelivery\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CatalogDelivery\Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_legal_pages_render(): void
    {
        foreach (['privacy', 'terms', 'shipping', 'returns'] as $page) {
            $this->get('/' . $page)
                ->assertOk()
                ->assertSee('legal-hero');
        }
    }

    public function test_unknown_legal_page_404s(): void
    {
        $this->get('/privacy-policy-extra')->assertNotFound();
    }

    public function test_footer_links_to_all_legal_pages(): void
    {
        $response = $this->get('/');
        foreach (['/privacy', '/terms', '/shipping', '/returns'] as $url) {
            $response->assertSee($url, false);
        }
    }
}