@section('title', 'Product Inventory | LUWI Admin')

<x-app-layout>
@include('partials.admin-nav')

<div class="admin-header">
    <div>
        <span class="cat-badge">Stock Control</span>
        <h1>Product Inventory.</h1>
    </div>
    <a href="{{ route('admin.products.create') }}" class="btn btn-primary">Add New Product</a>
</div>

<div class="inventory-table-wrap">
    <table class="inventory-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Price</th>
                <th>Status</th>
                <th class="align-right">Actions</th>
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
                    <td class="td-category">
                        {{ $product->category->name ?? 'Uncategorized' }}
                    </td>
                    <td class="td-price">
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
                    <td class="align-right">
                        <div class="action-links">
                            <a href="{{ route('admin.products.edit', $product->id) }}" class="link-edit">Edit</a>
                            <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" class="inline-form" onsubmit="return confirm('Archive this product permanently?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="link-delete">Delete</button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="pagination-wrap">
    {{ $products->links('partials.pagination') }}
</div>

</x-app-layout>
