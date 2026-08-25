<x-app-layout>

@section('title', 'Add Bank Details | Vendor Dashboard')

@section('content')
<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Financial Settings</span>
        <h1 class="pc-title">Add Bank Details</h1>
        <p class="pc-subtitle">Upload a screenshot of your bank details (IBAN, account holder, etc.) so customers can pay you directly.</p>
    </div>
</div>

<form action="{{ route('partner.bank-details.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="pc-card">
        <div class="pc-card__head">
            <h2 class="pc-card__title">Bank Details Image</h2>
        </div>
        <div class="pc-card__body">
            <div class="pc-field">
                <label class="pc-label" for="bank_details_image">Bank Details Screenshot/Image</label>
                <input type="file" name="bank_details_image" id="bank_details_image" class="pc-input" accept="image/*" required>
                <p class="pc-hint">Upload a screenshot or photo of your bank details (IBAN, account holder, etc.). Max 5MB. Supported: JPG, PNG, GIF.</p>
                @error('bank_details_image')
                    <p class="pc-error">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <div class="pc-card" style="margin-top: 1.5rem;">
        <div class="pc-card__head">
            <h2 class="pc-card__title">Status</h2>
        </div>
        <div class="pc-card__body">
            <div class="pc-field pc-field--checkbox">
                <input type="checkbox" name="is_active" id="is_active" value="1" checked>
                <label for="is_active" class="pc-label">Active - Customers can see these details at checkout</label>
            </div>
        </div>
    </div>

    <div class="pc-actions" style="margin-top: 1.5rem;">
        <button type="submit" class="btn btn-primary">Save Bank Details</button>
        <a href="{{ route('partner.bank-details.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection
</x-app-layout>