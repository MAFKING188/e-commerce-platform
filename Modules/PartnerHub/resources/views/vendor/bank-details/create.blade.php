@extends('partnerhub::layouts.app')

@section('title', 'Add Bank Details | Vendor Dashboard')

@section('content')
<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Financial Settings</span>
        <h1 class="pc-title">Add Bank Details</h1>
        <p class="pc-subtitle">Upload your bank details so customers can pay you directly.</p>
    </div>
</div>

<form action="{{ route('vendor.bank-details.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="pc-card">
        <div class="pc-card__head">
            <h2 class="pc-card__title">Bank Details Image</h2>
        </div>
        <div class="pc-card__body">
            <div class="pc-field">
                <label class="pc-label" for="bank_details_image">Bank Details Screenshot/Image</label>
                <input type="file" name="bank_details_image" id="bank_details_image" class="pc-input" accept="image/*">
                <p class="pc-hint">Upload a screenshot or photo of your bank details (IBAN, account holder, etc.). Max 5MB. Supported: JPG, PNG, GIF.</p>
                @error('bank_details_image')
                    <p class="pc-error">{{ $message }}</p>
                @enderror
            </div>

            @if($bankDetail && $bankDetail->bank_details_image)
                <div class="pc-field">
                    <label class="pc-label">Current Image</label>
                    <a href="{{ asset('storage/' . $bankDetail->bank_details_image) }}" target="_blank">
                        <img src="{{ asset('storage/' . $bankDetail->bank_details_image) }}" alt="Current Bank Details" class="pc-img-preview" style="max-width: 300px; border-radius: 8px;">
                    </a>
                </div>
            @endif
        </div>
    </div>

    <div class="pc-card" style="margin-top: 1.5rem;">
        <div class="pc-card__head">
            <h2 class="pc-card__title">Written Bank Details (Optional)</h2>
        </div>
        <div class="pc-card__body">
            <div class="pc-grid pc-grid--2">
                <div class="pc-field">
                    <label class="pc-label" for="account_holder">Account Holder Name</label>
                    <input type="text" name="account_holder" id="account_holder" class="pc-input" value="{{ old('account_holder', $bankDetail->account_holder ?? '') }}" placeholder="John Doe">
                </div>

                <div class="pc-field">
                    <label class="pc-label" for="iban">IBAN</label>
                    <input type="text" name="iban" id="iban" class="pc-input" value="{{ old('iban', $bankDetail->iban ?? '') }}" placeholder="MA64 1234 5678 9012 3456 7890">
                </div>

                <div class="pc-field">
                    <label class="pc-label" for="bank_name">Bank Name</label>
                    <input type="text" name="bank_name" id="bank_name" class="pc-input" value="{{ old('bank_name', $bankDetail->bank_name ?? '') }}" placeholder="Attijariwafa Bank">
                </div>

                <div class="pc-field">
                    <label class="pc-label" for="swift_bic">SWIFT/BIC</label>
                    <input type="text" name="swift_bic" id="swift_bic" class="pc-input" value="{{ old('swift_bic', $bankDetail->swift_bic ?? '') }}" placeholder="BCMAMAMC">
                </div>
            </div>

            <div class="pc-field" style="margin-top: 1rem;">
                <label class="pc-label" for="additional_info">Additional Instructions</label>
                <textarea name="additional_info" id="additional_info" class="pc-input" rows="3" placeholder="e.g., Include order number in reference...">{{ old('additional_info', $bankDetail->additional_info ?? '') }}</textarea>
            </div>
        </div>
    </div>

    <div class="pc-card" style="margin-top: 1.5rem;">
        <div class="pc-card__head">
            <h2 class="pc-card__title">Status</h2>
        </div>
        <div class="pc-card__body">
            <div class="pc-field pc-field--checkbox">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $bankDetail->is_active ?? true) ? 'checked' : '' }}>
                <label for="is_active" class="pc-label">Active - Customers can see these details at checkout</label>
            </div>
        </div>
    </div>

    <div class="pc-actions" style="margin-top: 1.5rem;">
        <button type="submit" class="btn btn-primary">Save Bank Details</button>
        <a href="{{ route('vendor.bank-details.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
</section>