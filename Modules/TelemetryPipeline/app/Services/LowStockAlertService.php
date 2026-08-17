<?php

namespace Modules\TelemetryPipeline\Services;

use Modules\CatalogDelivery\Models\Product;
use Modules\IdentityAccess\Models\User;
use Modules\TelemetryPipeline\Mail\LowStockAlert;
use Illuminate\Support\Facades\Mail;

class LowStockAlertService
{
    public function check(Product $product): void
    {
        if ($product->stock >= (int) config('shop.low_stock_threshold', 5)) {
            return;
        }

        $recipients = $product->partners()
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->merge([User::where('role', 'admin')->first()])
            ->filter(fn ($user) => $user && $user->status === 'active');

        foreach ($recipients->unique('id') as $user) {
            Mail::to($user)->queue(new LowStockAlert($product));
        }
    }
}