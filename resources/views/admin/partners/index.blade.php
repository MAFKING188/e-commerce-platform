@extends('layouts.app')

@section('title', 'Partner Ecosystem | LUWI Admin')

@section('styles')
<style>
    .admin-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 4rem; }
    .admin-header h1 { font-size: 3rem; font-weight: 800; color: var(--text-900); }

    .partner-table-wrap {
        background: var(--surface-100);
        border-radius: 2rem;
        border: 1px solid var(--border);
        overflow: hidden;
        box-shadow: var(--shadow-md);
    }

    .partner-table { width: 100%; border-collapse: collapse; }
    .partner-table th {
        text-align: left;
        padding: 1.5rem 2rem;
        background: var(--surface-300);
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--text-600);
        letter-spacing: 0.1em;
    }

    .partner-table td { padding: 1.5rem 2rem; border-bottom: 1px solid var(--border); vertical-align: middle; }
    
    .partner-info { display: flex; align-items: center; gap: 1rem; }
    .partner-icon {
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
    <a href="{{ route('admin.partners.create') }}" class="btn btn-primary">Establish New Partner</a>
</div>

<div class="partner-table-wrap">
    <table class="partner-table">
        <thead>
            <tr>
                <th>Partner</th>
                <th>Supply Scale</th>
                <th>Contact Registry</th>
                <th style="text-align: right;">Operations</th>
            </tr>
        </thead>
        <tbody>
            @forelse($partners as $partner)
                <tr>
                    <td>
                        <div class="partner-info">
                            <div class="partner-icon">V</div>
                            <div>
                                <div style="font-weight: 700; color: var(--text-900);">{{ $partner->name }}</div>
                                <div style="font-size: 0.8rem; color: var(--text-400);">Registered Partner</div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="product-count">{{ $partner->products_count }} Collections</span>
                    </td>
                    <td style="color: var(--text-600); font-size: 0.9rem;">
                        {{ $partner->contact_info ?? 'N/A' }}
                    </td>
                    <td style="text-align: right;">
                        <div style="display: flex; gap: 1rem; justify-content: flex-end; align-items: center;">
                            <a href="{{ route('admin.partners.show', $partner->id) }}" style="color: var(--brand-accent); font-weight: 800; font-size: 0.7rem; text-transform: uppercase; text-decoration: none;">
                                Inventory
                            </a>
                            <a href="{{ route('admin.partners.edit', $partner->id) }}" style="color: var(--text-600); font-weight: 800; font-size: 0.7rem; text-transform: uppercase; text-decoration: none;">
                                Edit
                            </a>
                            <form action="{{ route('admin.partners.destroy', $partner->id) }}" method="POST" onsubmit="return confirm('Terminate this partner relationship?')">
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
                    <td colspan="4" style="text-align: center; padding: 4rem; color: var(--text-400);">No partner partners established yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
