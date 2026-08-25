@section('title', 'Order Confirmed | SmartShop')

<x-app-layout>

<section class="confirmation-hero">
    <span class="cat-badge">Order #{{ $order->id }}</span>
    <h1>Thank you — your order is confirmed.</h1>
    <p class="confirmation-sub">
        A confirmation email is on its way to <strong>{{ auth()->user()->email }}</strong>.
        You can follow every step below or from your order history.
    </p>
</section>

<div class="confirmation-grid">
    <div class="summary-card">
        <h2>Order Summary</h2>
        @foreach($order->items as $item)
            <div class="summary-row">
                <span>{{ $item->product->name }} × {{ $item->quantity }}</span>
                <span>@money($item->price * $item->quantity)</span>
            </div>
        @endforeach
        <div class="summary-row total-row">
            <span>Total</span>
            <span>@money($order->total_price)</span>
        </div>
        <div class="summary-row">
            <span>Delivery to</span>
            <span>{{ $order->recipient_name }}, {{ $order->shipping_city }}, {{ $order->shipping_country }}</span>
        </div>

        @if($order->status === 'pending_payment')
            @php
                $bankPayment = $order->payments()->where('method', 'bank_transfer')->first();
            @endphp
            @if($bankPayment)
                <div class="summary-row" style="border-top: 1px solid var(--border); padding-top: 1rem; margin-top: 1rem;">
                    <div>
                        <strong style="color: #f59e0b;">Action Required: Upload Proof of Payment</strong>
                        <p style="font-size: 0.85rem; color: var(--text-600); margin-top: 0.25rem;">
                            Please upload a screenshot of your bank transfer receipt to complete your order.
                        </p>
                        <a href="{{ route('payment.upload-proof', $bankPayment->id) }}" class="btn btn-primary" style="margin-top: 0.75rem; display: inline-block;">
                            Upload Proof of Payment
                        </a>
                    </div>
                </div>
            @endif
        @endif
    </div>

    <div class="confirmation-side">
        <h2>What happens next</h2>
        <ol class="confirmation-steps">
            <li>Your artisan prepares and packs your piece.</li>
            <li>You get an email the moment it ships.</li>
            <li>Payment is released to the artisan after delivery is confirmed.</li>
        </ol>
        @include('marketplacepipeline::orders._progress', ['order' => $order])
        <div class="pc-form-actions">
            <a href="{{ route('orders.index') }}" class="btn btn-primary">Track This Order</a>
            <a href="{{ route('shop') }}" class="btn btn-ghost">Continue Shopping</a>
        </div>
    </div>
</div>

</x-app-layout>