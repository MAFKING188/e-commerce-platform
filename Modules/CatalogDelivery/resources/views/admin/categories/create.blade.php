@section('title', 'Collection Editor | LUWI Admin')

<x-app-layout>
@include('partials.admin-nav')
<div class="pc-wrap-narrow">
    <a href="{{ route('admin.categories.index') }}" class="pc-back-link">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
        Back to Collections
    </a>

    <div class="pc-header">
        <div>
            <span class="pc-eyebrow">Taxonomy Editor</span>
            <h1 class="pc-title">{{ isset($category) ? 'Refine' : 'New' }} Collection</h1>
        </div>
    </div>

    <div class="pc-card">
        <form action="{{ isset($category) ? route('admin.categories.update', $category->id) : route('admin.categories.store') }}" method="POST">
            @csrf
            @if(isset($category)) @method('PUT') @endif

            <div class="pc-form-grid">
                <div class="pc-field">
                    <label class="pc-field__label" for="name">Collection Name</label>
                    <input id="name" type="text" name="name" class="pc-field__input" value="{{ old('name', $category->name ?? '') }}" placeholder="e.g. Rare Textiles" required>
                </div>
            </div>

            <div class="pc-form-actions">
                <button type="submit" class="btn btn-primary pc-btn-sm">{{ isset($category) ? 'Update' : 'Initialize' }} Collection</button>
                <a href="{{ route('admin.categories.index') }}" class="pc-btn-sm">Cancel</a>
            </div>
        </form>
    </div>
</div>
</x-app-layout>