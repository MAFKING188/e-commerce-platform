<?php

namespace Modules\CatalogDelivery\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Generates a card-sized derivative (480px wide, same format) next to the
 * original on the `public` disk. Uses ext-gd directly — no external
 * dependency. Failure is non-fatal: callers keep serving the original.
 */
class ProductImageService
{
    public const CARD_WIDTH = 480;

    /** @var list<string> */
    private const SUPPORTED = ['image/jpeg', 'image/png', 'image/webp'];

    public static function makeCardVariant(string $relativePath): ?string
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($relativePath)) {
            return null;
        }

        $mime = $disk->mimeType($relativePath);
        if (! in_array($mime, self::SUPPORTED, true)) {
            return null;
        }

        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($disk->path($relativePath)),
            'image/png' => @imagecreatefrompng($disk->path($relativePath)),
            'image/webp' => @imagecreatefromwebp($disk->path($relativePath)),
            default => null,
        };

        if (! $source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);

        // Small originals don't need a variant.
        if ($width <= self::CARD_WIDTH) {
            imagedestroy($source);
            return null;
        }

        $cardHeight = (int) round($height * (self::CARD_WIDTH / $width));
        $card = imagecreatetruecolor(self::CARD_WIDTH, $cardHeight);

        if ($mime === 'image/png') {
            imagealphablending($card, false);
            imagesavealpha($card, true);
        } elseif ($mime === 'image/webp') {
            imagealphablending($card, false);
            imagesavealpha($card, true);
        }

        imagecopyresampled($card, $source, 0, 0, 0, 0, self::CARD_WIDTH, $cardHeight, $width, $height);

        $variantPath = self::variantPathFor($relativePath);
        $tmp = tmpfile();
        $tmpPath = stream_get_meta_data($tmp)['uri'];

        $saved = match ($mime) {
            'image/jpeg' => imagejpeg($card, $tmpPath, 82),
            'image/png' => imagepng($card, $tmpPath, 6),
            'image/webp' => imagewebp($card, $tmpPath, 82),
            default => false,
        };

        imagedestroy($card);
        imagedestroy($source);

        if (! $saved) {
            return null;
        }

        $disk->put($variantPath, file_get_contents($tmpPath));

        return $variantPath;
    }

    public static function variantExists(string $relativePath): bool
    {
        return Storage::disk('public')->exists(self::variantPathFor($relativePath));
    }

    public static function variantPathFor(string $relativePath): string
    {
        $info = pathinfo($relativePath);

        return ($info['dirname'] !== '.' ? $info['dirname'] . '/' : '') . $info['filename'] . '-card.' . ($info['extension'] ?? 'jpg');
    }
}