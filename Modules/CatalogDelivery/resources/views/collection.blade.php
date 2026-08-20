@section('title', 'The Collection | SmartShop')

<x-app-layout>

<section class="hero-luxury">
    <img src="https://picsum.photos/id/1027/2000/1200" class="hero-image-bg" alt="">
    <div class="hero-overlay">
        <span class="home-eyebrow">The LUWI Collection</span>
        <h1>The Curated<br>Collection.</h1>
        <p class="home-hero-sub">
            One marketplace, every craft. Explore what our artisans and curators are shipping right now.
        </p>
        <div class="home-hero-actions">
            <a href="{{ route('shop') }}" class="btn btn-primary home-btn-solid">Browse Everything</a>
            <a href="#new-arrivals" class="btn btn-ghost home-btn-outline">See What's New</a>
        </div>
    </div>
</section>

<section id="new-arrivals" class="luxury-section home-section-spaced">
    <div class="home-section-head">
        <span class="home-eyebrow-sm">Fresh In</span>
        <h2 class="home-section-title">New Arrivals.</h2>
    </div>

    <div class="collection-grid">
        @foreach($latestProducts as $product)
            @include('catalogdelivery::components.product-card', ['product' => $product])
        @endforeach
    </div>
</section>

<section id="featured" class="luxury-section">
    <div class="home-section-head-center">
        <span class="home-eyebrow-sm">Editor's Selection</span>
        <h2 class="home-section-title">Featured Pieces.</h2>
    </div>

    <div class="collection-grid">
        @foreach($featuredProducts as $product)
            @include('catalogdelivery::components.product-card', ['product' => $product])
        @endforeach
    </div>
</section>

</x-app-layout>