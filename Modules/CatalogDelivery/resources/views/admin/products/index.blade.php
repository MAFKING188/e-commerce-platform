@section('title', 'Product Inventory | LUWI Admin')

<x-app-layout>
@include('partials.admin-nav')

<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Stock Control</span>
        <h1 class="pc-title">Product Inventory</h1>
    </div>
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary pc-btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
        Add New Product
    </a>
</div>

<div class="pc-table-wrap">
    <table class="pc-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Price</th>
                <th>Status</th>
                <th class="is-right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                <tr>
                    <td>
                        <div class="item-preview">
                            <img src="{{ $product->image_url }}" onerror="this.src='https://via.placeholder.com/100?text=Item'" class="item-thumb" alt="">
                            <div>
                                <div class="item-name">{{ $product->name }}</div>
                                <div class="item-id">ID: #{{ $product->id }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="is-muted">
                        {{ $product->category->name ?? 'Uncategorized' }}
                    </td>
                    <td class="is-strong">
                        ${{ number_format($product->price, 2) }}
                    </td>
                    <td>
                        @if($product->stock > 10)
                            <span class="stock-pill stock-high">In Stock ({{ $product->stock }})</span>
                        @elseif($product->stock > 0)
                            <span class="stock-pill stock-low">Low Stock ({{ $product->stock }})</span>
                        @else
                            <span class="stock-pill stock-none">Out of Stock</span>
                        @endif
                    </td>
                    <td class="is-right">
                        <div class="pc-row-actions pc-row-actions--end">
                            <a href="{{ route('admin.products.edit', $product->id) }}" class="pc-btn-sm">Edit</a>
                            <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" data-confirm="Archive this product permanently?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="pc-btn-sm pc-btn-sm--danger">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="pc-pagination">
    {{ $products->links('partials.pagination') }}
</div>

</x-app-layout>