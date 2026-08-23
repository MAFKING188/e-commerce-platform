<?php

namespace Modules\CatalogDelivery\Services;

use Illuminate\Support\Facades\Cache;

/** Central invalidation for storefront query caches. Call after any catalog mutation. */
class CatalogCache
{
    public static function flush(?int $productId = null): void
    {
        Cache::forget('catalog:home');
        Cache::forget('catalog:collection');

        if ($productId !== null) {
            Cache::forget("catalog:related:{$productId}");
        }
    }
}