@section('title', 'Product Inventory | LUWI Admin')

@section('styles')
<style>
    .admin-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 4rem;
    }

    .admin-header h1 {
        font-size: 2.5rem;
        font-weight: 800;
        letter-spacing: -0.04em;
    }

    .inventory-table-wrap {
        background: var(--surface-100);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        overflow: hidden;
        box-shadow: var(--shadow-md);
    }

    .inventory-table {
        width: 100%;
        border-collapse: collapse;
    }

    .inventory-table th {
        text-align: left;
        padding: 1.25rem 2rem;
        background: var(--surface-300);
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.1em;
        color: var(--text-600);
        border-bottom: 1px solid var(--border);
    }

    .inventory-table td {
        padding: 1.5rem 2rem;
        border-bottom: 1px solid var(--border);
        vertical-align: middle;
        color: var(--text-900);
    }

    .inventory-table tr:last-child td {
        border-bottom: none;
    }

    .item-preview {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }

    .item-thumb {
        width: 64px;
        height: 64px;
        border-radius: var(--radius-sm);
        object-fit: cover;
        background: var(--surface-200);
    }

    .stock-pill {
        display: inline-block;
        padding: 0.4rem 0.8rem;
        border-radius: 2rem;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .stock-high { background: rgba(16, 185, 129, 0.1); color: #10b981; }
    .stock-low { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
    .stock-none { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

    .action-links {
        display: flex;
        gap: 1.5rem;
    }

    .link-edit { color: var(--brand-accent); font-weight: 700; text-decoration: none; font-size: 0.875rem; }
    .link-delete { color: var(--error); font-weight: 700; text-decoration: none; font-size: 0.875rem; cursor: pointer; background: none; border: none; padding: 0; }
</style>
@endsection

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
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                <tr>
                    <td>
                        <div class="item-preview">
                            <img src="{{ $product->image_url }}" onerror="this.src='https://via.placeholder.com/100?text=Item'" class="item-thumb" alt="">
                            <div>
                                <div style="font-weight: 700; color: var(--text-900);">{{ $product->name }}</div>
                                <div style="font-size: 0.75rem; color: var(--text-400);">ID: #{{ $product->id }}</div>
                            </div>
                        </div>
                    </td>
                    <td style="color: var(--text-600); font-weight: 600;">
                        {{ $product->category->name ?? 'Uncategorized' }}
                    </td>
                    <td style="font-weight: 700; color: var(--brand-accent);">
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
                    <td style="text-align: right;">
                        <div class="action-links" style="justify-content: flex-end;">
                            <a href="{{ route('admin.products.edit', $product->id) }}" class="link-edit">Edit</a>
                            <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Archive this product permanently?')">
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

<div style="margin-top: 3rem;">
    {{ $products->links('partials.pagination') }}
</div>

</x-app-layout>
