@section('title', 'Curate New Piece | LUWI')

@section('styles')
<style>
    .editor-stage {
        max-width: 900px;
        margin: 0 auto;
        padding-top: 2rem;
    }

    .editor-header {
        margin-bottom: 5rem;
        text-align: left;
        border-left: 5px solid var(--brand-accent);
        padding-left: 2.5rem;
    }

    .editor-header h1 {
        font-size: 3.5rem;
        font-weight: 800;
        letter-spacing: -0.05em;
        color: var(--text-900);
    }

    .editor-card {
        background: var(--surface-100);
        padding: 4rem;
        border-radius: 2.5rem;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-lg);
    }

    /* PREMIUM INPUTS */
    .form-group { margin-bottom: 2.5rem; }
    
    .form-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--text-400);
        margin-bottom: 1rem;
        letter-spacing: 0.1em;
    }

    .auth-input {
        width: 100%;
        padding: 1.25rem 1.5rem;
        border-radius: 16px;
        border: 1px solid var(--border);
        background: var(--surface-200);
        color: var(--text-900);
        font-family: inherit;
        font-size: 1rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .auth-input:focus {
        border-color: var(--brand-accent);
        background: var(--surface-100);
        outline: none;
        box-shadow: 0 0 0 4px var(--brand-accent-soft);
    }

    .field-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }

    @media (max-width: 768px) {
        .field-grid { grid-template-columns: 1fr; }
        .editor-card { padding: 2rem; }
        .editor-header h1 { font-size: 2.5rem; }
    }

    .field-full {
        grid-column: span 2;
    }

    .image-upload-zone {
        margin-top: 1rem;
        padding: 5rem 2rem;
        border: 2px dashed var(--border);
        border-radius: 20px;
        text-align: center;
        background: var(--surface-200);
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }

    .image-upload-zone:hover {
        border-color: var(--brand-accent);
        background: var(--brand-accent-soft);
    }

    .file-input-wrapper {
        position: relative;
        overflow: hidden;
        display: inline-block;
        cursor: pointer;
    }

    .file-input-wrapper input[type=file] {
        position: absolute;
        left: 0;
        top: 0;
        opacity: 0;
        cursor: pointer;
        width: 100%;
        height: 100%;
    }

    .upload-icon {
        font-size: 2.5rem;
        margin-bottom: 1rem;
        display: block;
        color: var(--brand-accent);
    }

    .cat-badge {
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--brand-accent);
        letter-spacing: 0.1em;
        display: block;
        margin-bottom: 1rem;
    }
</style>
@endsection

<x-app-layout>

<div class="editor-stage">
    <div class="editor-header">
        <span class="cat-badge">Inventory Management</span>
        <h1>Curate New Piece.</h1>
        <p style="color: var(--text-400); font-size: 1.1rem; margin-top: 1rem;">Expand the archive with a new item of exceptional design.</p>
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
                        @foreach(\App\Models\Category::all() as $category)
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
                    <textarea name="description" class="auth-input" rows="6" placeholder="Describe the craftsmanship and vision behind this piece..." style="resize: none;">{{ old('description') }}</textarea>
                </div>

                <div class="form-group field-full">
                    <label class="form-label">Visual Narrative (Narrative Images)</label>
                    <div class="image-upload-zone">
                        <span class="upload-icon">✦</span>
                        <div class="file-input-wrapper">
                            <span class="btn btn-ghost" style="border-radius: 99px; padding: 0.75rem 2rem;">Select Assets</span>
                            <input type="file" name="images[]" multiple required>
                        </div>
                        <p style="font-size: 0.8rem; color: var(--text-400); margin-top: 1.5rem; font-weight: 600;">High-resolution JPG or PNG recommended. Select one or more.</p>
                    </div>
                </div>
            </div>

            <div style="margin-top: 4rem; display: flex; gap: 1.5rem;">
                <button type="submit" class="btn btn-primary" style="flex: 2; padding: 1.5rem; border-radius: 16px; font-size: 1rem;">Add Piece to Archive</button>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost" style="flex: 1; border-radius: 16px;">Discard</a>
            </div>
        </form>
    </div>
</div>

</x-app-layout>
