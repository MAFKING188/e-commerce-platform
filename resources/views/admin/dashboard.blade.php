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
@include('partials.admin-nav')

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
        <div class="value">${{ number_format($stats['revenue'], 0) }}</div>
    </div>
    <div class="stat-card">
        <label>Active Orders</label>
        <div class="value">{{ $stats['active_orders'] }}</div>
    </div>
    <div class="stat-card">
        <label>Catalog Size</label>
        <div class="value">{{ $stats['catalog_size'] }}</div>
    </div>
    <div class="stat-card" style="{{ $stats['low_stock_count'] > 0 ? 'border-color: #ef4444; background: #fef2f2;' : '' }}">
        <label style="{{ $stats['low_stock_count'] > 0 ? 'color: #ef4444;' : '' }}">Low Stock Items</label>
        <div class="value" style="{{ $stats['low_stock_count'] > 0 ? 'color: #ef4444;' : '' }}">{{ $stats['low_stock_count'] }}</div>
    </div>
</div>

<div class="quick-actions" style="margin-bottom: 4rem; display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
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
                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <h2>Member Base</h2>
            <p>Administer user permissions, verify identities, and manage the community.</p>
        </div>
        <span class="btn btn-primary" style="width: fit-content; background: #10b981;">
            Access Registry ({{ $stats['pending_users'] }})
        </span>
    </a>

    <a href="{{ route('vendors.index') }}" class="action-panel">
        <div>
            <div class="icon-box" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <h2>Supply Chain</h2>
            <p>Manage partner vendors, track sources, and map inventory origins.</p>
        </div>
        <span class="btn btn-primary" style="width: fit-content; background: #8b5cf6;">Vendor Ecosystem</span>
    </a>

    <a href="{{ route('admin.reviews.index') }}" class="action-panel">
        <div>
            <div class="icon-box" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
            </div>
            <h2>Community Feedback</h2>
            <p>Moderate user reviews, curate testimonials, and manage platform sentiment.</p>
        </div>
        <span class="btn btn-primary" style="width: fit-content; background: #f59e0b;">
            Moderate ({{ $stats['pending_reviews'] }})
        </span>
    </a>
</div>

<div class="recent-activity" style="background: var(--surface-100); border-radius: 2rem; border: 1px solid var(--border); padding: 3rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
        <h2 style="font-size: 1.75rem; font-weight: 800; color: var(--text-900);">Pulse: Recent Acquisitions</h2>
        <a href="{{ route('admin.orders.index') }}" style="color: var(--brand-accent); font-weight: 700; text-decoration: none;">View All</a>
    </div>

    @if($recentOrders->isEmpty())
        <div style="text-align: center; padding: 4rem; color: var(--text-400); font-weight: 600;">
            No recent activity recorded.
        </div>
    @else
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; border-bottom: 1px solid var(--border);">
                        <th style="padding: 1rem 0; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--text-400);">Order ID</th>
                        <th style="padding: 1rem 0; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--text-400);">Member</th>
                        <th style="padding: 1rem 0; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--text-400);">Value</th>
                        <th style="padding: 1rem 0; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--text-400);">Status</th>
                        <th style="padding: 1rem 0; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: var(--text-400);">Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentOrders as $order)
                        <tr style="border-bottom: 1px solid var(--border); transition: background 0.3s ease;">
                            <td style="padding: 1.5rem 0; font-weight: 800; color: var(--brand-accent);">#{{ $order->id }}</td>
                            <td style="padding: 1.5rem 0; font-weight: 600;">{{ $order->user->name }}</td>
                            <td style="padding: 1.5rem 0; font-weight: 800;">${{ number_format($order->total_price, 2) }}</td>
                            <td style="padding: 1.5rem 0;">
                                <span style="font-size: 0.65rem; font-weight: 800; text-transform: uppercase; padding: 0.25rem 0.75rem; border-radius: 20px; 
                                    {{ $order->status == 'completed' ? 'background: rgba(16, 185, 129, 0.1); color: #10b981;' : 'background: rgba(245, 158, 11, 0.1); color: #f59e0b;' }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                            <td style="padding: 1.5rem 0; color: var(--text-400); font-size: 0.9rem;">{{ $order->created_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
