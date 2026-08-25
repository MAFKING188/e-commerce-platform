<x-app-layout>

@section('title', 'Upload Proof of Payment | Order #' . $order->id)

<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Payment Proof</span>
        <h1 class="pc-title">Upload Proof for Order #{{ $order->id }}</h1>
    </div>
</div>

@if(session('status'))
    <div class="pc-alert pc-alert--success">
        {{ session('status') }}
    </div>
@endif

@if($errors->any())
    <div class="pc-alert pc-alert--error">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="pc-card" style="margin-bottom: 1.5rem;">
    <div class="pc-card__head">
        <h2 class="pc-card__title">Order Summary</h2>
    </div>
    <div class="pc-card__body">
        <div class="pc-grid pc-grid--2">
            <div class="pc-field">
                <label class="pc-label">Order ID</label>
                <p>#{{ $order->id }}</p>
            </div>
            <div class="pc-field">
                <label class="pc-label">Total</label>
                <p class="text-lg font-bold">${{ number_format($order->total_price, 2) }}</p>
            </div>
            <div class="pc-field">
                <label class="pc-label">Status</label>
                <p>{{ ucfirst(str_replace('_', ' ', $order->status)) }}</p>
            </div>
            <div class="pc-field">
                <label class="pc-label">Reference</label>
                <p><code>ORDER-{{ $order->id }}</code></p>
            </div>
        </div>
    </div>
</div>

<p class="pc-text-muted" style="margin-bottom: 1.5rem;">
    Upload a screenshot or photo of your bank transfer confirmation/receipt for each vendor below.
    Each vendor will validate their payment separately within 24 hours.
</p>

@foreach($order->payments as $payment)
<div class="pc-card" style="margin-bottom: 1.5rem;">
    <div class="pc-card__head">
        <h2 class="pc-card__title">{{ $payment->partner->name ?? 'Platform' }} — ${{ number_format($payment->amount, 2) }}</h2>
        @if($payment->proof_path)
            <span class="pc-badge pc-badge--success">Proof Uploaded</span>
        @elseif($payment->status === 'paid')
            <span class="pc-badge pc-badge--success">Validated</span>
        @elseif($payment->status === 'rejected')
            <span class="pc-badge pc-badge--error">Rejected</span>
        @else
            <span class="pc-badge pc-badge--warning">Awaiting Proof</span>
        @endif
    </div>
    <div class="pc-card__body">
        @if($payment->partner)
            @php $bankDetail = $payment->partner->bankDetails; @endphp
            @if($bankDetail && $bankDetail->is_active)
                <div style="border: 1px solid #e0e0e0; border-radius: 8px; padding: 1rem; margin-bottom: 1rem; background: #fafafa;">
                    <h4 style="margin-bottom: 0.5rem;">Bank Details for {{ $payment->partner->name }}</h4>
                    @if($bankDetail->bank_details_image)
                        <a href="{{ asset('storage/' . $bankDetail->bank_details_image) }}" target="_blank">
                            <img src="{{ asset('storage/' . $bankDetail->bank_details_image) }}" alt="Bank Details" style="max-width: 100%; max-height: 200px; border-radius: 4px; margin: 0.5rem 0;">
                        </a>
                    @endif
                    <div style="font-size: 0.875rem; line-height: 1.6;">
                        @if($bankDetail->account_holder)
                            <strong>Account Holder:</strong> {{ $bankDetail->account_holder }}<br>
                        @endif
                        @if($bankDetail->iban)
                            <strong>IBAN:</strong> {{ $bankDetail->iban }}<br>
                        @endif
                        @if($bankDetail->bank_name)
                            <strong>Bank:</strong> {{ $bankDetail->bank_name }}<br>
                        @endif
                        @if($bankDetail->swift_bic)
                            <strong>SWIFT/BIC:</strong> {{ $bankDetail->swift_bic }}<br>
                        @endif
                        @if($bankDetail->additional_info)
                            <br><strong>Note:</strong> {{ $bankDetail->additional_info }}
                        @endif
                    </div>
                    <p style="font-size: 0.75rem; color: #666; margin-top: 0.5rem;">Reference: <code>ORDER-{{ $order->id }}-{{ $payment->partner_id }}</code></p>
                </div>
            @else
                <div class="pc-alert pc-alert--warning" style="margin-bottom: 1rem;">
                    This vendor has not configured bank details yet. Please contact them for payment instructions.
                </div>
            @endif
        @endif

        @if($payment->status === 'paid')
            <div class="pc-alert pc-alert--success">
                <strong>Payment validated</strong>
                <p style="margin-top: 0.25rem;">This vendor payment has been confirmed.</p>
            </div>
        @else
            @if($payment->status === 'rejected')
                <div class="pc-alert pc-alert--error" style="margin-bottom: 1rem;">
                    <strong>Proof was rejected</strong>
                    @if($payment->validation_notes)
                        <p style="margin-top: 0.25rem;">Reason: {{ $payment->validation_notes }}</p>
                    @endif
                    <p style="margin-top: 0.25rem;">Please upload a new proof of payment below.</p>
                </div>
            @elseif($payment->proof_path)
                <div class="pc-alert pc-alert--info" style="margin-bottom: 1rem;">
                    <strong>Proof already uploaded</strong>
                    <p style="margin-top: 0.5rem;">Your proof has been received and is pending validation. You can replace it below if needed.</p>
                    <img src="{{ asset('storage/' . $payment->proof_path) }}" alt="Proof of payment" style="max-width: 300px; margin-top: 1rem; border-radius: 8px;">
                </div>
            @endif
            <form method="POST" action="{{ route('payment.handle-upload-proof', ['payment' => $payment->id]) }}" enctype="multipart/form-data">
                @csrf
                <div class="pc-field" style="margin-bottom: 1rem;">
                    <label for="proof_image_{{ $payment->id }}" class="pc-label">Proof Screenshot</label>
                    <input type="file" name="proof_image" id="proof_image_{{ $payment->id }}" class="pc-input" accept="image/*" required>
                    @error('proof_image') <p class="pc-error">{{ $message }}</p> @enderror
                </div>
                <button type="submit" class="btn btn-primary">{{ $payment->proof_path ? 'Replace Proof' : 'Upload Proof' }}</button>
            </form>
        @endif
    </div>
</div>
@endforeach

<div style="margin-top: 1.5rem;">
    <a href="{{ route('orders.index') }}" class="btn btn-secondary">View My Orders</a>
</div>

</x-app-layout>
