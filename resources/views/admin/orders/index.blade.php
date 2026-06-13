@extends('layouts.app')

@section('title', 'Order Management | Admin')

@section('styles')
<style>
    .admin-orders-header { margin-bottom: 2rem; }
    .order-table { width: 100%; border-collapse: collapse; background: var(--surface-100); border-radius: 12px; overflow: hidden; border: 1px solid var(--border); }
    .order-table th, .order-table td { padding: 1rem; text-align: left; border-bottom: 1px solid var(--border); }
    .order-table th { background: var(--surface-200); font-size: 0.75rem; text-transform: uppercase; color: var(--text-400); }
    .status-pill { 
        padding: 0.35rem 0.75rem; 
        border-radius: 50px; 
        font-size: 0.7rem; 
        font-weight: 900; 
        text-transform: uppercase; 
        color: white !important;
    }
    .status-pending { background: #92400e !important; }
    .status-cancelled { background: #991b1b !important; }
    .status-paid { background: #166534 !important; }
    .status-completed { background: #1e40af !important; }
    .btn-sm { padding: 0.4rem 0.8rem; font-size: 0.8rem; border-radius: 4px; border: 1px solid var(--border); background: var(--surface-200); cursor: pointer; }
    .btn-complete { background: #10b981; color: white; border: none; }
</style>
@endsection

@section('content')
@include('partials.admin-nav')

<div class="admin-orders-header">
    <h1>Order Management</h1>
    <p>Monitor and process all customer acquisitions.</p>
</div>

<table class="order-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Customer</th>
            <th>Total</th>
            <th>Status</th>
            <th>Date</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($orders as $order)
        <tr>
            <td>#{{ $order->id }}</td>
            <td>{{ $order->user->name }}</td>
            <td>${{ number_format($order->total_price, 2) }}</td>
            <td>
                <span class="status-pill status-{{ strtolower($order->status) }}">
                    {{ $order->status }}
                </span>
            </td>
            <td>{{ $order->created_at->format('M d, H:i') }}</td>
            <td>
                <div style="display: flex; gap: 0.5rem;">
                    <a href="{{ route('admin.orders.show', $order->id) }}" class="btn-sm">View</a>
                    @if($order->status === 'paid')
                        <form action="{{ route('admin.orders.complete', $order->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn-sm btn-complete">Mark Shipped</button>
                        </form>
                    @endif
                </div>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection
