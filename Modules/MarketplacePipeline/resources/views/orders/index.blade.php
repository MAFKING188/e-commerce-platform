@section('title', 'Order History | SmartShop')

<x-app-layout>

<div class="orders-wrap">
    <div class="orders-header">
        <h1>Your Orders</h1>
    </div>

    @if (session('error'))
        <div class="checkout-error" role="alert" style="margin-bottom: 1rem;">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="checkout-error" role="alert" style="margin-bottom: 1rem;">
            @foreach ($errors->all() as $message)
                <div>{{ $message }}</div>
            @endforeach
        </div>
    @endif

    @if($orders->count())
        @foreach($orders as $order)
            <div class="order-card">
                <div class="order-summary-bar">
                    <div class="summary-item">
                        <label>Order Placed</label>
                        <span>{{ $order->created_at->format('M d, Y') }}</span>
                    </div>
                    <div class="summary-item">
                        <label>Total Price</label>
                        <span>@money($order->total_price)</span>
                    </div>
                    <div class="summary-item">
                        <label>Order ID</label>
                        <span>#{{ $order->id }}</span>
                    </div>
                    <div class="summary-item summary-item--right">
                        <span class="status-pill status-{{ strtolower($order->status) }}">
                            {{ $order->status }}
                        </span>
                    </div>
                </div>

                @include('marketplacepipeline::orders._progress', ['order' => $order])

                <div class="order-items-table-wrap">
                    <table class="order-items-table">
                        <thead>
                            <tr>
                                <th>Piece</th>
                                <th>Quantity</th>
                                <th>Value</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->items as $item)
                                <tr>
                                    <td>
                                        <div class="product-cell">
                                            <img src="{{ $item->product->image_url }}" class="product-thumb" alt="{{ $item->product->name }}">
                                            <span class="product-cell__name">{{ $item->product->name }}</span>
                                        </div>
                                    </td>
                                    <td class="order-cell-muted">{{ $item->quantity }}</td>
                                    <td class="order-cell-strong">@money($item->price)</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($order->recipient_name)
                    <div class="order-delivery">
                        <h4>Delivery</h4>
                        <div class="order-delivery__grid">
                            <div class="order-delivery__item">
                                <label>Recipient</label>
                                <span>{{ $order->recipient_name }}</span>
                            </div>
                            <div class="order-delivery__item">
                                <label>Phone</label>
                                <span>{{ $order->recipient_phone }}</span>
                            </div>
                            <div class="order-delivery__item order-delivery__item--full">
                                <label>Address</label>
                                <span>{{ $order->shipping_address }}</span>
                            </div>
                            @if($order->delivery_notes)
                                <div class="order-delivery__item order-delivery__item--full">
                                    <label>Delivery Notes</label>
                                    <span>{{ $order->delivery_notes }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                @if($order->status === 'pending')
                    <div class="order-actions">
                        <form method="POST" action="{{ route('orders.cancel', $order->id) }}" data-confirm="Cancel order #{{ $order->id }}? The pieces will be returned to the archive and your payment voided.">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-cancel">Cancel Order</button>
                        </form>

                        <form method="POST" action="{{ route('paypal.store') }}" class="paypal-checkout-form">
                            @csrf
                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                            <button type="submit" class="btn-paypal">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.067 8.478c.492.292.541.97.108 1.402l-6.142 6.143a.75.75 0 01-1.06 0L6.83 9.88c-.433-.432-.384-1.11.108-1.402a14.73 14.73 0 0113.128 0zM12 21a9 9 0 100-18 9 9 0 000 18z"/></svg>
                                Pay with PayPal
                            </button>
                        </form>
                    </div>
                @elseif($order->status === 'pending_payment')
                    @php
                        $pendingProof = $order->payments->where('method', 'bank_transfer')->whereNull('proof_path')->first();
                    @endphp
                    <div class="order-actions">
                        @if($pendingProof)
                            <a href="{{ route('payment.upload-proof', $order->id) }}" style="color:#fff;background:#10b981;border:none;border-radius:6px;padding:.5rem 1.5rem;font-size:.85rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.5rem;">
                                Upload Proof of Payment
                            </a>
                            <span style="color:#475569;font-size:.8rem;line-height:1.4;">
                                Pay each vendor by bank transfer, then upload your proof here.
                                Use reference <strong>ORDER-{{ $order->id }}-{{ $pendingProof->partner_id }}</strong>.
                                Vendors confirm within 24h.
                            </span>
                        @else
                            <span style="color:#475569;font-size:.8rem;">
                                Proof uploaded for all vendors. Awaiting vendor confirmation (within 24h).
                            </span>
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    @else
        <div class="no-orders">
            <h2>No orders yet</h2>
            <p>When you place an order, it will appear here.</p>
            <a href="{{ route('shop') }}" class="btn btn-primary">Discover Products</a>
        </div>
    @endif
</div>

@section('scripts')
<script>
    // Prevent double-submission of the PayPal button (avoid duplicate pending payments).
    document.querySelectorAll('form.paypal-checkout-form').forEach(function (form) {
        form.addEventListener('submit', function () {
            var btn = form.querySelector('button[type="submit"]');
            if (!btn) return;
            btn.disabled = true;
            btn.dataset.original = btn.innerHTML;
            btn.innerHTML = 'Redirecting to PayPal…';
        });
    });
</script>
@endsection

</x-app-layout>