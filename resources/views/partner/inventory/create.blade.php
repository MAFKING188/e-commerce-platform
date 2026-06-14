@extends('layouts.app')

@section('title', 'Add Product | Partner Dashboard')

@section('styles')
<style>
    .form-container { background: var(--surface-100); border-radius: 2rem; border: 1px solid var(--border); padding: 3rem; max-width: 800px; margin: 0 auto; }
    .form-group { margin-bottom: 2rem; }
    .form-group label { display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.75rem; color: var(--text-600); }
    .form-control { width: 100%; padding: 1rem; border-radius: 1rem; border: 1px solid var(--border); background: var(--surface-200); font-family: inherit; font-size: 1rem; transition: 0.2s; }
    .form-control:focus { outline: none; border-color: var(--brand-accent); box-shadow: 0 0 0 4px var(--brand-accent-soft); }
    textarea.form-control { min-height: 150px; resize: vertical; }
</style>
@endsection

@section('content')
@include('partials.partner-nav')

<div style="margin-bottom: 4rem; text-align: center;">
    <span class="cat-badge">Inventory Expansion</span>
    <h1 style="font-size: 2.5rem; font-weight: 800; margin-top: 1rem;">Add New Piece.</h1>
</div>

<div class="form-container">
    <form action="{{ route('partner.inventory.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div class="form-group">
            <label for="name">Product Name</label>
            <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Minimalist Oak Chair" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div class="form-group">
                <label for="price">Price (USD)</label>
                <input type="number" name="price" id="price" class="form-control" step="0.01" placeholder="0.00" required>
            </div>
            <div class="form-group">
                <label for="stock">Initial Stock</label>
                <input type="number" name="stock" id="stock" class="form-control" placeholder="0" required>
            </div>
        </div>

        <div class="form-group">
            <label for="category_id">Category</label>
            <select name="category_id" id="category_id" class="form-control" required>
                <option value="">Select a Category</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="description">Detailed Narrative</label>
            <textarea name="description" id="description" class="form-control" placeholder="Describe the craftsmanship and soul of this piece..." required></textarea>
        </div>

        <div class="form-group">
            <label for="images">Product Narrative Visuals (JPEG/PNG)</label>
            <input type="file" name="images[]" id="images" class="form-control" multiple>
            <p style="font-size: 0.8rem; color: var(--text-400); margin-top: 0.5rem;">Select one or more images to build the product's visual story.</p>
        </div>

        <div style="margin-top: 3rem; display: flex; gap: 1.5rem;">
            <button type="submit" class="btn btn-primary" style="flex: 2;">Publish to Catalog</button>
            <a href="{{ route('partner.inventory.index') }}" class="btn btn-ghost" style="flex: 1;">Cancel</a>
        </div>
    </form>
</div>
@endsection
