<?php

namespace App\Http\Controllers;

use Modules\PartnerHub\Models\Partner;
use Modules\MarketplacePipeline\Models\Payout;
use Illuminate\Http\Request;

class PartnerPayoutController extends Controller
{
    public function index()
    {
        $partner = Partner::where('user_id', auth()->id())->firstOrFail();
        $payouts = $partner->payouts()->with('order')->latest()->paginate(15);
        
        $stats = [
            'total_earned' => $partner->payouts()->where('status', 'processed')->sum('amount'),
            'pending_payout' => $partner->payouts()->where('status', 'pending')->sum('amount')
        ];

        return view('partner.payouts.index', compact('payouts', 'stats'));
    }
}
