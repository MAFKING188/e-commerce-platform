@section('title', 'Order Details | Admin')

<x-app-layout>
<div style="max-width: 800px; margin: 0 auto;">
    <div style="margin-bottom: 2rem;">
        <a href="{{ route('admin.orders.index') }}" style="color: var(--text-400); text-decoration: none;">&larr; Back to Orders</a>
        <h1 style="margin-top: 1rem;">Order #{{ $order->id }}</h1>
    </div>

    <div style="background: var(--surface-100); padding: 2rem; border-radius: 12px; border: 1px solid var(--border); margin-bottom: 2rem;">
        <h3>Customer Information</h3>
        <p><strong>Name:</strong> {{ $order->user->name }}</p>
        <p><strong>Email:</strong> {{ $order->user->email }}</p>
        <p><strong>Status:</strong> {{ strtoupper($order->status) }}</p>
    </div>

    <div style="background: var(--surface-100); padding: 2rem; border-radius: 12px; border: 1px solid var(--border);">
        <h3>Items</h3>
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="text-align: left; border-bottom: 1px solid var(--border);">
                    <th style="padding: 1rem 0;">Product</th>
                    <th style="padding: 1rem 0;">Qty</th>
                    <th style="padding: 1rem 0;">Price</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr style="border-bottom: 1px solid var(--border);">
                    <td style="padding: 1rem 0;">{{ $item->product->name }}</td>
                    <td style="padding: 1rem 0;">{{ $item->quantity }}</td>
                    <td style="padding: 1rem 0;">${{ number_format($item->price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" style="padding: 1rem 0; text-align: right; font-weight: 800;">Total:</td>
                    <td style="padding: 1rem 0; font-weight: 800;">${{ number_format($order->total_price, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
</x-app-layout>
