@extends('layouts.app')

@section('title', 'Category Archive | LUWI Admin')

@section('styles')
<style>
    .admin-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 4rem; }
    .admin-header h1 { font-size: 3rem; font-weight: 800; color: var(--text-900); }

    .category-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 2rem;
    }

    .category-card {
        background: var(--surface-100);
        padding: 2.5rem;
        border-radius: 2rem;
        border: 1px solid var(--border);
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: all 0.3s ease;
    }

    .category-card:hover { border-color: var(--brand-accent); transform: translateY(-5px); }
</style>
@endsection

@section('content')
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
                <h3 style="font-size: 1.5rem; font-weight: 800; color: var(--text-900);">{{ $category->name }}</h3>
                <span style="font-size: 0.75rem; font-weight: 700; color: var(--text-400);">{{ $category->products()->count() }} items mapped</span>
            </div>
            <div style="display: flex; gap: 1rem;">
                <a href="{{ route('admin.categories.edit', $category->id) }}" style="color: var(--brand-accent); font-weight: 800; font-size: 0.75rem; text-transform: uppercase; text-decoration: none;">Edit</a>
                <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('Deleting a collection may orphan products. Proceed?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" style="color: var(--error); background: none; border: none; font-weight: 800; font-size: 0.75rem; text-transform: uppercase; cursor: pointer;">Delete</button>
                </form>
            </div>
        </div>
    @endforeach
</div>
@endsection
