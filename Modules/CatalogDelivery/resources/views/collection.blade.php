@section('title', 'The Collection | SmartShop')

<x-app-layout>

<section class="hero-luxury">
    <img src="https://picsum.photos/id/1027/2000/1200" class="hero-image-bg" alt="">
    <div class="hero-overlay">
        <span class="home-eyebrow">The LUWI Collection</span>
        <h1>The Curated<br>Collection.</h1>
        <p class="home-hero-sub">
            Every piece, every artisan. Browse the full marketplace catalog by category.
        </p>
        <div class="home-hero-actions">
            <a href="{{ route('shop') }}" class="btn btn-primary home-btn-solid">Filter Everything</a>
            <a href="#electronics" class="btn btn-ghost home-btn-outline">Start Browsing</a>
        </div>
    </div>
</section>

<section class="luxury-section home-section-spaced">
    <div class="collection-jump">
        @foreach($categories as $category)
            <a href="#{{ \Illuminate\Support\Str::slug($category->name) }}" class="btn btn-ghost collection-jump-link">{{ $category->name }}</a>
        @endforeach
    </div>
</section>

@foreach($categories as $category)
    <section id="{{ \Illuminate\Support\Str::slug($category->name) }}" class="luxury-section home-section-spaced">
        <div class="home-section-head">
            <span class="home-eyebrow-sm">{{ $category->products->count() }} pieces</span>
            <h2 class="home-section-title">{{ $category->name }}.</h2>
        </div>

        <div class="collection-grid">
            @foreach($category->products as $product)
                @include('catalogdelivery::components.product-card', ['product' => $product])
            @endforeach
        </div>
    </section>
@endforeach

</x-app-layout>
