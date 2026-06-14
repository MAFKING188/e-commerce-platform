@extends('layouts.app')

@section('title', 'Financial Insights | Partner Dashboard')

@section('content')
@include('partials.partner-nav')

<div style="margin-bottom: 4rem;">
    <span class="cat-badge">Financial Performance</span>
    <h1 style="font-size: 2.5rem; font-weight: 800; margin-top: 1rem;">My Earnings.</h1>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 4rem;">
    <div style="background: var(--surface-100); padding: 2.5rem; border-radius: 2rem; border: 1px solid var(--border);">
        <label style="display: block; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--text-400); margin-bottom: 0.5rem;">Total Processed Earnings</label>
        <div style="font-size: 3rem; font-weight: 800; color: var(--brand-accent);">@money($stats['total_earned'])</div>
    </div>
    <div style="background: var(--surface-100); padding: 2.5rem; border-radius: 2rem; border: 1px solid var(--border);">
        <label style="display: block; font-size: 0.8rem; font-weight: 700; text-transform: uppercase; color: var(--text-400); margin-bottom: 0.5rem;">Pending Payout Balance</label>
        <div style="font-size: 3rem; font-weight: 800; color: var(--text-900);">@money($stats['pending_payout'])</div>
    </div>
</div>

<div class="inventory-table-wrap" style="background: var(--surface-100); border-radius: 1.5rem; border: 1px solid var(--border); overflow-x: auto;">
    <table class="inventory-table" style="width: 100%; border-collapse: collapse; text-align: left; min-width: 900px;">
        <thead>
            <tr>
                <th style="padding: 1.5rem; background: var(--surface-200); font-size: 0.85rem; font-weight: 700; color: var(--text-600);">Order Ref</th>
                <th style="padding: 1.5rem; background: var(--surface-200); font-size: 0.85rem; font-weight: 700; color: var(--text-600);">Calculated Date</th>
                <th style="padding: 1.5rem; background: var(--surface-200); font-size: 0.85rem; font-weight: 700; color: var(--text-600);">Net Amount</th>
                <th style="padding: 1.5rem; background: var(--surface-200); font-size: 0.85rem; font-weight: 700; color: var(--text-600);">Status</th>
                <th style="padding: 1.5rem; background: var(--surface-200); font-size: 0.85rem; font-weight: 700; color: var(--text-600); text-align: right;">Fulfillment Ref</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($payouts as $payout)
                <tr>
                    <td style="padding: 1.5rem; border-bottom: 1px solid var(--border); font-weight: 700;">
                        <a href="{{ route('partner.orders.show', $payout->order_id) }}" style="color: var(--brand-accent);">#{{ $payout->order_id }}</a>
                    </td>
                    <td style="padding: 1.5rem; border-bottom: 1px solid var(--border); color: var(--text-600);">
                        {{ $payout->created_at->format('M d, Y') }}
                    </td>
                    <td style="padding: 1.5rem; border-bottom: 1px solid var(--border); font-weight: 800;">
                        @money($payout->amount)
                    </td>
                    <td style="padding: 1.5rem; border-bottom: 1px solid var(--border);">
                        <span class="cat-badge" style="background: {{ $payout->status === 'processed' ? 'var(--brand-accent-soft)' : '#fef2f2' }}; color: {{ $payout->status === 'processed' ? 'var(--brand-accent)' : '#991b1b' }};">
                            {{ ucfirst($payout->status) }}
                        </span>
                    </td>
                    <td style="padding: 1.5rem; border-bottom: 1px solid var(--border); text-align: right; font-family: monospace; font-size: 0.85rem;">
                        {{ $payout->transaction_reference ?? 'Pending Transfer' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" style="padding: 4rem; text-align: center; color: var(--text-400);">No earnings recorded yet. Continue fulfilling orders to see your balance grow.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

<div style="margin-top: 3rem;">
    {{ $payouts->links() }}
</div>
@endsection
