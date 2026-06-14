<?php

namespace App\Http\Controllers;

use App\Models\Payout;
use Illuminate\Http\Request;

class AdminPayoutController extends Controller
{
    public function index()
    {
        $payouts = Payout::with(['partner', 'order'])->latest()->paginate(15);
        return view('admin.payouts.index', compact('payouts'));
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

        return back()->with('success', 'Payout processed successfully.');
    }
}
