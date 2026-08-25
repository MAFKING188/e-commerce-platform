@section('title', 'Order #' . $order->id . ' | Partner Dashboard')

@section('scripts')
@vite('resources/js/partner.js')
@endsection

<x-app-layout>
@include('partials.partner-nav')

<a href="{{ route('partner.orders.index') }}" class="pc-back-link">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
    Back to All Orders
</a>

@if(session('status'))
    <div class="pc-alert pc-alert--success">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="pc-alert pc-alert--error">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif

<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Order Fulfillment</span>
        <h1 class="pc-title">Order #{{ $order->id }}</h1>
    </div>
    @include('partials.partner.status-badge', ['status' => $order->status])
</div>

<div class="pc-meta-grid">
    <div class="pc-card pc-meta">
        <span class="pc-meta__label">Placement Date</span>
        <div class="pc-meta__value">{{ $order->created_at->format('M d, Y') }}</div>
    </div>
    <div class="pc-card pc-meta">
        <span class="pc-meta__label">Fulfillment Status</span>
        <div class="pc-meta__value">
            @include('partials.partner.status-badge', ['status' => $order->status])
        </div>
    </div>
    <div class="pc-card pc-meta">
        <span class="pc-meta__label">Client Reference</span>
        <div class="pc-meta__value">{{ $order->user->name }}</div>
    </div>
    @if($order->shipping_address)
        <div class="pc-card pc-meta">
            <span class="pc-meta__label">Delivery Destination</span>
            <div class="pc-meta__value">{{ $order->shipping_city }}, {{ $order->shipping_country }}</div>
        </div>
    @endif
</div>

<div class="pc-card">
    <div class="pc-card__head">
        <h2 class="pc-section-title">Items to Fulfill</h2>
    </div>
    <div class="pc-table-wrap pc-table-wrap--flush">
        <table class="pc-table">
            <thead>
                <tr>
                    <th>Piece</th>
                    <th>Quantity</th>
                    <th>Price Each</th>
                    <th class="is-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($partnerItems as $item)
                    <tr>
                        <td>
                            <div class="pc-product">
                                <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}" class="pc-product__img">
                                <div>
                                    <div class="pc-product__name">{{ $item->product->name }}</div>
                                    <div class="pc-product__cat">{{ $item->product->category->name ?? 'Collection' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="is-numeric">{{ $item->quantity }}</td>
                        <td class="is-muted">${{ number_format($item->price, 2) }}</td>
                        <td class="is-right is-numeric">${{ number_format($item->price * $item->quantity, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="pc-totals">
        <span class="pc-totals__label">Partner Subtotal:</span>
        <span class="pc-totals__value">${{ number_format($partnerSubtotal, 2) }}</span>
    </div>
</div>

@foreach($partnerPayments as $payment)
<div class="pc-card" style="margin-top: 1.5rem;">
    <div class="pc-card__head">
        <h2 class="pc-card__title">Payment — ${{ number_format($payment->amount, 2) }}</h2>
        @if($payment->status === 'paid')
            <span class="pc-badge pc-badge--success">Validated</span>
        @elseif($payment->status === 'rejected')
            <span class="pc-badge pc-badge--error">Rejected</span>
        @elseif($payment->proof_path)
            <span class="pc-badge pc-badge--info">Proof Uploaded</span>
        @else
            <span class="pc-badge pc-badge--warning">Awaiting Proof</span>
        @endif
    </div>
    <div class="pc-card__body">
        @if($payment->proof_path)
            <div style="margin-bottom: 1rem;">
                <strong>Proof of Payment:</strong>
                <a href="{{ asset('storage/' . $payment->proof_path) }}" target="_blank" class="pc-btn-sm" style="display: inline-block; margin-left: 0.5rem;">View Screenshot</a>
            </div>
        @endif

        @if($payment->validation_notes)
            <div class="pc-alert pc-alert--error" style="margin-bottom: 1rem;">
                <strong>Rejection reason:</strong> {{ $payment->validation_notes }}
            </div>
        @endif

        @if($payment->status === 'pending' && $order->status === 'pending_payment')
            @if($payment->proof_path)
                <p style="margin-bottom: 0.75rem;">The buyer has uploaded proof. Please review and validate.</p>
                <form action="{{ route('partner.orders.validate-payment', $order->id) }}" method="POST" style="display: inline-flex; gap: 0.5rem;" data-confirm="Validate this payment?">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" value="approve">
                    <button type="submit" class="btn btn-primary pc-btn-sm">Approve Payment</button>
                </form>
                <form action="{{ route('partner.orders.validate-payment', $order->id) }}" method="POST" style="display: inline-flex; gap: 0.5rem; margin-left: 0.5rem;" data-confirm="Reject this payment? The buyer will be asked to re-upload.">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="action" value="reject">
                    <div style="display: flex; gap: 0.5rem; align-items: center;">
                        <input type="text" name="reason" placeholder="Rejection reason (required)" class="pc-input" style="max-width: 250px;" required>
                        <button type="submit" class="btn btn-danger pc-btn-sm">Reject</button>
                    </div>
                </form>
            @else
                <p class="pc-text-muted">Waiting for buyer to upload proof of payment.</p>
            @endif
        @elseif($payment->status === 'paid')
            <p class="pc-text-muted">Payment validated on {{ $payment->validated_at ? $payment->validated_at->format('M d, Y H:i') : 'N/A' }}.</p>
        @elseif($payment->status === 'rejected')
            <p class="pc-text-muted">You rejected this payment. The buyer will be asked to re-upload.</p>
        @endif
    </div>
</div>
@endforeach

<div class="pc-note" style="margin-top: 1.5rem;">
    <h4 class="pc-note__title">Logistics Note</h4>
    <p class="pc-note__text">
        Please ensure all pieces are inspected for quality before dispatch. Once shipped, please update the central logistics hub.
    </p>
</div>

@if(in_array($order->status, ['pending_payment']))
    <p class="pc-text-muted" style="margin-top: 1rem;">Validate the payment above to proceed with fulfillment.</p>
@endif

@if($order->status === 'paid')
    <form action="{{ route('partner.orders.ship', $order->id) }}" method="POST" style="margin-top: 1rem;" data-confirm="Mark this order as shipped? The collector will be notified by email.">
        @csrf
        @method('PATCH')
        <button type="submit" class="btn btn-primary">Mark as Shipped</button>
    </form>
@endif

@if(in_array($order->status, ['paid', 'shipped']))
    <form action="{{ route('partner.orders.complete', $order->id) }}" method="POST" style="margin-top: 1rem;" data-confirm="Mark this order as completed?">
        @csrf
        @method('PATCH')
        <button type="submit" class="btn btn-primary">Mark as Completed</button>
    </form>
@endif

</x-app-layout>
