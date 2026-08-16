@section('title', 'Your Archive | SmartShop')

@section('styles')
<style>
    .archive-header {
        margin-bottom: 5rem;
        text-align: center;
    }
    .archive-header h1 {
        font-size: 4rem;
        font-weight: 800;
        letter-spacing: -0.05em;
        color: var(--text-900);
        margin-bottom: 1rem;
    }
    .archive-header p {
        color: var(--text-600);
        font-size: 1.1rem;
        max-width: 600px;
        margin: 0 auto;
    }
    .empty-state {
        text-align: center;
        padding: 8rem 2rem;
        background: var(--surface-100);
        border: 1px solid var(--border);
        border-radius: 3rem;
        box-shadow: var(--shadow-sm);
    }
    .empty-state h2 {
        font-size: 2rem;
        font-weight: 800;
        color: var(--text-900);
        margin-bottom: 1rem;
    }
</style>
@endsection

<x-app-layout>
<div class="archive-header">
    <span class="cat-badge" style="margin-bottom: 1rem; display: inline-block;">Personal Curation</span>
    <h1>Your Archive.</h1>
    <p>A refined collection of exceptional pieces curated for your personal aesthetic.</p>
</div>

@if($items->isEmpty())
    <div class="empty-state">
        <div style="font-size: 4rem; margin-bottom: 2rem;">✨</div>
        <h2>Your archive is currently empty.</h2>
        <p style="color: var(--text-600); margin-bottom: 3rem; max-width: 400px; margin-left: auto; margin-right: auto;">
            Explore our collection and save the pieces that speak to your sense of timeless design.
        </p>
        <a href="{{ route('shop') }}" class="btn btn-primary btn-lg" style="padding: 1rem 2.5rem;">Explore Collection</a>
    </div>
@else
    <div class="catalog-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 3rem;">
        @foreach($items as $item)
            @include('components.product-card', ['product' => $item->product])
        @endforeach
    </div>
@endif
</x-app-layout>
