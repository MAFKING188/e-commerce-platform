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

@if($orders->isNotEmpty())
<div class="pc-table-wrap">
    <table class="pc-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Status</th>
                <th>Proof</th>
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
                <td>
                    @if($order->status === 'pending_payment')
                        @if($order->payment && $order->payment->proof_path)
                            <a href="{{ asset('storage/' . $order->payment->proof_path) }}" target="_blank" class="pc-btn-sm">View</a>
                        @endif
                    @endif
                </td>
                <td class="is-muted">{{ $order->created_at->format('M d, H:i') }}</td>
                <td class="is-right">
                    <div class="pc-row-actions">
                        @if($order->status === 'pending_payment')
                            <form action="{{ route('admin.orders.validate-payment', $order->id) }}" method="POST">
                                @csrf
                                @method('POST')
                                <button type="submit" name="action" value="approve" class="pc-btn-sm pc-btn-sm--ok">Approve</button>
                                <button type="submit" name="action" value="reject" class="pc-btn-sm pc-btn-sm--error">Reject</button>
                            </form>
                        @endif
                    </div>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="pc-pagination">
    {{ $orders->links() }}
</div>
@else
<div class="review-empty">
    <svg class="review-empty-icon" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
    <p>No orders recorded yet.</p>
</div>
@endif
</x-app-layout>