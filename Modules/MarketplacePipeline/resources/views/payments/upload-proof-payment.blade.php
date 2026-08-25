<x-app-layout>

@section('title', 'Upload Proof of Payment | Order #{{ $payment->order->id }}')

@section('content')
<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Payment Proof</span>
        <h1 class="pc-title">Upload Proof for {{ $payment->partner->name }}</h1>
    </div>
</div>

@if(session('status'))
    <div class="pc-alert pc-alert--success">
        {{ session('status') }}
    </div>
@endif

<div class="pc-card">
    <div class="pc-card__head">
        <h2 class="pc-card__title">Payment Details</h2>
    </div>
    <div class="pc-card__body">
        <div class="pc-grid pc-grid--2">
            <div class="pc-field">
                <label class="pc-label">Vendor</label>
                <p>{{ $payment->partner->name }}</p>
            </div>
            <div class="pc-field">
                <label class="pc-label">Amount</label>
                <p class="text-lg font-bold">{{ $payment->amount }}</p>
            </div>
            <div class="pc-field">
                <label class="pc-label">Order</label>
                <p><a href="{{ route('orders.show', $payment->order) }}">#{{ $payment->order->id }}</a></p>
            </div>
            <div class="pc-field">
                <label class="pc-label">Reference</label>
                <p><code>ORDER-{{ $payment->order->id }}-{{ $payment->partner_id }}</code></p>
            </div>
        </div>
    </div>
</div>

@if($payment->proof_path)
<div class="pc-alert pc-alert--info" style="margin-top: 1.5rem;">
    <strong>Proof already uploaded</strong>
    <p style="margin-top: 0.5rem;">Your proof of payment has already been received and is pending validation.</p>
    <img src="{{ asset('storage/' . $payment->proof_path) }}" alt="Proof of payment" class="img-preview" style="max-width: 300px; margin-top: 1rem; border-radius: 8px;">
</div>
@else
<form method="POST" action="{{ route('payment.handle-upload-proof', ['payment' => $payment->id]) }}" enctype="multipart/form-data">
    @csrf

    <div class="pc-card" style="margin-top: 1.5rem;">
        <div class="pc-card__head">
            <h2 class="pc-card__title">Upload Proof of Payment</h2>
        </div>
        <div class="pc-card__body">
            <p class="pc-text-muted" style="margin-bottom: 1rem;">
                Upload a screenshot or photo of your bank transfer confirmation/receipt.
                <br>Vendor will validate within 24 hours.
            </p>

            <div class="pc-field">
                <label for="proof_image" class="pc-label">Proof Screenshot</label>
                <input type="file" name="proof_image" id="proof_image" class="pc-input" accept="image/*" required>
                @error('proof_image') <p class="pc-error">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="btn btn-primary">
                Upload Proof
            </button>

            <a href="{{ route('orders.index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    </div>
</form>
@endif

@endsection
</x-app-layout>