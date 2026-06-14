@extends('layouts.app')

@section('title', 'Edit Product | Partner Dashboard')

@section('styles')
<style>
    .form-container { background: var(--surface-100); border-radius: 2rem; border: 1px solid var(--border); padding: 3rem; max-width: 800px; margin: 0 auto; }
    .form-group { margin-bottom: 2rem; }
    .form-group label { display: block; font-weight: 700; font-size: 0.9rem; margin-bottom: 0.75rem; color: var(--text-600); }
    .form-control { width: 100%; padding: 1rem; border-radius: 1rem; border: 1px solid var(--border); background: var(--surface-200); font-family: inherit; font-size: 1rem; transition: 0.2s; }
    .form-control:focus { outline: none; border-color: var(--brand-accent); box-shadow: 0 0 0 4px var(--brand-accent-soft); }
    .media-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 1.5rem; margin-top: 1rem; }
    .media-item { position: relative; border-radius: 1rem; overflow: hidden; border: 1px solid var(--border); background: var(--surface-200); aspect-ratio: 1; cursor: move; }
    .media-item img { width: 100%; height: 100%; object-fit: cover; }
    .media-actions { position: absolute; top: 0.5rem; right: 0.5rem; display: flex; gap: 0.5rem; }
    .media-btn { background: rgba(255, 255, 255, 0.9); border: none; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #ef4444; font-size: 0.8rem; box-shadow: var(--shadow-sm); }
</style>
<!-- Include SortableJS for drag and drop reordering -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endsection

@section('content')
@include('partials.partner-nav')

<div style="margin-bottom: 4rem; text-align: center;">
    <span class="cat-badge">Inventory Refinement</span>
    <h1 style="font-size: 2.5rem; font-weight: 800; margin-top: 1rem;">Refine Piece.</h1>
</div>

<div class="form-container">
    <form action="{{ route('partner.inventory.update', $product->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <div class="form-group">
            <label for="name">Product Name</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ $product->name }}" required>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
            <div class="form-group">
                <label for="price">Price (USD)</label>
                <input type="number" name="price" id="price" class="form-control" step="0.01" value="{{ $product->price }}" required>
            </div>
            <div class="form-group">
                <label for="stock">Current Stock</label>
                <input type="number" name="stock" id="stock" class="form-control" value="{{ $product->stock }}" required>
            </div>
        </div>

        <div class="form-group">
            <label for="category_id">Category</label>
            <select name="category_id" id="category_id" class="form-control" required>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ $product->category_id == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="form-group">
            <label for="description">Detailed Narrative</label>
            <textarea name="description" id="description" class="form-control" required>{{ $product->description }}</textarea>
        </div>

        <div class="form-group">
            <label>Product Narrative Gallery (Drag to Reorder)</label>
            <div class="media-gallery" id="media-gallery">
                @foreach($product->images->sortBy('position') as $image)
                    <div class="media-item" data-id="{{ $image->id }}">
                        <img src="{{ asset($image->url) }}" alt="Product Image">
                        <div class="media-actions">
                            <button type="button" class="media-btn" onclick="deleteVisual({{ $product->id }}, {{ $image->id }}, this)" title="Remove Visual">
                                ✕
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="form-group">
            <label for="images">Add More Narrative Visuals</label>
            <input type="file" name="images[]" id="images" class="form-control" multiple>
        </div>

        <div style="margin-top: 3rem; display: flex; gap: 1.5rem;">
            <button type="submit" class="btn btn-primary" style="flex: 2;">Save Refinements</button>
            <a href="{{ route('partner.inventory.index') }}" class="btn btn-ghost" style="flex: 1;">Cancel</a>
        </div>
    </form>
    
    <div style="margin-top: 2rem; padding-top: 2rem; border-top: 1px solid var(--border); text-align: center;">
        <form action="{{ route('partner.inventory.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Archive this piece permanently?')">
            @csrf
            @method('DELETE')
            <button type="submit" style="background: none; border: none; color: #ef4444; font-weight: 700; cursor: pointer; font-size: 0.9rem;">
                Permanently Archive Piece
            </button>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const el = document.getElementById('media-gallery');
        if (el && Sortable) {
            Sortable.create(el, {
                animation: 150,
                onEnd: function() {
                    const order = Array.from(el.children).map(item => item.dataset.id);
                    updateVisualSequence({{ $product->id }}, order);
                }
            });
        }
    });

    function updateVisualSequence(productId, order) {
        fetch(`{{ url('partner/inventory') }}/${productId}/reorder-images`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ order: order })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                showToast('Narrative sequence updated.', 'success');
            }
        });
    }

    function deleteVisual(productId, imageId, btn) {
        if (!confirm('Remove this visual from the narrative?')) return;

        fetch(`{{ url('partner/inventory') }}/${productId}/images/${imageId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                btn.closest('.media-item').remove();
                showToast(data.message, 'success');
            }
        });
    }
</script>
@endsection
