<?php

namespace Modules\MarketplacePipeline\Services;

use Modules\MarketplacePipeline\Models\Order;
use Modules\MarketplacePipeline\Models\Payout;

class PayoutService
{
    public function settle(Order $order): void
    {
        $order->load('items.product.partners');

        $partnerItems = [];
        foreach ($order->items as $item) {
            $partners = $item->product->partners;
            if ($partners->isEmpty()) {
                continue;
            }
            $lineValue = $item->price * $item->quantity;
            $share = $lineValue / $partners->count();
            foreach ($partners as $partner) {
                $partnerItems[$partner->id] = ($partnerItems[$partner->id] ?? 0) + $share;
            }
        }

        foreach ($partnerItems as $partnerId => $grossAmount) {
            $netAmount = $grossAmount * (1 - config('shop.commission_rate'));

            Payout::updateOrCreate(
                ['order_id' => $order->id, 'partner_id' => $partnerId],
                ['amount' => $netAmount, 'status' => 'pending']
            );
        }
    }
}
