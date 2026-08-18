<?php

namespace Modules\PartnerHub\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\PartnerHub\Models\Partner;
use Illuminate\Http\Request;

class PartnerProfileController extends Controller
{
    public function show($id)
    {
        $partner = Partner::with('products.images')->findOrFail($id);

        return view('partnerhub::partner_profile', compact('partner'));
    }

    public function profile()
    {
        $partner = Partner::where('user_id', auth()->id())->firstOrFail();
        $user = auth()->user()->load(['orders', 'addresses', 'reviews', 'wishlists']);

        $inventoryCount = $partner->products()->count();
        $pendingPayout = $partner->payouts()->where('status', 'pending')->sum('amount');

        $stats = [
            'Pieces in catalog' => $inventoryCount,
            'Pending earnings' => '$' . number_format($pendingPayout, 2),
            'Archived pieces' => $user->wishlists->count(),
        ];

        return view('partnerhub::partner.profile.show', [
            'user' => $user,
            'partner' => $partner,
            'stats' => $stats,
            'timeline' => $user->activityTimeline(8),
        ]);
    }

    public function edit()
    {
        $partner = Partner::where('user_id', auth()->id())->firstOrFail();

        return view('partnerhub::partner.profile.edit', compact('partner'));
    }

    public function update(Request $request)
    {
        $partner = Partner::where('user_id', auth()->id())->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'website' => ['nullable', 'url', 'max:255'],
            'contact_info' => ['nullable', 'string', 'max:255'],
        ]);

        $partner->update($validated);

        return redirect()->route('partner.profile.edit')->with('status', 'Profile updated successfully');
    }
}
