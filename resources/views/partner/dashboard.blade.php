@extends('layouts.app')

@section('title', 'Partner Dashboard | LUWI')

@section('styles')
<style>
    .partner-header { margin-bottom: 4rem; }
    .partner-header h1 { font-size: 3rem; font-weight: 800; color: var(--text-900); }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 2rem;
        margin-bottom: 4rem;
    }

    .stat-card {
        background: var(--surface-100);
        padding: 2rem;
        border-radius: 1.5rem;
        border: 1px solid var(--border);
    }
</style>
@endsection

@section('content')
<div class="partner-header">
    <span class="cat-badge">Partner Dashboard</span>
    <h1>Welcome, {{ $partner->name }}.</h1>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <label>Active Inventory</label>
        <div class="value" style="font-size: 2.5rem; font-weight: 800;">{{ $inventoryCount }}</div>
    </div>
</div>

<div class="recent-activity" style="background: var(--surface-100); border-radius: 2rem; border: 1px solid var(--border); padding: 3rem;">
    <h2 style="font-size: 1.75rem; font-weight: 800; margin-bottom: 2rem;">Recent Partner Orders</h2>
    
    @if($recentOrders->isEmpty())
        <p>No orders containing your items yet.</p>
    @else
        <ul>
            @foreach($recentOrders as $order)
                <li>Order #{{ $order->id }} - {{ $order->created_at->diffForHumans() }}</li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
