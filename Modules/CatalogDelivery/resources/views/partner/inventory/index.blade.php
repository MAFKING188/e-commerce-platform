@section('title', 'My Inventory | Partner Dashboard')

<x-app-layout>
@include('partials.partner-nav')

<div class="inventory-header">
    <div>
        <span class="cat-badge">Inventory Management</span>
        <h1 class="inventory-title">My Inventory.</h1>
    </div>
    <a href="{{ route('partner.inventory.create') }}" class="btn btn-primary">Add New Product</a>
</div>

<form action="{{ route('partner.inventory.bulk-action') }}" method="POST">
    @csrf
    <div class="bulk-controls">
        <select name="action" class="btn btn-ghost bulk-select">
            <option value="">Bulk Actions</option>
            <option value="delete">Delete Selected</option>
        </select>
        <button type="submit" class="btn btn-primary bulk-apply">Apply</button>
    </div>

    <div class="inventory-table-wrap">
        <table class="inventory-table">
            <thead>
                <tr>
                    <th class="th-checkbox"><input type="checkbox" id="select-all"></th>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th class="align-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($products as $product)
                    <tr>
                        <td><input type="checkbox" name="product_ids[]" value="{{ $product->id }}" class="product-checkbox"></td>
                        <td>
                            <div class="product-meta">
                                <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="product-img">
                                <div>
                                    <div class="product-name">{{ $product->name }}</div>
                                    <div class="product-category-sub">{{ $product->category->name ?? 'Uncategorized' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="inventory-price">${{ number_format($product->price, 2) }}</td>
                        <td>
                            <span class="cat-badge" style="background: {{ $product->stock < 5 ? '#fee2e2' : 'var(--brand-accent-soft)' }}; color: {{ $product->stock < 5 ? '#991b1b' : 'var(--brand-accent)' }};">
                                {{ $product->stock }} in stock
                            </span>
                        </td>
                        <td class="align-right">
                            <a href="{{ route('partner.inventory.edit', $product->id) }}" class="link-edit">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="inventory-empty">
                            No products in your inventory yet.
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

<script>
    document.getElementById('select-all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.product-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
</script>
</x-app-layout>
