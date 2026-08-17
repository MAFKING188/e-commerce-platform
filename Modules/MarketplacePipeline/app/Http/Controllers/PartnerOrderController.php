<?php

namespace Modules\MarketplacePipeline\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\PartnerHub\Models\Partner;
use Modules\MarketplacePipeline\Models\Order;
use Illuminate\Http\Request;

class PartnerOrderController extends Controller
{
    protected function getPartner()
    {
        return Partner::where('user_id', auth()->id())->firstOrFail();
    }

    public function index()
    {
        $partner = $this->getPartner();
        
        // Fetch orders that contain at least one item owned by this partner
        $orders = Order::whereHas('items.product.partners', function($q) use ($partner) {
            $q->where('partners.id', $partner->id);
        })->latest()->paginate(10);

        return view('marketplacepipeline::partner.orders.index', compact('orders'));
    }

    public function show($id)
    {
        $partner = $this->getPartner();
        
        // Ensure the order actually contains items from this partner
        $order = $partner->orders()->where('orders.id', $id)->firstOrFail();
        
        return view('marketplacepipeline::partner.orders.show', compact('order'));
    }
}
