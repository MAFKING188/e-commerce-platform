@extends('layouts.app')

@section('title', 'Admin Command Center | LUWI')

@section('styles')
<style>
    .admin-header { margin-bottom: 4rem; display: flex; justify-content: space-between; align-items: flex-end; }
    .admin-header h1 { font-size: 3rem; font-weight: 800; color: var(--text-900); }

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
        box-shadow: var(--shadow-sm);
    }

    .stat-card label {
        display: block;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--text-400);
        margin-bottom: 1rem;
        letter-spacing: 0.1em;
    }

    .stat-card .value {
        font-size: 2.5rem;
        font-weight: 800;
        color: var(--text-900);
    }

    .quick-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }

    .action-panel {
        background: var(--surface-100);
        padding: 3rem;
        border-radius: 2rem;
        border: 1px solid var(--border);
        text-decoration: none;
        transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        min-height: 300px;
    }

    .action-panel:hover {
        transform: translateY(-10px);
        border-color: var(--brand-accent);
        box-shadow: var(--shadow-lg);
    }

    .action-panel h2 { font-size: 2rem; font-weight: 800; color: var(--text-900); }
    .action-panel p { color: var(--text-600); margin-top: 1rem; max-width: 300px; }

    .icon-box {
        width: 60px;
        height: 60px;
        background: var(--brand-accent-soft);
        color: var(--brand-accent);
        border-radius: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 2rem;
    }

    @media (max-width: 768px) {
        .quick-actions { grid-template-columns: 1fr; }
    }
</style>
@endsection

@section('content')
<div class="admin-header">
    <div>
        <span style="font-weight: 800; color: var(--brand-accent); text-transform: uppercase; letter-spacing: 0.2em;">Platform Overview</span>
        <h1>Command Center</h1>
    </div>
    <div style="font-weight: 700; color: var(--text-400);">{{ now()->format('l, F d') }}</div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <label>Total Revenue</label>
        <div class="value">${{ number_format(\App\Models\Order::where('status', 'completed')->sum('total_price'), 0) }}</div>
    </div>
    <div class="stat-card">
        <label>Active Orders</label>
        <div class="value">{{ \App\Models\Order::where('status', 'pending')->count() }}</div>
    </div>
    <div class="stat-card">
        <label>Catalog Size</label>
        <div class="value">{{ \App\Models\Product::count() }}</div>
    </div>
    <div class="stat-card">
        <label>Members</label>
        <div class="value">{{ \App\Models\User::count() }}</div>
    </div>
</div>

<div class="quick-actions">
    <a href="{{ route('admin.orders.index') }}" class="action-panel">
        <div>
            <div class="icon-box" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            </div>
            <h2>Fulfillment</h2>
            <p>Monitor real-time acquisitions, process payments, and manage shipping status.</p>
        </div>
        <span class="btn btn-primary" style="width: fit-content; background: #3b82f6;">Order Queue</span>
    </a>

    <a href="{{ route('products.index') }}" class="action-panel">
        <div>
            <div class="icon-box">
                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <h2>Inventory</h2>
            <p>Manage products, adjust stock levels, and curate the luxury catalog.</p>
        </div>
        <span class="btn btn-primary" style="width: fit-content;">Manage Archive</span>
    </a>

    <a href="{{ route('users.index') }}" class="action-panel">
        <div>
            <div class="icon-box" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
            </div>
            <h2>Member Base</h2>
            <p>View user behavior, manage permissions, and track premium registrations.</p>
        </div>
        <span class="btn btn-primary" style="width: fit-content; background: #10b981;">Manage Users</span>
    </a>
</div>
@endsection
