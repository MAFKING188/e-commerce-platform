@extends('layouts.app')

@section('title', 'Order Details | Partner Dashboard')

@section('styles')
<style>
    .order-header { margin-bottom: 4rem; }
    .order-meta-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem; margin-bottom: 4rem; }
    .meta-card { background: var(--surface-100); padding: 2rem; border-radius: 1.5rem; border: 1px solid var(--border); }
    .meta-card label { display: block; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--text-400); margin-bottom: 0.5rem; }
    .meta-card .value { font-size: 1.25rem; font-weight: 800; color: var(--text-900); }
    
    .items-table-wrap { background: var(--surface-100); border-radius: 1.5rem; border: 1px solid var(--border); overflow: hidden; }
    .items-table { width: 100%; border-collapse: collapse; }
    .items-table th { padding: 1.5rem; background: var(--surface-200); text-align: left; font-size: 0.85rem; font-weight: 700; color: var(--text-600); border-bottom: 1px solid var(--border); }
    .items-table td { padding: 1.5rem; border-bottom: 1px solid var(--border); }
    .product-info { display: flex; align-items: center; gap: 1rem; }
    .product-img { width: 64px; height: 64px; border-radius: 0.75rem; object-fit: cover; }
</style>
@endsection

@section('content')
@include('partials.partner-nav')

<div class="order-header">
    <a href="{{ route('partner.orders.index') }}" class="btn btn-ghost" style="margin-bottom: 2rem;">← Back to All Orders</a>
    <span class="cat-badge">Order Fulfillment</span>
    <h1 style="font-size: 2.5rem; font-weight: 800; margin-top: 1rem;">Order #{{ $order->id }}</h1>
</div>

<div class="order-meta-grid">
    <div class="meta-card">
        <label>Placement Date</label>
        <div class="value">{{ $order->created_at->format('M d, Y') }}</div>
    </div>
    <div class="meta-card">
        <label>Fulfillment Status</label>
        <div class="value">
            <span style="color: {{ $order->status === 'completed' ? 'var(--success)' : 'var(--brand-accent)' }}">
                {{ ucfirst($order->status) }}
            </span>
        </div>
    </div>
    <div class="meta-card">
        <label>Client Reference</label>
        <div class="value">{{ $order->user->name }}</div>
    </div>
</div>

<div class="items-table-wrap">
    <h3 style="padding: 1.5rem; border-bottom: 1px solid var(--border); font-size: 1.25rem; font-weight: 800;">Items to Fulfill</h3>
    <table class="items-table">
        <thead>
            <tr>
                <th>Piece</th>
                <th>Quantity</th>
                <th>Price Each</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @php 
                $partnerItems = $order->items->filter(function($item) {
                    return $item->product->partners->contains(auth()->user()->id); 
                    // Note: This logic assumes partner ID is linked to user ID. 
                    // Let's refine this to use the actual Partner model in the controller or here.
                });
                $partnerSubtotal = 0;
            @endphp
            
            @foreach($order->items as $item)
                @if($item->product->partners->where('user_id', auth()->id())->first())
                    @php $partnerSubtotal += ($item->price * $item->quantity); @endphp
                    <tr>
                        <td>
                            <div class="product-info">
                                <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}" class="product-img">
                                <div>
                                    <div style="font-weight: 700;">{{ $item->product->name }}</div>
                                    <div style="font-size: 0.85rem; color: var(--text-400);">{{ $item->product->category->name ?? 'Collection' }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="font-weight: 600;">{{ $item->quantity }}</td>
                        <td>${{ number_format($item->price, 2) }}</td>
                        <td style="text-align: right; font-weight: 700;">${{ number_format($item->price * $item->quantity, 2) }}</td>
                    </tr>
                @endif
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: right; padding: 2rem; font-weight: 700; color: var(--text-600);">Partner Subtotal:</td>
                <td style="text-align: right; padding: 2rem; font-size: 1.5rem; font-weight: 800; color: var(--brand-accent);">${{ number_format($partnerSubtotal, 2) }}</td>
            </tr>
        </tfoot>
    </table>
</div>

<div style="margin-top: 4rem; background: var(--brand-accent-soft); border: 1px solid var(--brand-accent); border-radius: 1.5rem; padding: 2rem;">
    <h4 style="color: var(--brand-accent); font-weight: 800; margin-bottom: 1rem;">Logistics Note</h4>
    <p style="font-size: 0.95rem; color: var(--brand-accent); opacity: 0.8;">
        Please ensure all pieces are inspected for quality before dispatch. Once shipped, please update the central logistics hub.
    </p>
</div>
@endsection
