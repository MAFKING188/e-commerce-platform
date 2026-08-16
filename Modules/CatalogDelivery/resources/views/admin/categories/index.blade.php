@section('title', 'Category Archive | LUWI Admin')

<x-app-layout>
@include('partials.admin-nav')

<div class="admin-header">
    <div>
        <span class="cat-badge">Taxonomy</span>
        <h1>Collections.</h1>
    </div>
    <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">Create New Collection</a>
</div>

<div class="category-grid">
    @foreach($categories as $category)
        <div class="category-card">
            <div>
                <h3 class="category-name">{{ $category->name }}</h3>
                <span class="category-count">{{ $category->products()->count() }} items mapped</span>
            </div>
            <div class="category-actions">
                <a href="{{ route('admin.categories.edit', $category->id) }}" class="category-edit">Edit</a>
                <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('Deleting a collection may orphan products. Proceed?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="category-delete">Delete</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
</x-app-layout>
