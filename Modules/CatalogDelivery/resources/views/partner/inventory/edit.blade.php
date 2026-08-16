@section('title', 'Edit Product | Partner Dashboard')

<!-- Include SortableJS for drag and drop reordering -->
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
<x-app-layout>
@include('partials.partner-nav')

<div class="inventory-form-head">
    <span class="cat-badge">Inventory Refinement</span>
    <h1 class="inventory-title">Refine Piece.</h1>
</div>

<div class="form-container">
    <form action="{{ route('partner.inventory.update', $product->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')
        
        <div class="form-group">
            <label for="name">Product Name</label>
            <input type="text" name="name" id="name" class="form-control" value="{{ $product->name }}" required>
        </div>

        <div class="inventory-form-grid">
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

        <div class="inventory-form-actions">
            <button type="submit" class="btn btn-primary inventory-form-submit">Save Refinements</button>
            <a href="{{ route('partner.inventory.index') }}" class="btn btn-ghost inventory-form-cancel">Cancel</a>
        </div>
    </form>
    
    <div class="inventory-danger-zone">
        <form action="{{ route('partner.inventory.destroy', $product->id) }}" method="POST" onsubmit="return confirm('Archive this piece permanently?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="inventory-archive-btn">
                Permanently Archive Piece
            </button>
        </form>
    </div>
</div>
</x-app-layout>


