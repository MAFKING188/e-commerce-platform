@section('title', 'Order Management | Admin')

<x-app-layout>
@include('partials.admin-nav')

<div class="admin-orders-header">
    <h1>Order Management</h1>
    <p>Monitor and process all customer acquisitions.</p>
</div>

<div class="table-container">
    <table class="order-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            <tr>
                <td>#{{ $order->id }}</td>
                <td>{{ $order->user->name }}</td>
                <td>@money($order->total_price)</td>
                <td>
                    <span class="status-pill status-{{ strtolower($order->status) }}">
                        {{ $order->status }}
                    </span>
                </td>
                <td>{{ $order->created_at->format('M d, H:i') }}</td>
                <td>
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="btn-sm">View</a>
                        @if($order->status === 'paid')
                            <form action="{{ route('admin.orders.complete', $order->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn-sm btn-complete">Mark Shipped</button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>
</x-app-layout>
