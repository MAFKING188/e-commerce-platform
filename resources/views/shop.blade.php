@section('title', 'Archive Collection | SmartShop')

@section('styles')
<style>
    .shop-header { 
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 4rem; 
    }
    .shop-header h1 { font-size: 3rem; font-weight: 800; color: var(--text-900); }

    /* FILTER DRAWER */
    .filter-drawer {
        position: fixed;
        top: 0;
        right: 0;
        width: 400px;
        height: 100vh;
        background: var(--nav-bg);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-left: 1px solid var(--border);
        z-index: 2000;
        padding: 3rem;
        transform: translateX(100%);
        transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    .filter-drawer.active {
        transform: translateX(0);
    }

    .filter-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,0.3);
        z-index: 1999;
        opacity: 0;
        visibility: hidden;
        transition: all 0.4s ease;
    }

    .filter-overlay.active {
        opacity: 1;
        visibility: visible;
    }

    .filter-form {
        display: flex;
        flex-direction: column;
        gap: 2rem;
    }

    .filter-input {
        width: 100%;
        padding: 1rem 1.5rem;
        border-radius: 12px;
        border: 1px solid var(--border);
        background: var(--surface-200);
        color: var(--text-900);
        font-size: 0.9rem;
        font-weight: 500;
        outline: none;
        transition: all 0.3s ease;
    }

    .filter-input:focus {
        border-color: var(--brand-accent);
        background: var(--surface-100);
        box-shadow: 0 0 0 3px var(--brand-accent-soft);
    }

    .catalog-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 3rem;
    }

    @media (max-width: 1024px) {
        .shop-header h1 { font-size: 2.5rem; }
    }

    @media (max-width: 768px) {
        .filter-drawer { width: 100%; }
        .shop-header { margin-bottom: 2rem; flex-direction: column; text-align: center; gap: 1rem; }
        .catalog-grid { 
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); 
            gap: 1.25rem; 
        }
    }
</style>
@endsection

@section('scripts')
<script>
    function toggleFilters(open) {
        const drawer = document.getElementById('filterDrawer');
        const overlay = document.getElementById('filterOverlay');
        
        if (open) {
            drawer.classList.add('active');
            overlay.classList.add('active');
            document.body.style.overflow = 'hidden';
        } else {
            drawer.classList.remove('active');
            overlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        }
    }
</script>
@endsection
<x-app-layout>

<div class="shop-header">
    <div>
        <h1>The Archive</h1>
        <p style="color: var(--text-600); font-size: 1.1rem; margin-top: 0.5rem;">Curated essentials for the modern lifestyle.</p>
    </div>
    <button class="btn btn-ghost" onclick="toggleFilters(true)" style="gap: 0.75rem;">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
        Filter
    </button>
</div>

<!-- Filter Drawer -->
<div class="filter-overlay" id="filterOverlay" onclick="toggleFilters(false)"></div>
<div class="filter-drawer" id="filterDrawer">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h2 style="font-weight: 800; letter-spacing: -0.02em;">Refine Search</h2>
        <button onclick="toggleFilters(false)" style="background: none; border: none; cursor: pointer; color: var(--text-400);">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

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
                <input type="number" name="min_price" value="{{ request('min_price') }}" class="filter-input" placeholder="Min">
                <input type="number" name="max_price" value="{{ request('max_price') }}" class="filter-input" placeholder="Max">
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

        <button type="submit" class="btn btn-primary" style="padding: 1.25rem; width: 100%; border-radius: 12px; margin-top: 1rem;">Apply Filter</button>
        <a href="{{ route('shop') }}" class="btn btn-ghost" style="width: 100%;">Reset Archive</a>
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

</x-app-layout>


