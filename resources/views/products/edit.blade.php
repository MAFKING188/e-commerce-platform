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

    .media-gallery { display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 1.5rem; margin-top: 1rem; }
    .media-item { position: relative; border-radius: 1rem; overflow: hidden; border: 1px solid var(--border); background: var(--surface-200); aspect-ratio: 1; cursor: move; }
    .media-item img { width: 100%; height: 100%; object-fit: cover; }
    .media-actions { position: absolute; top: 0.5rem; right: 0.5rem; display: flex; gap: 0.5rem; }
    .media-btn { background: rgba(255, 255, 255, 0.9); border: none; width: 28px; height: 28px; border-radius: 50%; display: flex; align-items: center; justify-content: center; cursor: pointer; color: #ef4444; font-size: 0.8rem; box-shadow: var(--shadow-sm); }
</style>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endsection

@section('content')

<div class="editor-stage">
    <div class="editor-header">
        <span class="stat-label">Inventory Management</span>
        <h1>Edit Product.</h1>
    </div>

    <div class="editor-card">
        <form action="{{ route('admin.products.update', $product->id) }}" method="POST" enctype="multipart/form-data">
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
                    <label class="form-label">Product Narrative Gallery (Drag to Reorder)</label>
                    <div class="media-gallery" id="media-gallery">
                        @foreach($product->images->sortBy('position') as $image)
                            <div class="media-item" data-id="{{ $image->id }}">
                                <img src="{{ asset($image->url) }}" alt="">
                                <div class="media-actions">
                                    <button type="button" class="media-btn" onclick="deleteVisual({{ $product->id }}, {{ $image->id }}, this)" title="Remove Visual">✕</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="form-group field-full">
                    <label class="form-label">Add Narrative Visuals</label>
                    <input type="file" name="images[]" class="auth-input" style="background: white;" multiple>
                </div>
            </div>

            <div style="margin-top: 3rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" style="flex: 2; padding: 1.25rem;">Update Product Details</button>
                <a href="{{ route('admin.products.index') }}" class="btn btn-ghost" style="flex: 1;">Cancel</a>
            </div>
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
        fetch(`{{ url('admin/products') }}/${productId}/reorder-images`, {
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
                showToast(data.message, 'success');
            }
        });
    }

    function deleteVisual(productId, imageId, btn) {
        if (!confirm('Remove this visual?')) return;

        fetch(`{{ url('admin/products') }}/${productId}/images/${imageId}`, {
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
