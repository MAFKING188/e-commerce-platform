@section('title', 'Collection Editor | LUWI Admin')

<x-app-layout>
<div style="max-width: 600px; margin: 0 auto;">
    <div style="text-align: center; margin-bottom: 4rem;">
        <span class="cat-badge">Taxonomy Editor</span>
        <h1 style="font-size: 3rem; font-weight: 800; color: var(--text-900);">{{ isset($category) ? 'Refine' : 'New' }} Collection.</h1>
    </div>

    <div style="background: var(--surface-100); padding: 3rem; border-radius: 2rem; border: 1px solid var(--border);">
        <form action="{{ isset($category) ? route('admin.categories.update', $category->id) : route('admin.categories.store') }}" method="POST">
            @csrf
            @if(isset($category)) @method('PUT') @endif

            <div class="form-group">
                <label class="form-label">Collection Name</label>
                <input type="text" name="name" class="form-input" value="{{ old('name', $category->name ?? '') }}" placeholder="e.g. Rare Textiles" required>
            </div>

            <div style="margin-top: 3rem; display: flex; gap: 1rem;">
                <button type="submit" class="btn btn-primary" style="flex: 2; padding: 1.25rem;">{{ isset($category) ? 'Update' : 'Initialize' }} Collection</button>
                <a href="{{ route('admin.categories.index') }}" class="btn btn-ghost" style="flex: 1;">Cancel</a>
            </div>
        </form>
    </div>
</div>
</x-app-layout>
