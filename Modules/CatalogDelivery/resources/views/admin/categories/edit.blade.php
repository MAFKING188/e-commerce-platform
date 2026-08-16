@section('title', 'Collection Editor | LUWI Admin')

<x-app-layout>
<div class="category-form-stage">
    <div class="category-form-head">
        <span class="cat-badge">Taxonomy Editor</span>
        <h1 class="category-form-title">{{ isset($category) ? 'Refine' : 'New' }} Collection.</h1>
    </div>

    <div class="category-form-card">
        <form action="{{ isset($category) ? route('admin.categories.update', $category->id) : route('admin.categories.store') }}" method="POST">
            @csrf
            @if(isset($category)) @method('PUT') @endif

            <div class="form-group">
                <label class="form-label">Collection Name</label>
                <input type="text" name="name" class="form-input" value="{{ old('name', $category->name ?? '') }}" placeholder="e.g. Rare Textiles" required>
            </div>

            <div class="category-form-actions">
                <button type="submit" class="btn btn-primary category-form-submit">{{ isset($category) ? 'Update' : 'Initialize' }} Collection</button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-ghost category-form-cancel">Cancel</a>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
