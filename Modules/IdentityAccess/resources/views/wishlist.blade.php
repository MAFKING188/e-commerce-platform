@section('title', 'Your Archive | SmartShop')

<x-app-layout>
<div class="archive-header">
    <span class="cat-badge archive-badge">Personal Curation</span>
    <h1>Your Archive.</h1>
    <p>A refined collection of exceptional pieces curated for your personal aesthetic.</p>
</div>

@if($items->isEmpty())
    <div class="empty-state">
        <svg class="empty-state__icon" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
        <h2>Your archive is currently empty.</h2>
        <p class="empty-state__text">
            Explore our collection and save the pieces that speak to your sense of timeless design.
        </p>
        <a href="{{ route('shop') }}" class="btn btn-primary btn-lg">Explore Collection</a>
    </div>
@else
    <div class="catalog-grid">
        @foreach($items as $item)
            @include('catalogdelivery::components.product-card', ['product' => $item->product])
        @endforeach
    </div>
@endif
</x-app-layout>
