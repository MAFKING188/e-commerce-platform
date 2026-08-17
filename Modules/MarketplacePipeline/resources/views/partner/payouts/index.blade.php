@section('title', 'Financial Insights | Partner Dashboard')

@section('scripts')
@vite('resources/js/partner.js')
@endsection

<x-app-layout>
@include('partials.partner-nav')

<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Financial Performance</span>
        <h1 class="pc-title">My Earnings.</h1>
    </div>
</div>

<div class="pc-stats">
    @include('partials.partner.stat-card', [
        'label' => 'Total Processed Earnings',
        'value' => \App\Services\CurrencyService::format($stats['total_earned']),
    ])
    @include('partials.partner.stat-card', [
        'label' => 'Pending Payout Balance',
        'value' => \App\Services\CurrencyService::format($stats['pending_payout']),
    ])
</div>

<div class="pc-table-wrap">
    <table class="pc-table">
        <thead>
            <tr>
                <th>Order Ref</th>
                <th>Calculated Date</th>
                <th>Net Amount</th>
                <th>Status</th>
                <th class="is-right">Fulfillment Ref</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payouts as $payout)
                <tr>
                    <td>
                        <a href="{{ route('partner.orders.show', $payout->order_id) }}" class="pc-section-link">#{{ $payout->order_id }}</a>
                    </td>
                    <td class="is-muted">{{ $payout->created_at->format('M d, Y') }}</td>
                    <td class="is-numeric">@money($payout->amount)</td>
                    <td>@include('partials.partner.status-badge', ['status' => $payout->status])</td>
                    <td class="is-right is-ref is-muted">{{ $payout->transaction_reference ?? 'Pending Transfer' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        @include('partials.partner.empty-state', [
                            'icon' => 'receipt',
                            'title' => 'No earnings recorded yet',
                            'text' => 'Continue fulfilling orders and your payout history will appear here.',
                        ])
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div class="pagination-wrap">
    {{ $payouts->links() }}
</div>
</x-app-layout>