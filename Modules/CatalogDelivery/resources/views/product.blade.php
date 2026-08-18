@section('title', $product->name . ' | SmartShop')
@section('description', \Illuminate\Support\Str::limit($product->description, 160))
@section('og_image', $product->image_url)

<x-app-layout>

<div class="product-details">
    <!-- Left: Gallery -->
    <div class="gallery">
        <div class="main-image-container">
            <img id="mainImage" src="{{ $product->image_url }}" alt="{{ $product->name }}">
        </div>

        @if($product->images->count() > 1)
            <div class="thumbnails">
                @foreach($product->images as $image)
                    <div class="thumbnail {{ $loop->first ? 'active' : '' }}" onclick="updateMainImage(this, '{{ $image->resolved_url }}')">
                        <img src="{{ $image->resolved_url }}" alt="Thumbnail">
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Right: Info -->
    <div class="product-info">
        <span class="product-category">{{ $product->category->name ?? 'Collection' }}</span>
        <h1>{{ $product->name }}</h1>
        
        @if($product->stock > 0)
            <span class="product-stock-in">IN STOCK</span>
        @else
            <span class="product-stock-out">OUT OF STOCK</span>
        @endif

        <div class="price-tag">@money($product->price)</div>

        <div class="description-box">
            <h3 class="product-desc-title">Description</h3>
            <p>{{ $product->description }}</p>
        </div>

        @if($product->stock > 0)
            <div class="cart-form">
                <form action="{{ route('cart.add') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    
                    <div class="quantity-control">
                        <label class="product-qty-label">Quantity</label>
                        <input type="number" name="quantity" value="1" min="1" max="{{ $product->stock }}" class="quantity-input">
                    </div>

                    <button type="submit" class="btn btn-primary product-add-btn">
                        Add to Bag
                    </button>
                </form>
            </div>
        @else
            <div class="product-out-banner">
                This curated piece is currently out of the archive.
            </div>
        @endif
    </div>
</div>

{{-- PHASE 7: THE TESTIMONIAL (REVIEWS) --}}
<section class="product-reviews-section">
    <div class="product-section-head">
        <span class="cat-badge">Member Feedback</span>
        <h2 class="product-section-title">The Archive Reviews.</h2>
    </div>

    @auth
        <div class="review-form-panel">
            <h3>Share your perspective</h3>
            <form method="POST" action="{{ route('reviews.store') }}" class="review-form">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <label for="rating">Rating</label>
                <select name="rating" id="rating" required>
                    <option value="5">5 — Exceptional</option>
                    <option value="4">4 — Excellent</option>
                    <option value="3">3 — Good</option>
                    <option value="2">2 — Fair</option>
                    <option value="1">1 — Poor</option>
                </select>
                <label for="comment">Comment</label>
                <textarea name="comment" id="comment" rows="4" placeholder="What makes this piece special?"></textarea>
                <button type="submit" class="btn btn-primary">Submit Review</button>
            </form>
        </div>
    @else
        <p><a href="{{ route('login') }}">Sign in</a> to share your perspective.</p>
    @endauth

    @if($product->reviews->isEmpty())
        <div class="product-empty-reviews">
            <p class="product-empty-text">No testimonials recorded for this piece yet.</p>
        </div>
    @else
        <div class="product-reviews-grid">
            @foreach($product->reviews as $review)
                <div class="product-review-card">
                    <div class="product-stars">
                        @for($i = 0; $i < 5; $i++)
                            <svg width="16" height="16" fill="{{ $i < $review->rating ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.518 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        @endfor
                    </div>
                    <p class="product-review-text">"{{ $review->comment }}"</p>
                    <div class="product-reviewer">
                        <div class="product-avatar">
                            @if ($review->user->avatarUrl())
                                <img src="{{ $review->user->avatarUrl() }}" alt="{{ $review->user->name }}" class="product-avatar__img">
                            @else
                                {{ substr($review->user->name, 0, 1) }}
                            @endif
                        </div>
                        <span class="product-reviewer-name">{{ $review->user->name }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>

{{-- PHASE 8: THE SUGGESTION (RELATED ITEMS) --}}

@if($relatedProducts->isNotEmpty())
    <section class="product-related-section">
        <div class="product-related-head">
            <span class="cat-badge">Discovery</span>
            <h2 class="product-section-title">Complete The Look.</h2>
        </div>

        <div class="product-related-grid">
            @foreach($relatedProducts as $related)
                @include('catalogdelivery::components.product-card', ['product' => $related])
            @endforeach
        </div>
    </section>
@endif

<script>
    function updateMainImage(thumb, src) {
        document.getElementById('mainImage').src = src;
        document.querySelectorAll('.thumbnail').forEach(el => el.classList.remove('active'));
        thumb.classList.add('active');
    }
</script>

</x-app-layout>
