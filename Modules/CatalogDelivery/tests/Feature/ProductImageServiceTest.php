<?php

namespace Modules\CatalogDelivery\Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Modules\CatalogDelivery\Services\ProductImageService;
use Tests\TestCase;

class ProductImageServiceTest extends TestCase
{
    private string $disk;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    /** Create a real JPEG on the fake disk using GD. */
    private function putTestImage(string $path, int $width, int $height): void
    {
        $img = imagecreatetruecolor($width, $height);
        imagefill($img, 0, 0, imagecolorallocate($img, 120, 40, 90));

        ob_start();
        imagejpeg($img, null, 85);
        $bytes = ob_get_clean();
        imagedestroy($img);

        Storage::disk('public')->put($path, $bytes);
    }

    public function test_generates_card_variant_for_large_image(): void
    {
        $this->putTestImage('products/big.jpg', 1000, 750);

        $variant = ProductImageService::makeCardVariant('products/big.jpg');

        $this->assertNotNull($variant);
        $this->assertSame(ProductImageService::variantPathFor('products/big.jpg'), $variant);
        Storage::disk('public')->assertExists($variant);

        [$w, $h] = getimagesizefromstring(Storage::disk('public')->get($variant));
        $this->assertSame(480, $w);
        $this->assertSame(360, $h);
    }

    public function test_skips_variant_for_small_originals(): void
    {
        $this->putTestImage('products/small.jpg', 300, 200);

        $this->assertNull(ProductImageService::makeCardVariant('products/small.jpg'));
        $this->assertFalse(ProductImageService::variantExists('products/small.jpg'));
    }

    public function test_returns_null_for_missing_files(): void
    {
        $this->assertNull(ProductImageService::makeCardVariant('products/ghost.jpg'));
    }
}