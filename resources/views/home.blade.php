@extends('layouts.app')

@section('title', 'SmartShop | Exceptional Design')

@section('styles')
<style>
    /* HERO: The Vault Lock */
    .hero-luxury {
        height: 80vh;
        min-height: 650px;
        display: flex;
        align-items: center;
        position: relative;
        border-radius: 2rem;
        overflow: hidden;
        margin-bottom: 12rem; /* Extreme air gap */
        background: #0f172a;
        z-index: 100; /* Ensure hero stays above and separate */
    }

    .hero-image-bg {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 1;
        filter: brightness(0.65);
    }

    .hero-luxury::after {
        content: '';
        position: absolute;
        inset: 0;
        background: linear-gradient(90deg, rgba(15,23,42,0.9) 0%, transparent 70%);
        z-index: 5;
    }

    .hero-overlay {
        position: relative;
        z-index: 20;
        padding: 0 6rem;
        max-width: 900px;
        color: white;
    }

    .hero-overlay h1 {
        font-size: clamp(3.5rem, 10vw, 6.5rem);
        font-weight: 800;
        line-height: 0.9;
        letter-spacing: -0.05em;
        margin-bottom: 2rem;
    }

    /* GRID RECOVERY */
    .collection-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 3rem;
    }

    @media (max-width: 768px) {
        .hero-overlay { padding: 0 2rem; text-align: center; }
        .hero-luxury { height: 75vh; margin-bottom: 6rem; }
    }
</style>
@endsection

@section('content')

<section class="hero-luxury">
    <!-- Using ID 1027 (Luxury Watch) for guaranteed Hero display -->
    <img src="https://picsum.photos/id/1027/2000/1200" class="hero-image-bg" alt="">
    <div class="hero-overlay">
        <span style="font-weight: 800; letter-spacing: 0.3em; text-transform: uppercase; color: var(--brand-accent); display: block; margin-bottom: 1.5rem;">Collection / 26</span>
        <h1>Beyond <br>The Ordinary.</h1>
        <p style="font-size: 1.2rem; margin-bottom: 3.5rem; opacity: 0.9; max-width: 480px; line-height: 1.6;">
            A relentless pursuit of craftsmanship. Discover pieces that define a new era of personal style.
        </p>
        <div style="display: flex; gap: 1.5rem;">
            <a href="{{ route('shop') }}" class="btn btn-primary" style="background: white; color: black; padding: 1.5rem 3rem;">Enter Shop</a>
            <a href="{{ route('signup') }}" class="btn btn-ghost" style="color: white; border-color: rgba(255,255,255,0.3);">Become Member</a>
        </div>
    </div>
</section>

<section class="luxury-section" style="margin-bottom: 12rem;">
    <div style="margin-bottom: 5rem; border-left: 5px solid var(--brand-accent); padding-left: 2.5rem;">
        <span style="font-size: 0.75rem; font-weight: 800; color: var(--brand-accent); letter-spacing: 0.3em; text-transform: uppercase;">Curated Selection</span>
        <h2 style="font-size: 3.5rem; font-weight: 800; color: var(--text-900);">Editor's Choice.</h2>
    </div>

    <div class="collection-grid">
        @foreach($featuredProducts->take(4) as $product)
            @include('components.product-card', ['product' => $product])
        @endforeach
    </div>
</section>

<section class="luxury-section">
    <div style="text-align: center; margin-bottom: 6rem;">
        <span style="font-size: 0.75rem; font-weight: 800; color: var(--brand-accent); letter-spacing: 0.3em; text-transform: uppercase;">New Arrivals</span>
        <h2 style="font-size: 3.5rem; font-weight: 800; color: var(--text-900);">The Latest Drop.</h2>
    </div>

    <div class="collection-grid">
        @foreach($latestProducts as $product)
            @include('components.product-card', ['product' => $product])
        @endforeach
    </div>
</section>

@endsection
