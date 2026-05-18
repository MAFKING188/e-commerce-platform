@extends('layouts.app')

@section('title', 'Refine Product | LUWI Admin')

@section('styles')
<style>
    .editor-stage {
        max-width: 800px;
        margin: 0 auto;
    }

    .editor-header {
        margin-bottom: 4rem;
        text-align: center;
    }

    .editor-header h1 {
        font-size: 3rem;
        font-weight: 800;
        letter-spacing: -0.04em;
    }

    .editor-card {
        background: white;
        padding: 4rem;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        box-shadow: var(--shadow-lg);
    }

    .field-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }

    .field-full {
        grid-column: span 2;
    }

    .image-preview-zone {
        margin-top: 1rem;
        padding: 2rem;
        border: 2px dashed var(--border);
        border-radius: var(--radius-md);
        text-align: center;
        background: var(--surface-200);
    }

    .current-img {
        width: 120px;
        height: 120px;
        border-radius: 8px;
        object-fit: cover;
        margin-bottom: 1rem;
        border: 1px solid var(--border);
    }
</style>
@endsection

@section('content')

<div class="editor-stage">
    <div class="editor-header">
        <span class="stat-label">Inventory Management</span>
        <h1>Edit Product.</h1>
    </div>

    <div class="editor-card">
        <form action="{{ route('products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="field-grid">
                <div class="form-group field-full">
                    <label class="form-label">Product Name</label>
                    <input type="text" name="name" class="auth-input" value="{{ old('name', $product->name) }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="auth-input">
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ $product->category_id == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Price ($)</label>
                    <input type="number" step="0.01" name="price" class="auth-input" value="{{ old('price', $product->price) }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Stock Level</label>
                    <input type="number" name="stock" class="auth-input" value="{{ old('stock', $product->stock) }}" required>
                </div>

                <div class="form-group field-full">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="auth-input" rows="6" style="resize: none;">{{ old('description', $product->description) }}</textarea>
                </div>

                <div class="form-group field-full">
                    <label class="form-label">Product Imagery</label>
                    <div class="image-preview-zone">
                        @if($product->images->first())
                            <img src="{{ asset('storage/' . str_replace('storage/', '', $product->images->first()->url)) }}" class="current-img" alt="">
                            <p style="font-size: 0.8rem; color: var(--text-600); margin-bottom: 1rem;">Current active image</p>
                        @endif
                        <input type="file" name="image" class="auth-input" style="background: white;">
                    </div>
                </div>
            </div>

            <div style="margin-top: 3rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" style="flex: 2; padding: 1.25rem;">Update Product Details</button>
                <a href="{{ route('products.index') }}" class="btn btn-ghost" style="flex: 1;">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection
