@section('title', 'My Orders | Partner Dashboard')

@section('scripts')
@vite('resources/js/partner.js')
@endsection

<x-app-layout>
@include('partials.partner-nav')

<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Order Fulfillment</span>
        <h1 class="pc-title">My Orders.</h1>
    </div>
</div>

<form action="{{ route('partner.orders.index') }}" method="GET" class="pc-filter">
    <input type="text" name="search" class="pc-filter__input" placeholder="Search order #" value="{{ request('search') }}" inputmode="numeric">
    <select name="status" class="pc-filter__select">
        <option value="">All statuses</option>
        @foreach ($statuses as $status)
            <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
        @endforeach
    </select>
    <button type="submit" class="btn btn-primary pc-btn-sm">Apply</button>
    @if (request()->hasAny(['search', 'status']))
        <a href="{{ route('partner.orders.index') }}" class="pc-filter__reset">Clear filters</a>
    @endif
</form>

<div class="pc-table-wrap">
    <table class="pc-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Customer</th>
                <th>Items</th>
                <th>Status</th>
                <th>Payment</th>
                <th>Proof</th>
                <th class="is-right">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td class="is-numeric">#{{ $order->id }}</td>
                    <td class="is-muted">{{ $order->created_at->format('M d, Y') }}</td>
                    <td>{{ $order->user->name }} ({{ $order->user->email }})</td>
                    <td>
                        @foreach($order->items as $item)
                            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.25rem;">
                                <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}" style="width: 32px; height: 32px; border-radius: 4px; object-fit: cover;">
                                <div style="font-size: 0.8rem; line-height: 1.2;">
                                    <div style="font-weight: 500;">{{ $item->product->name }}</div>
                                    <div style="color: #64748b;">×{{ $item->quantity }} — ${{ number_format($item->price, 2) }}</div>
                                </div>
                            </div>
                        @endforeach
                    </td>
                    <td>@include('partials.partner.status-badge', ['status' => $order->status])</td>
                    <td>
                        @foreach($order->payments as $payment)
                            <div class="payment-badge" style="display: inline-block; margin: 0.125rem; padding: 0.125rem 0.5rem; border-radius: 4px; font-size: 0.75rem; background: {{ $payment->status === 'paid' ? '#d4edda' : ($payment->status === 'pending' ? '#fff3cd' : '#f8d7da') }}; color: {{ $payment->status === 'paid' ? '#155724' : ($payment->status === 'pending' ? '#856404' : '#721c24') }};">
                                {{ $payment->partner->name ?? 'Platform' }}: {{ $payment->amount }} ({{ ucfirst($payment->status) }})
                            </div>
                        @endforeach
                    </td>
                    <td>
                        @foreach($order->payments as $payment)
                            @if($payment->status === 'pending' && $payment->method === 'bank_transfer')
                                @if($payment->proof_path)
                                    <a href="{{ asset('storage/' . $payment->proof_path) }}" target="_blank" class="pc-btn-sm" style="display: inline-block; margin: 0.125rem; font-size: 0.7rem;">View Proof</a>
                                @else
                                    <span class="pc-text-muted" style="font-size: 0.75rem;">No proof</span>
                                @endif
                            @endif
                        @endforeach
                    </td>
                    <td class="is-right">
                        @if($order->status === 'pending_payment')
                            @foreach($order->payments as $payment)
                                @if($payment->status === 'pending' && $payment->method === 'bank_transfer' && $payment->partner_id && !$payment->validated_at)
                                    <form action="{{ route('partner.payments.validate', $payment) }}" method="POST" style="display:inline; margin: 0.125rem;">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" name="action" value="approve" class="pc-btn-sm pc-btn-sm--ok" style="font-size: 0.7rem; padding: 0.25rem 0.5rem;">Approve</button>
                                        <button type="submit" name="action" value="reject" class="pc-btn-sm pc-btn-sm--error" style="font-size: 0.7rem; padding: 0.25rem 0.5rem;">Reject</button>
                                    </form>
                                @endif
                            @endforeach
                        @elseif($order->status === 'paid')
                            <form action="{{ route('partner.orders.ship', $order->id) }}" method="POST" style="display:inline; margin: 0.125rem;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="pc-btn-sm" style="font-size: 0.7rem; padding: 0.25rem 0.5rem; background:#0070ba; color:#fff; border:none; border-radius:4px;">Mark Shipped</button>
                            </form>
                        @elseif($order->status === 'shipped')
                            <form action="{{ route('partner.orders.complete', $order->id) }}" method="POST" style="display:inline; margin: 0.125rem;">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="pc-btn-sm pc-btn-sm--ok" style="font-size: 0.7rem; padding: 0.25rem 0.5rem;">Mark Completed</button>
                            </form>
                        @elseif($order->status === 'completed')
                            <span class="pc-text-muted" style="font-size: 0.75rem;">Completed</span>
                        @elseif($order->status === 'cancelled')
                            <span class="pc-text-muted" style="font-size: 0.75rem;">Cancelled</span>
                        @endif

                        <a href="{{ route('partner.orders.show', $order->id) }}" class="pc-btn-sm" style="font-size: 0.7rem; padding: 0.25rem 0.5rem; margin: 0.125rem;">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8">
                        @include('partials.partner.empty-state', [
                            'icon' => request()->hasAny(['search', 'status']) ? 'search' : 'receipt',
                            'title' => request()->hasAny(['search', 'status']) ? 'No matching orders' : 'No orders yet',
                            'text' => request()->hasAny(['search', 'status'])
                                ? 'Try adjusting your search or clearing the filters to see all orders.'
                                : 'Orders containing your items will appear here once customers start purchasing your pieces.',
                        ])
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination-wrap">
    {{ $orders->links() }}
</div>
</x-app-layout>