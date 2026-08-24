@section('title', 'Order Details | Admin')

<x-app-layout>
@include('partials.admin-nav')

<div class="pc-wrap-narrow">
    <a href="{{ route('admin.orders.index') }}" class="pc-back-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
        Back to Orders
    </a>

    <div class="pc-header">
        <div>
            <span class="pc-eyebrow">Fulfillment</span>
            <h1 class="pc-title">Order #{{ $order->id }}</h1>
        </div>
        @include('partials.partner.status-badge', ['status' => $order->status])
    </div>

    <div class="pc-meta-grid">
        <div class="pc-card pc-meta">
            <span class="pc-meta__label">Customer</span>
            <div class="pc-meta__value">{{ $order->user->name }}</div>
        </div>
        <div class="pc-card pc-meta">
            <span class="pc-meta__label">Email</span>
            <div class="pc-meta__value">{{ $order->user->email }}</div>
        </div>
    </div>

    @if($order->shipping_address)
        <div class="pc-meta-grid">
            <div class="pc-card pc-meta">
                <span class="pc-meta__label">Recipient</span>
                <div class="pc-meta__value">{{ $order->recipient_name }}</div>
            </div>
            <div class="pc-card pc-meta">
                <span class="pc-meta__label">Phone</span>
                <div class="pc-meta__value">{{ $order->recipient_phone }}</div>
            </div>
            <div class="pc-card pc-meta">
                <span class="pc-meta__label">Delivery Address</span>
                <div class="pc-meta__value">{{ $order->shipping_address }}</div>
            </div>
            @if($order->delivery_notes)
                <div class="pc-card pc-meta">
                    <span class="pc-meta__label">Delivery Notes</span>
                    <div class="pc-meta__value">{{ $order->delivery_notes }}</div>
                </div>
            @endif
        </div>
    @endif

    <div class="pc-card">
        <div class="pc-card__head">
            <h2 class="pc-card__title">Items</h2>
        </div>
        <div class="pc-table-wrap">
            <table class="pc-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th class="is-right">Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product->name }}</td>
                        <td class="is-numeric">{{ $item->quantity }}</td>
                        <td class="is-right is-strong">${{ number_format($item->price, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="is-right">Total:</td>
                        <td class="is-right is-strong">${{ number_format($order->total_price, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @if($order->status === 'pending_payment')
        <div class="pc-card pc-card--warning">
            <div class="pc-card__head">
                <h2 class="pc-card__title">Validate Payment</h2>
            </div>
            <div class="pc-card__body">
                <p>Order is pending bank transfer proof validation.</p>
                <p class="text-muted small">Maximum 24 hours for validation.</p>
                <form action="{{ route('admin.orders.validate-payment', $order->id) }}" method="POST">
                    @csrf
                    @method('POST')
                    <div class="form-group">
                        <select name="action" class="form-input">
                            <option value="approve">Approve</option>
                            <option value="reject">Reject</option>
                        </select>
                    </div>
                    @if(request('action') === 'reject')
                        <div class="form-group">
                            <textarea name="reason" class="form-input" rows="3" required></textarea>
                            <span class="form-error">Reason is required when rejecting</span>
                        </div>
                    @endif
                    <button type="submit" class="btn btn-primary">Validate</button>
                </form>
            </div>
        </div>
    @endif
</div>
</x-app-layout>