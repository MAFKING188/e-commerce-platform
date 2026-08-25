<x-app-layout>

@section('title', 'Edit Bank Details | Vendor Dashboard')

@section('content')
<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Financial Settings</span>
        <h1 class="pc-title">Edit Bank Details</h1>
        <p class="pc-subtitle">Update your bank details screenshot for customer payments.</p>
    </div>
</div>

<form action="{{ route('partner.bank-details.update', $bankDetail) }}" method="POST" enctype="multipart/form-data">
    @csrf
    @method('PUT')

    <div class="pc-card">
        <div class="pc-card__head">
            <h2 class="pc-card__title">Bank Details Image</h2>
        </div>
        <div class="pc-card__body">
            <div class="pc-field">
                <label class="pc-label" for="bank_details_image">Bank Details Screenshot/Image</label>
                <input type="file" name="bank_details_image" id="bank_details_image" class="pc-input" accept="image/*">
                <p class="pc-hint">Upload a new screenshot or photo of your bank details. Leave empty to keep current. Max 5MB.</p>
                @error('bank_details_image')
                    <p class="pc-error">{{ $message }}</p>
                @enderror
            </div>

            @if($bankDetail->bank_details_image)
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
            <h2 class="pc-card__title">Status</h2>
        </div>
        <div class="pc-card__body">
            <div class="pc-field pc-field--checkbox">
                <input type="checkbox" name="is_active" id="is_active" value="1" {{ $bankDetail->is_active ? 'checked' : '' }}>
                <label for="is_active" class="pc-label">Active - Customers can see these details at checkout</label>
            </div>
        </div>
    </div>

    <div class="pc-actions" style="margin-top: 1.5rem;">
        <button type="submit" class="btn btn-primary">Update Bank Details</button>
        <a href="{{ route('partner.bank-details.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection
</x-app-layout>