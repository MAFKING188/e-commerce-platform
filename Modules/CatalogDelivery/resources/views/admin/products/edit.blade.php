@section('title', 'Refine Product | LUWI Admin')

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

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
<x-app-layout>

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
                    <textarea name="description" class="auth-input no-resize" rows="6">{{ old('description', $product->description) }}</textarea>
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
                    <input type="file" name="images[]" class="auth-input editor-input-plain" multiple>
                </div>
            </div>

            <div class="edit-actions">
                <button type="submit" class="btn btn-primary editor-submit-sm">Update Product Details</button>
                <a href="{{ route('admin.products.index') }}" class="btn btn-ghost editor-cancel-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>

</x-app-layout>


