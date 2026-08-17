@section('title', 'My Inventory | Partner Dashboard')

@section('scripts')
@vite('resources/js/partner.js')
<script>
    document.getElementById('select-all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.product-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
</script>
@endsection

<x-app-layout>
@include('partials.partner-nav')

@if (session('error'))
    <div class="pc-flash pc-flash--error">⚠ {{ session('error') }}</div>
@endif

<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Inventory Management</span>
        <h1 class="pc-title">My Inventory.</h1>
    </div>
    <a href="{{ route('partner.inventory.create') }}" class="btn btn-primary">Add New Product</a>
</div>

<form action="{{ route('partner.inventory.index') }}" method="GET" class="pc-filter">
    <input type="text" name="search" class="pc-filter__input" placeholder="Search products" value="{{ request('search') }}">
    <select name="stock" class="pc-filter__select">
        <option value="">All stock levels</option>
        <option value="in" {{ request('stock') === 'in' ? 'selected' : '' }}>In stock</option>
        <option value="low" {{ request('stock') === 'low' ? 'selected' : '' }}>Low or out of stock</option>
    </select>
    <button type="submit" class="btn btn-primary pc-btn-sm">Apply</button>
    @if (request()->hasAny(['search', 'stock']))
        <a href="{{ route('partner.inventory.index') }}" class="pc-filter__reset">Clear filters</a>
    @endif
</form>

<form action="{{ route('partner.inventory.bulk-action') }}" method="POST">
    @csrf
    <div class="pc-bulk">
        <select name="action" class="pc-bulk__select">
            <option value="">Bulk Actions</option>
            <option value="delete">Delete Selected</option>
        </select>
        <button type="submit" class="btn btn-primary pc-btn-sm"
            data-confirm
            data-confirm-title="Delete selected products?"
            data-confirm-message="This will permanently remove the selected products from your inventory. This cannot be undone."
            data-confirm-label="Delete">
            Apply
        </button>
    </div>

    <div class="pc-table-wrap">
        <table class="pc-table">
            <thead>
                <tr>
                    <th class="pc-table__check"><input type="checkbox" id="select-all" aria-label="Select all products"></th>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th class="is-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td><input type="checkbox" name="product_ids[]" value="{{ $product->id }}" class="product-checkbox" aria-label="Select {{ $product->name }}"></td>
                        <td>
                            <div class="pc-product">
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="pc-product__img">
                                <div>
                                    <div class="pc-product__name">{{ $product->name }}</div>
                                    <div class="pc-product__cat">{{ $product->category->name ?? 'Uncategorized' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="is-numeric">${{ number_format($product->price, 2) }}</td>
                        <td>
                            @include('partials.partner.status-badge', [
                                'status' => $product->stock < 5 ? 'low' : 'in stock',
                                'variant' => $product->stock < 5 ? 'danger' : 'ok',
                            ])
                        </td>
                        <td class="is-right">
                            <a href="{{ route('partner.inventory.edit', $product->id) }}" class="pc-section-link">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5">
                            @include('partials.partner.empty-state', [
                                'icon' => request()->hasAny(['search', 'stock']) ? 'search' : 'box',
                                'title' => request()->hasAny(['search', 'stock']) ? 'No matching products' : 'No products in your inventory yet',
                                'text' => request()->hasAny(['search', 'stock'])
                                    ? 'Try adjusting your search or clearing the filters to see all products.'
                                    : 'Add your first product to start selling on the marketplace.',
                                'actionLabel' => request()->hasAny(['search', 'stock']) ? '' : 'Add New Product',
                                'actionUrl' => request()->hasAny(['search', 'stock']) ? '' : route('partner.inventory.create'),
                            ])
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</form>

<div class="pagination-wrap">
    {{ $products->links() }}
</div>
</x-app-layout>