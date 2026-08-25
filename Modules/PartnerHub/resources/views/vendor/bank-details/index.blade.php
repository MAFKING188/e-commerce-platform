<x-app-layout>

@section('title', 'Bank Details | Vendor Dashboard')

@section('content')
<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Financial Settings</span>
        <h1 class="pc-title">Bank Details for Payouts</h1>
        <p class="pc-subtitle">Manage how customers pay you directly via bank transfer.</p>
    </div>
</div>

@if(session('status'))
    <div class="pc-alert pc-alert--success">
        {{ session('status') }}
    </div>
@endif

@if($bankDetail)
<div class="pc-card">
    <div class="pc-card__head">
        <h2 class="pc-card__title">Current Bank Details</h2>
    </div>
    <div class="pc-card__body">
        <div class="pc-grid pc-grid--2">
            @if($bankDetail->bank_details_image)
                <div class="pc-field">
                    <label class="pc-label">Bank Details Image</label>
                    <a href="{{ asset('storage/' . $bankDetail->bank_details_image) }}" target="_blank">
                        <img src="{{ asset('storage/' . $bankDetail->bank_details_image) }}" alt="Bank Details" class="pc-img-preview" style="max-width: 300px; border-radius: 8px;">
                    </a>
                </div>
            @endif

            <div class="pc-field">
                <label class="pc-label">Account Holder</label>
                <p>{{ $bankDetail->account_holder ?? 'Not provided' }}</p>
            </div>

            <div class="pc-field">
                <label class="pc-label">IBAN</label>
                <p>{{ $bankDetail->iban ?? 'Not provided' }}</p>
            </div>

            <div class="pc-field">
                <label class="pc-label">Bank Name</label>
                <p>{{ $bankDetail->bank_name ?? 'Not provided' }}</p>
            </div>

            <div class="pc-field">
                <label class="pc-label">SWIFT/BIC</label>
                <p>{{ $bankDetail->swift_bic ?? 'Not provided' }}</p>
            </div>

            <div class="pc-field pc-field--full">
                <label class="pc-label">Additional Info</label>
                <p>{{ $bankDetail->additional_info ?? 'None' }}</p>
            </div>
        </div>

        <div class="pc-actions" style="margin-top: 1.5rem;">
            <a href="{{ route('partner.bank-details.edit', $bankDetail) }}" class="btn btn-primary">Edit Details</a>
            <form action="{{ route('partner.bank-details.destroy', $bankDetail) }}" method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete these bank details?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger">Delete</button>
            </form>
        </div>
    </div>
</div>

@if(!$bankDetail->is_active)
<div class="pc-alert pc-alert--warning" style="margin-top: 1rem;">
    <strong>Note:</strong> These bank details are currently inactive. Customers will not see them at checkout.
</div>
@endif
@else
<div class="pc-card">
    <div class="pc-card__body" style="text-align: center; padding: 3rem;">
        <svg class="pc-icon pc-icon--large" style="margin-bottom: 1rem; color: var(--color-muted);" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
            <path d="M21 12V7H5"/>
            <path d="M3 5v14a2 2 0 0 0 2 2h14"/>
            <path d="M18 12a2 2 0 0 0 0 4 2 2 0 0 0 0-4"/>
            <path d="M7.5 17.5a4.5 4.5 0 0 1 9 0"/>
        </svg>
        <h2 style="margin-bottom: 0.5rem;">No Bank Details Configured</h2>
        <p class="pc-text-muted" style="margin-bottom: 1.5rem;">Upload your bank details so customers can pay you directly via bank transfer.</p>
        <a href="{{ route('partner.bank-details.create') }}" class="btn btn-primary">Add Bank Details</a>
    </div>
</div>
@endif
@endsection
</x-app-layout>