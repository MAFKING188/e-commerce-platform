@section('title', 'SmartShop | Premium E-Commerce')

<x-app-layout>

<section class="hero-luxury">
    <!-- Using ID 1027 (Luxury Watch) for guaranteed Hero display -->
    <img src="https://picsum.photos/id/1027/2000/1200" class="hero-image-bg" alt="">
    <div class="hero-overlay">
        <span class="home-eyebrow">Collection / 26</span>
        <h1>Beyond <br>The Ordinary.</h1>
        <p class="home-hero-sub">
            A relentless pursuit of craftsmanship. Discover pieces that define a new era of personal style.
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
        @foreach($featuredProducts->take(4) as $product)
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

</x-app-layout>
