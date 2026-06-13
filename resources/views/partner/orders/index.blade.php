@extends('layouts.app')

@section('title', 'My Orders | Partner Dashboard')

@section('content')
<div style="margin-bottom: 4rem;">
    <span class="cat-badge">Order Fulfillment</span>
    <h1 style="font-size: 2.5rem; font-weight: 800; margin-top: 1rem;">My Orders.</h1>
</div>

<div class="inventory-table-wrap">
    <table class="inventory-table">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Date</th>
                <th>Status</th>
                <th style="text-align: right;">View</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($orders as $order)
                <tr>
                    <td>#{{ $order->id }}</td>
                    <td>{{ $order->created_at->format('M d, Y') }}</td>
                    <td>{{ ucfirst($order->status) }}</td>
                    <td style="text-align: right;">
                        <a href="{{ route('partner.orders.show', $order->id) }}" class="btn btn-ghost">View Details</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div style="margin-top: 3rem;">
    {{ $orders->links() }}
</div>
@endsection
