@section('title', 'Financial Registry | Admin Command Center')

<x-app-layout>
@include('partials.admin-nav')

<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Financial Command</span>
        <h1 class="pc-title">Partner Payouts</h1>
    </div>
</div>

<div class="pc-table-wrap">
    <table class="pc-table">
        <thead>
            <tr>
                <th>Partner</th>
                <th>Order</th>
                <th>Amount</th>
                <th>Status</th>
                <th class="is-right">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payouts as $payout)
                <tr>
                    <td>
                        <div class="is-strong">{{ $payout->partner->name }}</div>
                        <div class="is-muted">{{ $payout->partner->user->email }}</div>
                    </td>
                    <td>
                        <a href="{{ route('admin.orders.show', $payout->order_id) }}" class="pc-section-link">#{{ $payout->order_id }}</a>
                    </td>
                    <td class="is-strong">
                        @money($payout->amount)
                    </td>
                    <td>
                        @include('partials.partner.status-badge', ['status' => $payout->status])
                    </td>
                    <td class="is-right">
                        @if ($payout->status === 'pending')
                            <form action="{{ route('admin.payouts.process', $payout->id) }}" method="POST" class="pc-row-actions pc-row-actions--end">
                                @csrf
                                <input type="text" name="transaction_reference" placeholder="Ref#" class="pc-filter__input pc-filter__input--sm" required>
                                <button type="submit" class="pc-btn-sm pc-btn-sm--ok">Mark Paid</button>
                            </form>
                        @else
                            <div class="is-muted">Ref: {{ $payout->transaction_reference }}</div>
                            <div class="is-muted">{{ $payout->processed_at?->format('M d, H:i') ?? '—' }}</div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="pc-pagination">
    {{ $payouts->links() }}
</div>
</x-app-layout>