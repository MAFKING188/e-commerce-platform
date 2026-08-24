<x-app-layout>

@section('title', 'SmartShop | Premium E-Commerce')

@section('styles')
<style>
    .hero-luxury {
        position: relative;
        height: 90vh;
        min-height: 600px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        overflow: hidden;
        margin: -2rem -2rem 4rem;
        border-radius: 0 0 var(--radius-lg) var(--radius-lg);
    }

    .hero-image-bg {
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: -1;
        filter: brightness(0.4);
    }

    .hero-overlay {
        position: relative;
        z-index: 1;
        color: white;
        max-width: 900px;
        padding: 0 2rem;
    }

    .home-eyebrow {
        display: inline-block;
        font-size: 0.75rem;
        font-weight: 800;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        padding: 0.5rem 1.25rem;
        border-radius: 99px;
        margin-bottom: 1.5rem;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .hero-luxury h1 {
        font-size: clamp(3rem, 8vw, 5.5rem);
        font-weight: 800;
        line-height: 1.1;
        letter-spacing: -0.03em;
        margin-bottom: 1.5rem;
        text-shadow: 0 4px 24px rgba(0, 0, 0, 0.3);
    }

    .home-hero-sub {
        font-size: 1.25rem;
        font-weight: 400;
        line-height: 1.6;
        opacity: 0.9;
        max-width: 600px;
        margin: 0 auto 2.5rem;
    }

    .home-hero-actions {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    .home-btn-solid {
        padding: 1rem 2.5rem;
        font-size: 1rem;
        font-weight: 700;
    }

    .home-btn-outline {
        padding: 1rem 2.5rem;
        font-size: 1rem;
        font-weight: 700;
        border-color: rgba(255, 255, 255, 0.5);
        color: white;
    }

    .home-btn-outline:hover {
        background: white;
        color: var(--brand-primary);
    }

    .luxury-section {
        padding: 4rem 0;
    }

    .home-section-spaced {
        padding-top: 6rem;
    }

    .home-section-head {
        text-align: center;
        margin-bottom: 3rem;
    }

    .home-section-head-center {
        text-align: center;
        margin-bottom: 3rem;
    }

    .home-eyebrow-sm {
        display: inline-block;
        font-size: 0.7rem;
        font-weight: 800;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        color: var(--brand-accent);
        margin-bottom: 0.75rem;
    }

    .home-section-title {
        font-size: clamp(2rem, 4vw, 2.75rem);
        font-weight: 800;
        letter-spacing: -0.02em;
        color: var(--text-900);
    }

    .collection-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 2rem;
    }

    @media (max-width: 768px) {
        .hero-luxury {
            height: 70vh;
            min-height: 500px;
            margin: -1.25rem -1.25rem 3rem;
        }
        .luxury-section {
            padding: 3rem 0;
        }
    }
</style>
@endsection

@section('content')
<section class="hero-luxury">
    <!-- Using ID 1027 (Luxury Watch) for guaranteed Hero display -->
    <img src="https://picsum.photos/id/1027/2000/1200" class="hero-image-bg" alt="">
    <div class="hero-overlay">
        <span class="home-eyebrow">{{ \Modules\CatalogDelivery\Models\Product::where('stock', '>', 0)->count() }} pieces in stock, ready to ship</span>
        <h1>Beyond <br>The Ordinary.</h1>
        <p class="home-hero-sub">
            Independent artisans. Verified one-of-a-kind pieces. Free returns within 30 days — no questions asked.
        </p>
        <div class="home-hero-actions">
            <a href="{{ route('shop') }}" class="btn btn-primary home-btn-solid">Enter Shop</a>
            <a href="{{ route('signup') }}" class="btn btn-ghost home-btn-outline">Become Member</a>
        </div>
    </div>
</section>

<section id="editor-choice" class="luxury-section home-section-spaced">
    <div class="home-section-head">
        <span class="home-eyebrow-sm">Curated Selection</span>
        <h2 class="home-section-title">Editor's Choice.</h2>
    </div>

    <div class="collection-grid">
        @foreach($editorChoiceProducts->take(4) as $product)
            @include('catalogdelivery::components.product-card', ['product' => $product])
        @endforeach
    </div>
</section>

<section id="new-arrivals" class="luxury-section">
    <div class="home-section-head-center">
        <span class="home-eyebrow-sm">New Arrivals</span>
        <h2 class="home-section-title">The Latest Drop.</h2>
    </div>

    <div class="collection-grid">
        @foreach($latestProducts as $product)
            @include('catalogdelivery::components.product-card', ['product' => $product])
        @endforeach
    </div>
</section>
@endsection
</x-app-layout>
