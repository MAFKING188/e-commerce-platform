<?php

namespace Modules\PartnerHub\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\PartnerHub\Models\Partner;
use Modules\PartnerHub\Models\VendorBankDetail;
use Illuminate\Support\Facades\Storage;

class VendorBankDetailController extends Controller
{
    protected function getPartner()
    {
        return Partner::where('user_id', auth()->id())->firstOrFail();
    }

    public function index()
    {
        $partner = $this->getPartner();
        $bankDetail = $partner->bankDetails;

        return view('partnerhub::vendor.bank-details.index', compact('partner', 'bankDetail'));
    }

    public function create()
    {
        $partner = $this->getPartner();
        $bankDetail = $partner->bankDetails;

        if ($bankDetail) {
            return redirect()->route('vendor.bank-details.edit', $bankDetail)
                ->with('status', 'Bank details already exist. You can edit them below.');
        }

        return view('partnerhub::vendor.bank-details.create', compact('partner'));
    }

    public function store(Request $request)
    {
        $partner = $this->getPartner();

        if ($partner->bankDetails) {
            return redirect()->route('vendor.bank-details.edit', $partner->bankDetails)
                ->withErrors('Bank details already exist. Please edit instead.');
        }

        $validated = $request->validate([
            'bank_details_image' => 'nullable|image|max:5000',
            'account_holder' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'swift_bic' => 'nullable|string|max:255',
            'additional_info' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('bank_details_image')) {
            $validated['bank_details_image'] = $request->file('bank_details_image')->store('vendor-bank-details', 'public');
        }

        $validated['partner_id'] = $partner->id;

        VendorBankDetail::create($validated);

        return redirect()->route('vendor.bank-details.index')
            ->with('status', 'Bank details saved successfully.');
    }

    public function edit(VendorBankDetail $bankDetail)
    {
        $partner = $this->getPartner();

        if ($bankDetail->partner_id !== $partner->id) {
            abort(403);
        }

        return view('partnerhub::vendor.bank-details.edit', compact('partner', 'bankDetail'));
    }

    public function update(Request $request, VendorBankDetail $bankDetail)
    {
        $partner = $this->getPartner();

        if ($bankDetail->partner_id !== $partner->id) {
            abort(403);
        }

        $validated = $request->validate([
            'bank_details_image' => 'nullable|image|max:5000',
            'account_holder' => 'nullable|string|max:255',
            'iban' => 'nullable|string|max:255',
            'bank_name' => 'nullable|string|max:255',
            'swift_bic' => 'nullable|string|max:255',
            'additional_info' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if ($request->hasFile('bank_details_image')) {
            // Delete old image
            if ($bankDetail->bank_details_image) {
                Storage::disk('public')->delete($bankDetail->bank_details_image);
            }
            $validated['bank_details_image'] = $request->file('bank_details_image')->store('vendor-bank-details', 'public');
        }

        $bankDetail->update($validated);

        return redirect()->route('vendor.bank-details.index')
            ->with('status', 'Bank details updated successfully.');
    }

    public function destroy(VendorBankDetail $bankDetail)
    {
        $partner = $this->getPartner();

        if ($bankDetail->partner_id !== $partner->id) {
            abort(403);
        }

        if ($bankDetail->bank_details_image) {
            Storage::disk('public')->delete($bankDetail->bank_details_image);
        }

        $bankDetail->delete();

        return redirect()->route('vendor.bank-details.index')
            ->with('status', 'Bank details deleted successfully.');
    }
}