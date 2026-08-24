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
                <th>Status</th>
                <th>Proof</th>
                <th class="is-right">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($orders as $order)
                <tr>
                    <td class="is-numeric">#{{ $order->id }}</td>
                    <td class="is-muted">{{ $order->created_at->format('M d, Y') }}</td>
                    <td>@include('partials.partner.status-badge', ['status' => $order->status])</td>
                    <td>
                        @if($order->status === 'pending_payment')
                            @if($order->payment && $order->payment->proof_path)
                                <a href="{{ asset('storage/' . $order->payment->proof_path) }}" target="_blank" class="pc-btn-sm">View</a>
                            @endif
                        @endif
                    </td>
                    <td class="is-right">
                        @if($order->status === 'pending_payment')
                            @if($order->payment && !$order->payment->validated_at)
                                <form action="{{ route('partner.orders.show', $order->id) }}" method="POST" style="display:inline">
                                    @csrf
                                    @method('POST')
                                    <button type="submit" name="action" value="approve" class="pc-btn-sm pc-btn-sm--ok">Approve</button>
                                    <button type="submit" name="action" value="reject" class="pc-btn-sm pc-btn-sm--error">Reject</button>
                                </form>
                            @endif
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
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