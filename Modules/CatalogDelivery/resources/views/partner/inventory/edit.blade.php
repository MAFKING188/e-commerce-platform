@section('title', 'Edit Product | Partner Dashboard')

<!-- Include SortableJS for drag and drop reordering -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

@section('scripts')
@vite('resources/js/partner.js')
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
        pcConfirm('Remove this visual?', 'This will remove the image from the product narrative. This cannot be undone.', function() {
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
        });
    }
</script>
@endsection

<x-app-layout>
@include('partials.partner-nav')

<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Inventory Refinement</span>
        <h1 class="pc-title">Refine Piece.</h1>
    </div>
</div>

<div class="form-container">
    <form action="{{ route('partner.inventory.update', $product->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="pc-field">
            <label for="name" class="pc-field__label">Product Name</label>
            <input type="text" name="name" id="name" class="pc-field__input {{ $errors->has('name') ? 'pc-field__input--invalid' : '' }}" value="{{ $product->name }}" required>
            @error('name')<p class="pc-field__error">{{ $message }}</p>@enderror
        </div>

        <div class="pc-form-grid">
            <div class="pc-field">
                <label for="price" class="pc-field__label">Price (USD)</label>
                <input type="number" name="price" id="price" class="pc-field__input {{ $errors->has('price') ? 'pc-field__input--invalid' : '' }}" step="0.01" value="{{ $product->price }}" required>
                @error('price')<p class="pc-field__error">{{ $message }}</p>@enderror
            </div>
            <div class="pc-field">
                <label for="stock" class="pc-field__label">Current Stock</label>
                <input type="number" name="stock" id="stock" class="pc-field__input {{ $errors->has('stock') ? 'pc-field__input--invalid' : '' }}" value="{{ $product->stock }}" required>
                @error('stock')<p class="pc-field__error">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="pc-field">
            <label for="category_id" class="pc-field__label">Category</label>
            <select name="category_id" id="category_id" class="pc-field__input {{ $errors->has('category_id') ? 'pc-field__input--invalid' : '' }}" required>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" {{ $product->category_id == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            @error('category_id')<p class="pc-field__error">{{ $message }}</p>@enderror
        </div>

        <div class="pc-field">
            <label for="description" class="pc-field__label">Detailed Narrative</label>
            <textarea name="description" id="description" class="pc-field__input {{ $errors->has('description') ? 'pc-field__input--invalid' : '' }}" required>{{ $product->description }}</textarea>
            @error('description')<p class="pc-field__error">{{ $message }}</p>@enderror
        </div>

        <div class="pc-field">
            <label class="pc-field__label">Product Narrative Gallery (Drag to Reorder)</label>
            <div class="pc-media-grid" id="media-gallery">
                @foreach ($product->images->sortBy('position') as $image)
                    <div class="pc-media-item" data-id="{{ $image->id }}">
                        <img src="{{ asset($image->url) }}" alt="Product Image">
                        <button type="button" class="pc-media-item__remove" onclick="deleteVisual({{ $product->id }}, {{ $image->id }}, this)" title="Remove Visual" aria-label="Remove visual"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="pc-field">
            <label for="images" class="pc-field__label">Add More Narrative Visuals</label>
            <input type="file" name="images[]" id="images" class="pc-field__input" multiple>
        </div>

        <div class="inventory-form-actions">
            <button type="submit" class="btn btn-primary inventory-form-submit">Save Refinements</button>
            <a href="{{ route('partner.inventory.index') }}" class="btn btn-ghost inventory-form-cancel">Cancel</a>
        </div>
    </form>

    <div class="pc-danger-zone">
        <form action="{{ route('partner.inventory.destroy', $product->id) }}" method="POST">
            @csrf
            @method('DELETE')
            <button type="submit" class="pc-danger-btn"
                data-confirm
                data-confirm-title="Archive this piece?"
                data-confirm-message="This will permanently remove the piece from your inventory. This cannot be undone."
                data-confirm-label="Archive">
                Permanently Archive Piece
            </button>
        </form>
    </div>
</div>
</x-app-layout>