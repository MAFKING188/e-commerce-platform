@extends('layouts.app')

@section('title', 'Vendor Ecosystem | LUWI Admin')

@section('styles')
<style>
    .admin-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 4rem; }
    .admin-header h1 { font-size: 3rem; font-weight: 800; color: var(--text-900); }

    .vendor-table-wrap {
        background: var(--surface-100);
        border-radius: 2rem;
        border: 1px solid var(--border);
        overflow: hidden;
        box-shadow: var(--shadow-md);
    }

    .vendor-table { width: 100%; border-collapse: collapse; }
    .vendor-table th {
        text-align: left;
        padding: 1.5rem 2rem;
        background: var(--surface-300);
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--text-600);
        letter-spacing: 0.1em;
    }

    .vendor-table td { padding: 1.5rem 2rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
    
    .vendor-info { display: flex; align-items: center; gap: 1rem; }
    .vendor-icon {
        width: 40px;
        height: 40px;
        background: var(--brand-accent-soft);
        color: var(--brand-accent);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
    }

    .product-count {
        background: var(--surface-300);
        color: var(--text-600);
        padding: 0.25rem 0.75rem;
        border-radius: 2rem;
        font-size: 0.7rem;
        font-weight: 700;
    }
</style>
@endsection

@section('content')
@include('partials.admin-nav')

<div class="admin-header">
    <div>
        <span class="cat-badge">Supply Chain</span>
        <h1>Ecosystem.</h1>
    </div>
    <a href="{{ route('vendors.create') }}" class="btn btn-primary">Establish New Partner</a>
</div>

<div class="vendor-table-wrap">
    <table class="vendor-table">
        <thead>
            <tr>
                <th>Partner</th>
                <th>Supply Scale</th>
                <th>Contact Registry</th>
                <th style="text-align: right;">Operations</th>
            </tr>
        </thead>
        <tbody>
            @forelse($vendors as $vendor)
                <tr>
                    <td>
                        <div class="vendor-info">
                            <div class="vendor-icon">V</div>
                            <div>
                                <div style="font-weight: 700; color: var(--text-900);">{{ $vendor->name }}</div>
                                <div style="font-size: 0.8rem; color: var(--text-400);">Registered Partner</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="product-count">{{ $vendor->products_count }} Collections</span>
                    </td>
                    <td style="color: var(--text-600); font-size: 0.9rem;">
                        {{ $vendor->contact_info ?? 'N/A' }}
                    </td>
                    <td style="text-align: right;">
                        <div style="display: flex; gap: 1rem; justify-content: flex-end; align-items: center;">
                            <a href="{{ route('vendors.show', $vendor->id) }}" style="color: var(--brand-accent); font-weight: 800; font-size: 0.7rem; text-transform: uppercase; text-decoration: none;">
                                Inventory
                            </a>
                            <a href="{{ route('vendors.edit', $vendor->id) }}" style="color: var(--text-600); font-weight: 800; font-size: 0.7rem; text-transform: uppercase; text-decoration: none;">
                                Edit
                            </a>
                            <form action="{{ route('vendors.destroy', $vendor->id) }}" method="POST" onsubmit="return confirm('Terminate this vendor relationship?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" style="color: var(--error); background: none; border: none; font-weight: 800; font-size: 0.7rem; text-transform: uppercase; cursor: pointer;">
                                    Terminate
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" style="text-align: center; padding: 4rem; color: var(--text-400);">No partner vendors established yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
