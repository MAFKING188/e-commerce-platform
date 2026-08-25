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
            @if($order->status === 'paid')
                <form action="{{ route('partner.orders.ship', $order->id) }}" method="POST" class="inline-form" data-confirm="Mark this order as shipped? The collector will be notified by email.">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn btn-primary pc-btn-sm">Mark as Shipped</button>
                </form>
            @endif
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

<div class="pc-note">
    <h4 class="pc-note__title">Logistics Note</h4>
    <p class="pc-note__text">
        Please ensure all pieces are inspected for quality before dispatch. Once shipped, please update the central logistics hub.
    </p>
</div>

@if($order->status === 'pending_payment')
    <div class="pc-card pc-card--warning">
        <div class="pc-card__head">
            <h2 class="pc-card__title">Validate Payment</h2>
        </div>
        <div class="pc-card__body">
            <p>Order is pending bank transfer proof validation.</p>
            <p class="text-muted small">Maximum 24 hours for validation.</p>
            <form action="{{ route('partner.orders.validate-payment', $order->id) }}" method="POST" class="inline-form" data-confirm="Validate this payment?">
                @csrf
                @method('PATCH')
                <input type="hidden" name="action" value="approve">
                <button type="submit" class="btn btn-primary pc-btn-sm">Approve</button>
                <button type="submit" name="action" value="reject" class="btn btn-danger pc-btn-sm">Reject</button>
            </form>
        </div>
    </div>
@endif

@if(in_array($order->status, ['paid', 'shipped']))
    <div class="pc-card pc-card--success">
        <div class="pc-card__head">
            <h2 class="pc-card__title">Mark as Completed</h2>
        </div>
        <div class="pc-card__body">
            <p>Once the order has been delivered, mark it as completed to release payment.</p>
            <form action="{{ route('partner.orders.complete', $order->id) }}" method="POST" class="inline-form" data-confirm="Mark this order as completed?">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn btn-primary pc-btn-sm">Mark as Completed</button>
            </form>
        </div>
    </div>
@endif

</x-app-layout>