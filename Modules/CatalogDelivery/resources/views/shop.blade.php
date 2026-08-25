@section('title', 'Archive Collection | SmartShop')

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
        <p class="shop-subtitle">Curated essentials for the modern lifestyle.</p>
    </div>
    <button class="btn btn-ghost shop-filter-btn" onclick="toggleFilters(true)">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
        Filter
    </button>
</div>

<!-- Filter Drawer -->
<div class="filter-overlay" id="filterOverlay" onclick="toggleFilters(false)"></div>
<div class="filter-drawer" id="filterDrawer">
    <div class="shop-drawer-head">
        <h2 class="shop-drawer-title">Refine Search</h2>
        <button onclick="toggleFilters(false)" class="shop-close-btn">
            <svg width="24" height="24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <form action="{{ route('shop') }}" method="GET" class="filter-form">
        <div class="form-group">
            <label class="shop-field-label">Search Catalog</label>
            <input type="text" name="search" value="{{ request('search') }}" class="filter-input" placeholder="Keyword...">
        </div>
        
        <div class="form-group">
            <label class="shop-field-label">Collection</label>
            <select name="category" class="filter-input">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ request('category') == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label class="shop-field-label">Supplier</label>
            <select name="supplier" class="filter-input">
                <option value="">All Suppliers</option>
                @foreach($suppliers as $supplier)
                    <option value="{{$supplier->id}}" {{ request('supplier') == $supplier->id ? 'selected' : '' }}>
                        {{$supplier->name}}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label class="shop-field-label">Price Range</label>
            <div class="shop-price-range">
                <input type="number" name="min_price" value="{{ request('min_price') }}" class="filter-input" placeholder="Min">
                <input type="number" name="max_price" value="{{ request('max_price') }}" class="filter-input" placeholder="Max">
            </div>
        </div>

        <div class="form-group">
            <label class="shop-field-label">Sort By</label>
            <select name="sort" class="filter-input">
                <option value="latest" {{ request('sort') == 'latest' ? 'selected' : '' }}>Newest</option>
                <option value="price_asc" {{ request('sort') == 'price_asc' ? 'selected' : '' }}>Price: Low to High</option>
                <option value="price_desc" {{ request('sort') == 'price_desc' ? 'selected' : '' }}>Price: High to Low</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary shop-apply-btn">Apply Filter</button>
        <a href="{{ route('shop') }}" class="btn btn-ghost shop-reset-btn">Reset Archive</a>
    </form>
</div>

@if($products->isEmpty())
    <div class="shop-empty">
        <h2 class="shop-empty-title">No results in archive.</h2>
        <a href="{{ route('shop') }}" class="shop-empty-link">View All Products</a>
    </div>
@else
    <div class="catalog-grid">
        @foreach($products as $product)
            @include('catalogdelivery::components.product-card', ['product' => $product])
        @endforeach
    </div>

    <div class="shop-pagination">
        {{ $products->links('partials.pagination') }}
    </div>
@endif

</x-app-layout>


