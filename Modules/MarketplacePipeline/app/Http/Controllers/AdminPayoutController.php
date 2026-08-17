<?php

namespace Modules\MarketplacePipeline\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\MarketplacePipeline\Models\Payout;
use Modules\TelemetryPipeline\Services\TelemetryService;
use Illuminate\Http\Request;

class AdminPayoutController extends Controller
{
    public function index()
    {
        $payouts = Payout::with(['partner', 'order'])->latest()->paginate(15);
        return view('marketplacepipeline::admin.payouts.index', compact('payouts'));
    }

    public function process(Request $request, $id)
    {
        $payout = Payout::findOrFail($id);
        
        $request->validate([
            'transaction_reference' => 'required|string|max:100'
        ]);

        $payout->update([
            'status' => 'processed',
            'transaction_reference' => $request->transaction_reference,
            'processed_at' => now()
        ]);

        (new TelemetryService)->log('admin.payouts.process', ['payout_id' => $id]);

        return back()->with('success', 'Payout processed successfully.');
    }
}
