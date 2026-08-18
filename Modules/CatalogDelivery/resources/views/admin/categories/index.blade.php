@section('title', 'Category Archive | LUWI Admin')

<x-app-layout>
@include('partials.admin-nav')

<div class="pc-header">
    <div>
        <span class="pc-eyebrow">Taxonomy</span>
        <h1 class="pc-title">Collections</h1>
    </div>
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary pc-btn-sm">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 5v14M5 12h14"/></svg>
        Create New Collection
    </a>
</div>

<div class="category-grid">
    @foreach($categories as $category)
        <div class="category-card">
            <div>
                <h3 class="category-name">{{ $category->name }}</h3>
                <span class="category-count">{{ $category->products_count }} items mapped</span>
            </div>
            <div class="category-actions">
                <a href="{{ route('admin.categories.edit', $category->id) }}" class="category-edit">Edit</a>
                <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST" data-confirm="Deleting a collection may orphan products. Proceed?">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="category-delete">Delete</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
</x-app-layout>