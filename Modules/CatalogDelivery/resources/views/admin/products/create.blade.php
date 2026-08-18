@section('title', 'Curate New Piece | LUWI')

<x-app-layout>
@include('partials.admin-nav')

<div class="editor-stage">
    <div class="editor-header">
        <span class="cat-badge">Inventory Management</span>
        <h1>Curate New Piece</h1>
        <p class="editor-subtitle">Expand the archive with a new item of exceptional design.</p>
    </div>

    <div class="editor-card">
        <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="field-grid">
                <div class="form-group field-full">
                    <label class="form-label">Piece Designation</label>
                    <input type="text" name="name" class="auth-input" placeholder="e.g. Minimalist Oak Table" value="{{ old('name') }}" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Collection / Category</label>
                    <select name="category_id" class="auth-input" required>
                        <option value="">Select Collection</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Value ($)</label>
                    <input type="number" step="0.01" name="price" class="auth-input" placeholder="0.00" value="{{ old('price') }}" required>
                </div>

                <div class="form-group field-full">
                    <label class="form-label">Initial Availability (Stock)</label>
                    <input type="number" name="stock" class="auth-input" placeholder="0" value="{{ old('stock', 0) }}" required>
                </div>

                <div class="form-group field-full">
                    <label class="form-label">The Story (Description)</label>
                    <textarea name="description" class="auth-input no-resize" rows="6" placeholder="Describe the craftsmanship and vision behind this piece...">{{ old('description') }}</textarea>
                </div>

                <div class="form-group field-full">
                    <label class="form-label">Visual Narrative (Narrative Images)</label>
                    <div class="image-upload-zone">
                        <svg class="upload-icon" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>
                        <div class="file-input-wrapper">
                            <span class="btn btn-ghost upload-btn">Select Assets</span>
                            <input type="file" name="images[]" multiple required>
                        </div>
                        <p class="upload-hint">High-resolution JPG or PNG recommended. Select one or more.</p>
                    </div>
                </div>
            </div>

            <div class="editor-actions">
                <button type="submit" class="btn btn-primary editor-submit">Add Piece to Archive</button>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost editor-cancel">Discard</a>
            </div>
        </form>
    </div>
</div>

</x-app-layout>
