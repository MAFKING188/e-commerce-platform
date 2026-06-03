@extends('layouts.app')

@section('title', 'Archive Collection | SmartShop')

@section('styles')
<style>
    .shop-header { margin-bottom: 4rem; }
    .shop-header h1 { font-size: 3rem; font-weight: 800; color: var(--text-900); }

    .filter-panel {
        background: var(--surface-100);
        padding: 2.5rem;
        border-radius: 1.5rem;
        border: 1px solid var(--border);
        margin-bottom: 6rem;
    }

    .filter-form {
        display: grid;
        grid-template-columns: 1.5fr 1fr 1fr 1fr auto;
        gap: 1.5rem;
        align-items: flex-end;
    }

    .filter-input {
        width: 100%;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        border: 1px solid var(--border);
        background: var(--surface-200);
        color: var(--text-900);
    }

    .catalog-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 3rem;
    }

    @media (max-width: 1024px) {
        .shop-header h1 { font-size: 2.5rem; }
        .filter-form { gap: 1rem; }
    }

    @media (max-width: 768px) {
        .shop-header { margin-bottom: 2rem; text-align: center; }
        .filter-panel { padding: 1.5rem; margin-bottom: 3rem; }
        .filter-form { grid-template-columns: 1fr; }
        .catalog-grid { 
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); 
            gap: 1.25rem; 
        }
    }
</style>
@endsection

@section('content')

<div class="shop-header">
    <h1>The Archive</h1>
    <p style="color: var(--text-600); font-size: 1.1rem; margin-top: 0.5rem;">Curated essentials for the modern lifestyle.</p>
</div>

<div class="filter-panel">
    <form action="{{ route('shop') }}" method="GET" class="filter-form">
        <div class="form-group">
            <label style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.75rem; display: block; color: var(--text-400);">Search Catalog</label>
            <input type="text" name="search" value="{{ request('search') }}" class="filter-input" placeholder="Keyword...">
        </div>
        
        <div class="form-group">
            <label style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.75rem; display: block; color: var(--text-400);">Collection</label>
            <select name="category" class="filter-input">
                <option value="">All Categories</option>
                @foreach(\App\Models\Category::all() as $category)
                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.75rem; display: block; color: var(--text-400);">Price Range</label>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <input type="number" name="min_price" value="{{ request('min_price') }}" class="filter-input" placeholder="Min" style="padding: 1rem 0.75rem;">
                <input type="number" name="max_price" value="{{ request('max_price') }}" class="filter-input" placeholder="Max" style="padding: 1rem 0.75rem;">
            </div>
        </div>

        <div class="form-group">
            <label style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; margin-bottom: 0.75rem; display: block; color: var(--text-400);">Sort By</label>
            <select name="sort" class="filter-input">
                <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Newest</option>
                <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary" style="padding: 1.25rem 2.5rem; border-radius: 12px;">Apply Filter</button>
    </form>
</div>

@if($products->isEmpty())
    <div style="text-align: center; padding: 12rem 0;">
        <h2 style="font-weight: 800; color: var(--text-400);">No results in archive.</h2>
        <a href="{{ route('shop') }}" style="color: var(--brand-accent); font-weight: 700; margin-top: 1rem; display: block;">View All Products</a>
    </div>
@else
    <div class="catalog-grid">
        @foreach($products as $product)
            @include('components.product-card', ['product' => $product])
        @endforeach
    </div>

    <div style="margin-top: 5rem;">
        {{ $products->links('partials.pagination') }}
    </div>
    @endif

    @endsection
