@section('title', 'Admin Command Center | LUWI')

<x-app-layout>
@include('partials.admin-nav')

<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Platform Overview</span>
        <h1 class="pc-title">Command Center</h1>
    </div>
    <div class="pc-header__date">{{ now()->format('l, F d') }}</div>
</div>

<div class="pc-stats">
    <div class="pc-stat">
        <span class="pc-stat__label">Total Revenue</span>
        <span class="pc-stat__value is-accent">${{ number_format($stats['revenue'], 0) }}</span>
    </div>
    <div class="pc-stat">
        <span class="pc-stat__label">Active Orders</span>
        <span class="pc-stat__value">{{ $stats['active_orders'] }}</span>
    </div>
    <div class="pc-stat">
        <span class="pc-stat__label">Catalog Size</span>
        <span class="pc-stat__value">{{ $stats['catalog_size'] }}</span>
    </div>
    <div class="pc-stat {{ $stats['low_stock_count'] > 0 ? 'is-alert' : '' }}">
        <span class="pc-stat__label">Low Stock Items</span>
        <span class="pc-stat__value">{{ $stats['low_stock_count'] }}</span>
    </div>
</div>

<div class="quick-actions">
    <a href="{{ route('admin.orders.index') }}" class="action-panel">
        <div>
            <div class="icon-box">
                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
            </div>
            <h2>Fulfillment</h2>
            <p>Monitor real-time acquisitions, process payments, and manage shipping status.</p>
        </div>
        <span class="btn btn-primary">Order Queue</span>
    </a>

    <a href="{{ route('admin.products.index') }}" class="action-panel">
        <div>
            <div class="icon-box">
                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <h2>Inventory</h2>
            <p>Manage products, adjust stock levels, and curate the luxury catalog.</p>
        </div>
        <span class="btn btn-primary">Manage Archive</span>
    </a>

    <a href="{{ route('admin.users.index') }}" class="action-panel">
        <div>
            <div class="icon-box">
                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            </div>
            <h2>Member Base</h2>
            <p>Administer user permissions, verify identities, and manage the community.</p>
        </div>
        <span class="btn btn-primary">
            Access Registry ({{ $stats['pending_users'] }})
        </span>
    </a>

    <a href="{{ route('admin.partners.index') }}" class="action-panel">
        <div>
            <div class="icon-box">
                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
            </div>
            <h2>Supply Chain</h2>
            <p>Manage partner partners, track sources, and map inventory origins.</p>
        </div>
        <span class="btn btn-primary">Partner Ecosystem</span>
    </a>

    <a href="{{ route('admin.reviews.index') }}" class="action-panel">
        <div>
            <div class="icon-box">
                <svg width="32" height="32" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
            </div>
            <h2>Community Feedback</h2>
            <p>Moderate user reviews, curate testimonials, and manage platform sentiment.</p>
        </div>
        <span class="btn btn-primary">
            Moderate ({{ $stats['pending_reviews'] }})
        </span>
    </a>
</div>

<div class="pc-card">
    <div class="pc-card__head">
        <div>
            <h2 class="pc-card__title">Pulse: Recent Acquisitions</h2>
        </div>
        <a href="{{ route('admin.orders.index') }}" class="pc-section-link">View All</a>
    </div>

    @if($recentOrders->isEmpty())
        @include('partials.partner.empty-state', [
            'icon' => 'receipt',
            'title' => 'No recent activity',
            'text' => 'New acquisitions will appear here as members complete their purchases.',
        ])
    @else
        <div class="pc-table-wrap">
            <table class="pc-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Member</th>
                        <th>Value</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentOrders as $order)
                        <tr>
                            <td class="is-numeric">#{{ $order->id }}</td>
                            <td class="is-muted">{{ $order->user->name }}</td>
                            <td class="is-strong">${{ number_format($order->total_price, 2) }}</td>
                            <td>@include('partials.partner.status-badge', ['status' => $order->status])</td>
                            <td class="is-muted">{{ $order->created_at->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
</x-app-layout>