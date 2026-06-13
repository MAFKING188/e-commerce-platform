@extends('layouts.app')

@section('title', 'My Inventory | Partner Dashboard')

@section('content')
<div style="margin-bottom: 4rem;">
    <span class="cat-badge">Inventory Management</span>
    <h1 style="font-size: 2.5rem; font-weight: 800; margin-top: 1rem;">My Inventory.</h1>
</div>

<div style="margin-bottom: 2rem;">
    <a href="{{ route('partner.inventory.create') }}" class="btn btn-primary">Add New Product</a>
</div>

<div class="inventory-table-wrap">
    <table class="inventory-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Stock</th>
                <th style="text-align: right;">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($products as $product)
                <tr>
                    <td>{{ $product->name }}</td>
                    <td>${{ number_format($product->price, 2) }}</td>
                    <td>{{ $product->stock }}</td>
                    <td style="text-align: right;">
                        <a href="{{ route('partner.inventory.edit', $product->id) }}" class="link-edit">Edit</a>
                        <form action="{{ route('partner.inventory.destroy', $product->id) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Remove product?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="link-delete" style="margin-left: 1rem;">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div style="margin-top: 3rem;">
    {{ $products->links() }}
</div>
@endsection
