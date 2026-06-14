@extends('layouts.app')

@section('title', 'My Inventory | Partner Dashboard')

@section('styles')
<style>
    .inventory-header { margin-bottom: 4rem; display: flex; justify-content: space-between; align-items: flex-end; }
    .inventory-table-wrap { background: var(--surface-100); border-radius: 1.5rem; border: 1px solid var(--border); overflow-x: auto; }
    .inventory-table { width: 100%; border-collapse: collapse; text-align: left; min-width: 800px; }
    .inventory-table th { padding: 1.5rem; background: var(--surface-200); font-weight: 700; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.05em; color: var(--text-600); border-bottom: 1px solid var(--border); }
    .inventory-table td { padding: 1.5rem; border-bottom: 1px solid var(--border); font-size: 0.95rem; }
    .inventory-table tr:last-child td { border-bottom: none; }
    .product-meta { display: flex; align-items: center; gap: 1rem; }
    .product-img { width: 48px; height: 48px; border-radius: 0.75rem; object-fit: cover; background: var(--surface-200); }
    .link-edit { color: var(--brand-accent); font-weight: 700; text-decoration: none; }
    .link-delete { color: #ef4444; font-weight: 700; border: none; background: none; cursor: pointer; padding: 0; }
    .bulk-controls { margin-bottom: 2rem; display: flex; gap: 1rem; align-items: center; background: var(--surface-100); padding: 1rem 1.5rem; border-radius: 1rem; border: 1px solid var(--border); }
</style>
@endsection

@section('content')
@include('partials.partner-nav')

<div class="inventory-header">
    <div>
        <span class="cat-badge">Inventory Management</span>
        <h1 style="font-size: 2.5rem; font-weight: 800; margin-top: 1rem;">My Inventory.</h1>
    </div>
    <a href="{{ route('partner.inventory.create') }}" class="btn btn-primary">Add New Product</a>
</div>

<form action="{{ route('partner.inventory.bulk-action') }}" method="POST">
    @csrf
    <div class="bulk-controls">
        <select name="action" class="btn btn-ghost" style="padding: 0.5rem 1rem; font-size: 0.85rem;">
            <option value="">Bulk Actions</option>
            <option value="delete">Delete Selected</option>
        </select>
        <button type="submit" class="btn btn-primary" style="padding: 0.5rem 1.5rem; font-size: 0.85rem;">Apply</button>
    </div>

    <div class="inventory-table-wrap">
        <table class="inventory-table">
            <thead>
                <tr>
                    <th style="width: 40px;"><input type="checkbox" id="select-all"></th>
                    <th>Product</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th style="text-align: right;">Actions</th>
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
                                    <div style="font-weight: 700;">{{ $product->name }}</div>
                                    <div style="font-size: 0.8rem; color: var(--text-400);">{{ $product->category->name ?? 'Uncategorized' }}</div>
                                </div>
                            </div>
                        </td>
                        <td style="font-weight: 600;">${{ number_format($product->price, 2) }}</td>
                        <td>
                            <span class="cat-badge" style="background: {{ $product->stock < 5 ? '#fee2e2' : 'var(--brand-accent-soft)' }}; color: {{ $product->stock < 5 ? '#991b1b' : 'var(--brand-accent)' }};">
                                {{ $product->stock }} in stock
                            </span>
                        </td>
                        <td style="text-align: right;">
                            <a href="{{ route('partner.inventory.edit', $product->id) }}" class="link-edit">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 4rem; color: var(--text-400);">
                            No products in your inventory yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</form>

<div style="margin-top: 3rem;">
    {{ $products->links() }}
</div>

<script>
    document.getElementById('select-all').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.product-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
</script>
@endsection
