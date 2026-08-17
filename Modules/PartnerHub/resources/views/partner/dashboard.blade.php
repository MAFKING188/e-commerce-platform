@section('title', 'Partner Dashboard | LUWI')

@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@vite('Modules/PartnerHub/resources/assets/js/dashboard.js')
@vite('resources/js/partner.js')
@endsection

<x-app-layout>
@include('partials.partner-nav')

<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Partner Dashboard</span>
        <h1 class="pc-title">Welcome, {{ $partner->name }}.</h1>
    </div>
</div>

<div class="pc-stats">
    @include('partials.partner.stat-card', [
        'label' => 'Active Inventory',
        'value' => $inventoryCount,
        'footnoteLink' => route('partner.inventory.index'),
        'footnoteLinkLabel' => 'Manage Portfolio',
    ])
    @include('partials.partner.stat-card', [
        'label' => 'Pending Payout',
        'value' => '$' . number_format($pendingPayout, 2),
        'footnoteLink' => route('partner.payouts.index'),
        'footnoteLinkLabel' => 'Earnings History',
    ])
    @include('partials.partner.stat-card', [
        'label' => 'Total Revenue',
        'value' => '$' . number_format($totalRevenue, 2),
        'accent' => true,
        'footnote' => 'Gross lifecycle value',
    ])
</div>

<div class="pc-card pc-chart">
    <div class="pc-chart__head">
        <h2 class="pc-chart__title">Sales Performance</h2>
        <span class="pc-stat__foot">Last 30 days</span>
    </div>
    <div class="pc-chart__canvas">
        <canvas id="salesChart" data-labels="{{ json_encode($chartData['labels']) }}" data-values="{{ json_encode($chartData['values']) }}"></canvas>
    </div>
</div>

<div class="pc-card">
    <div class="pc-card__head">
        <h2 class="pc-section-title">Recent Partner Orders</h2>
        <a href="{{ route('partner.orders.index') }}" class="pc-section-link">View all orders</a>
    </div>
    @if ($recentOrders->isEmpty())
        @include('partials.partner.empty-state', [
            'icon' => 'receipt',
            'title' => 'No orders yet',
            'text' => 'Orders containing your items will appear here once customers start purchasing your pieces.',
        ])
    @else
        <div class="pc-table-wrap pc-table-wrap--flush">
            <table class="pc-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th class="is-right">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentOrders as $order)
                        <tr>
                            <td class="is-numeric">#{{ $order->id }}</td>
                            <td class="is-muted">{{ $order->created_at->diffForHumans() }}</td>
                            <td>@include('partials.partner.status-badge', ['status' => $order->status])</td>
                            <td class="is-right">
                                <a href="{{ route('partner.orders.show', $order->id) }}" class="pc-section-link">Details</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
</x-app-layout>