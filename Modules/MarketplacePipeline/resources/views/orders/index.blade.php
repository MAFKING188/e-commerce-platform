@section('title', 'Order History | SmartShop')

<x-app-layout>

<div style="max-width: 1000px; margin: 0 auto;">
    <div class="orders-header">
        <h1>Your Orders</h1>
    </div>

    {{-- ENCOURAGE SUPPORT --}}
    <div style="background: var(--brand-accent-soft); border: 1px solid var(--brand-accent); padding: 1.5rem 2rem; border-radius: 12px; margin-bottom: 3rem; display: flex; align-items: center; justify-content: space-between; gap: 2rem; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 300px;">
            <h3 style="font-size: 1.1rem; font-weight: 800; color: var(--brand-accent); margin-bottom: 0.25rem;">Enjoying SmartShop?</h3>
            <p style="font-size: 0.9rem; color: var(--text-600);">If you enjoy this project, consider supporting its growth. Your contributions help fund server costs and future architectural experiments.</p>
        </div>
        <a href="https://www.paypal.com/ncp/payment/Q3SN7Q7K8YDEU" target="_blank" class="btn btn-primary" style="background: var(--brand-accent); white-space: nowrap;">
            💳 Support Project
        </a>
    </div>

    @if($orders->count())
        @foreach($orders as $order)
            <div class="order-card">
            <!-- Header Summary -->
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
                <div class="summary-item" style="text-align: right;">
                    <span class="status-pill status-{{ strtolower($order->status) }}">
                        {{ $order->status }}
                    </span>
                </div>
            </div>

            <!-- Items Table -->
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
                                        <span style="font-weight: 600; color: var(--text-900);">{{ $item->product->name }}</span>
                                    </div>
                                </td>
                                <td style="color: var(--text-600);">{{ $item->quantity }}</td>
                                <td style="font-weight: 600; color: var(--text-900);">@money($item->price)</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Actions -->
            @if($order->status === 'pending')
                <div class="order-actions">
                    <form method="POST" action="{{ route('orders.cancel', $order->id) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="btn-cancel">Cancel Order</button>
                    </form>

                    <form method="POST" action="{{ route('paypal.store') }}">
                        @csrf
                        <input type="hidden" name="order_id" value="{{ $order->id }}">
                        <button type="submit" class="btn-paypal">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor"><path d="M20.067 8.478c.492.292.541.97.108 1.402l-6.142 6.143a.75.75 0 01-1.06 0L6.83 9.88c-.433-.432-.384-1.11.108-1.402a14.73 14.73 0 0113.128 0zM12 21a9 9 0 100-18 9 9 0 000 18z"/></svg>
                            Pay with PayPal
                        </button>
                    </form>
                </div>
            @endif
        </div>
    @endforeach
@else
    <div class="no-orders">
        <h2 style="font-weight: 700; margin-bottom: 1rem; color: var(--text-900);">No orders yet</h2>
        <p style="color: var(--text-600); margin-bottom: 2rem;">When you place an order, it will appear here.</p>
        <a href="{{ route('shop') }}" class="btn btn-primary">Discover Products</a>
    </div>
@endif

</x-app-layout>
