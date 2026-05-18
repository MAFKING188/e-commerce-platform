@extends('layouts.app')

@section('title', 'Saved Archive | LUWI Member')

@section('content')
<div style="margin-bottom: 5rem;">
    <span class="cat-badge">Member Curation</span>
    <h1 style="font-size: 3rem; font-weight: 800; color: var(--text-900);">Your Saved Pieces.</h1>
    <p style="color: var(--text-600); margin-top: 0.5rem;">A personal archive of your most coveted items.</p>
</div>

@if($wishlistItems->isEmpty())
    <div style="padding: 10rem 2rem; text-align: center; background: var(--surface-100); border-radius: 2.5rem; border: 1px dashed var(--border);">
        <div style="font-size: 3rem; margin-bottom: 2rem; opacity: 0.3;">🖤</div>
        <h2 style="font-weight: 800; color: var(--text-900); margin-bottom: 1rem;">Your archive is empty.</h2>
        <p style="color: var(--text-400); margin-bottom: 3rem; max-width: 400px; margin-left: auto; margin-right: auto;">Explore the collection and heart the pieces that speak to you. They will be preserved here for your next session.</p>
        <a href="{{ route('shop') }}" class="btn btn-primary" style="padding: 1.25rem 3rem;">Browse Archive</a>
    </div>
@else
    <div class="catalog-grid">
        @foreach($wishlistItems as $product)
            @include('components.product-card', ['product' => $product])
        @endforeach
    </div>
@endif

<script>
    function toggleWishlist(btn, productId) {
        // 💡 BACKEND CHALLENGE: Use Fetch API to call a route that saves this to the DB.
        btn.classList.toggle('active');
        console.log('Toggling wishlist for product:', productId);
    }
</script>
@endsection
