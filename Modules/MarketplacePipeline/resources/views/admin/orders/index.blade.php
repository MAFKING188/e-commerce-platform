@section('title', 'Order Management | Admin')

<x-app-layout>
@include('partials.admin-nav')

<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Fulfillment</span>
        <h1 class="pc-title">Order Management</h1>
        <p class="pc-subtitle">Monitor and process all customer acquisitions.</p>
    </div>
</div>

<div class="pc-table-wrap">
    <table class="pc-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Status</th>
                <th>Date</th>
                <th class="is-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orders as $order)
            <tr>
                <td class="is-numeric">#{{ $order->id }}</td>
                <td>{{ $order->user->name }}</td>
                <td class="is-strong">@money($order->total_price)</td>
                <td>@include('partials.partner.status-badge', ['status' => $order->status])</td>
                <td class="is-muted">{{ $order->created_at->format('M d, H:i') }}</td>
                <td class="is-right">
                    <div class="pc-row-actions">
                        <a href="{{ route('admin.orders.show', $order->id) }}" class="pc-btn-sm">View</a>
                        @if($order->status === 'paid')
                            <form action="{{ route('admin.orders.complete', $order->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="pc-btn-sm pc-btn-sm--ok">Mark Shipped</button>
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