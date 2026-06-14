@extends('layouts.app')

@section('title', 'Partner Dashboard | LUWI')

@section('styles')
<style>
    .partner-header { margin-bottom: 4rem; }
    .partner-header h1 { font-size: 3rem; font-weight: 800; color: var(--text-900); }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 2rem;
        margin-bottom: 4rem;
    }

    .stat-card {
        background: var(--surface-100);
        padding: 2rem;
        border-radius: 1.5rem;
        border: 1px solid var(--border);
    }

    .chart-container {
        background: var(--surface-100);
        padding: 2.5rem;
        border-radius: 2rem;
        border: 1px solid var(--border);
        margin-bottom: 4rem;
        height: 400px;
    }
</style>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
@endsection

@section('content')
@include('partials.partner-nav')

<div class="partner-header">
    <span class="cat-badge">Partner Dashboard</span>
    <h1>Welcome, {{ $partner->name }}.</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <label>Active Inventory</label>
        <div class="value" style="font-size: 2.5rem; font-weight: 800; color: var(--text-900);">{{ $inventoryCount }}</div>
        <a href="{{ route('partner.inventory.index') }}" style="font-size: 0.8rem; color: var(--brand-accent); font-weight: 700;">Manage Portfolio →</a>
    </div>
    <div class="stat-card">
        <label>Pending Payout</label>
        <div class="value" style="font-size: 2.5rem; font-weight: 800; color: var(--text-900);">${{ number_format($pendingPayout, 2) }}</div>
        <a href="{{ route('partner.payouts.index') }}" style="font-size: 0.8rem; color: var(--brand-accent); font-weight: 700;">Earnings History →</a>
    </div>
    <div class="stat-card">
        <label>Total Revenue</label>
        <div class="value" style="font-size: 2.5rem; font-weight: 800; color: var(--brand-accent);">${{ number_format($totalRevenue, 2) }}</div>
        <div style="font-size: 0.8rem; color: var(--text-400);">Gross lifecycle value</div>
    </div>
</div>

<div class="chart-container">
    <h3 style="font-size: 1.25rem; font-weight: 800; margin-bottom: 2rem;">Sales Performance (Last 30 Days)</h3>
    <canvas id="salesChart"></canvas>
</div>

<div class="recent-activity" style="background: var(--surface-100); border-radius: 2rem; border: 1px solid var(--border); padding: 3rem;">
...
@section('scripts')
<script>
    const ctx = document.getElementById('salesChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($chartData['labels']) !!},
            datasets: [{
                label: 'Daily Revenue ($)',
                data: {!! json_encode($chartData['values']) !!},
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#3b82f6'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' },
                    ticks: { font: { weight: '600' } }
                },
                x: {
                    grid: { display: false },
                    ticks: { font: { weight: '600' } }
                }
            }
        }
    });
</script>
@endsection
    <h2 style="font-size: 1.75rem; font-weight: 800; margin-bottom: 2rem;">Recent Partner Orders</h2>
    
    @if($recentOrders->isEmpty())
        <p style="color: var(--text-400);">No orders containing your items yet.</p>
    @else
        <div class="inventory-table-wrap" style="border: none;">
            <table class="inventory-table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="padding: 1rem; text-align: left; color: var(--text-600); font-size: 0.8rem; text-transform: uppercase;">Order ID</th>
                        <th style="padding: 1rem; text-align: left; color: var(--text-600); font-size: 0.8rem; text-transform: uppercase;">Date</th>
                        <th style="padding: 1rem; text-align: left; color: var(--text-600); font-size: 0.8rem; text-transform: uppercase;">Status</th>
                        <th style="padding: 1rem; text-align: right; color: var(--text-600); font-size: 0.8rem; text-transform: uppercase;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentOrders as $order)
                        <tr style="border-bottom: 1px solid var(--border);">
                            <td style="padding: 1.5rem; font-weight: 700;">#{{ $order->id }}</td>
                            <td style="padding: 1.5rem; color: var(--text-600);">{{ $order->created_at->diffForHumans() }}</td>
                            <td style="padding: 1.5rem;">
                                <span class="cat-badge" style="background: var(--brand-accent-soft); color: var(--brand-accent);">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td style="padding: 1.5rem; text-align: right;">
                                <a href="{{ route('partner.orders.show', $order->id) }}" style="color: var(--brand-accent); font-weight: 700;">Details</a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
