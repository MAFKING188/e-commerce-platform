@extends('layouts.app')

@section('title', 'Financial Registry | Admin Command Center')

@section('content')
@include('partials.admin-nav')

<div style="margin-bottom: 4rem;">
    <span class="cat-badge">Financial Command</span>
    <h1 style="font-size: 2.5rem; font-weight: 800; margin-top: 1rem;">Partner Payouts.</h1>
</div>

<div class="inventory-table-wrap" style="background: var(--surface-100); border-radius: 1.5rem; border: 1px solid var(--border); overflow-x: auto;">
    <table class="inventory-table" style="width: 100%; border-collapse: collapse; text-align: left; min-width: 1000px;">
        <thead>
            <tr>
                <th style="padding: 1.5rem; background: var(--surface-200); font-size: 0.85rem; font-weight: 700; color: var(--text-600);">Partner</th>
                <th style="padding: 1.5rem; background: var(--surface-200); font-size: 0.85rem; font-weight: 700; color: var(--text-600);">Order</th>
                <th style="padding: 1.5rem; background: var(--surface-200); font-size: 0.85rem; font-weight: 700; color: var(--text-600);">Amount</th>
                <th style="padding: 1.5rem; background: var(--surface-200); font-size: 0.85rem; font-weight: 700; color: var(--text-600);">Status</th>
                <th style="padding: 1.5rem; background: var(--surface-200); font-size: 0.85rem; font-weight: 700; color: var(--text-600); text-align: right;">Action</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($payouts as $payout)
                <tr>
                    <td style="padding: 1.5rem; border-bottom: 1px solid var(--border);">
                        <div style="font-weight: 700;">{{ $payout->partner->name }}</div>
                        <div style="font-size: 0.8rem; color: var(--text-400);">{{ $payout->partner->user->email }}</div>
                    </td>
                    <td style="padding: 1.5rem; border-bottom: 1px solid var(--border);">
                        <a href="{{ route('admin.orders.show', $payout->order_id) }}" style="color: var(--brand-accent); font-weight: 600;">#{{ $payout->order_id }}</a>
                    </td>
                    <td style="padding: 1.5rem; border-bottom: 1px solid var(--border); font-weight: 700;">
                        @money($payout->amount)
                    </td>
                    <td style="padding: 1.5rem; border-bottom: 1px solid var(--border);">
                        <span class="cat-badge" style="background: {{ $payout->status === 'processed' ? 'var(--brand-accent-soft)' : '#fef2f2' }}; color: {{ $payout->status === 'processed' ? 'var(--brand-accent)' : '#991b1b' }};">
                            {{ ucfirst($payout->status) }}
                        </span>
                    </td>
                    <td style="padding: 1.5rem; border-bottom: 1px solid var(--border); text-align: right;">
                        @if ($payout->status === 'pending')
                            <form action="{{ route('admin.payouts.process', $payout->id) }}" method="POST" style="display: flex; gap: 0.5rem; justify-content: flex-end; align-items: center;">
                                @csrf
                                <input type="text" name="transaction_reference" placeholder="Ref#" class="btn btn-ghost" style="padding: 0.4rem 0.8rem; width: 120px;" required>
                                <button type="submit" class="btn btn-primary" style="padding: 0.4rem 1rem;">Mark Paid</button>
                            </form>
                        @else
                            <div style="font-size: 0.85rem; color: var(--text-400);">Ref: {{ $payout->transaction_reference }}</div>
                            <div style="font-size: 0.75rem; color: var(--text-400);">{{ $payout->processed_at->format('M d, H:i') }}</div>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div style="margin-top: 3rem;">
    {{ $payouts->links() }}
</div>
@endsection
