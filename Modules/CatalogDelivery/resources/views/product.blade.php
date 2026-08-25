@section('title', $product->name . ' | SmartShop')
@section('description', \Illuminate\Support\Str::limit($product->description, 160))
@section('og_image', $product->image_url)

<x-app-layout>

<div class="product-details">
    <!-- Left: Gallery -->
    <div class="gallery">
        <div class="main-image-container">
            <img id="mainImage" src="{{ $product->image_url }}" alt="{{ $product->name }}" decoding="async">
        </div>

        @if($product->images->count() > 1)
            <div class="thumbnails">
                @foreach($product->images as $image)
                    <div class="thumbnail {{ $loop->first ? 'active' : '' }}" onclick="updateMainImage(this, '{{ $image->resolved_url }}')">
                        <img src="{{ $image->resolved_url }}" alt="Thumbnail" loading="lazy" decoding="async">
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Right: Info -->
    <div class="product-info">
        <span class="product-category">{{ $product->category->name ?? 'Collection' }}</span>
        @if($product->partners->isNotEmpty())
            <span class="product-partner">By {{ $product->partners->first()->name }}</span>
        @endif
        @if($product->color)
            <span class="product-color">Color: {{ $product->color }}</span>
        @endif
        @if($product->size)
            <span class="product-size">Size: {{ $product->size }}</span>
        @endif
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
                <form id="addToCartForm" data-product-id="{{ $product->id }}">
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
            <form method="POST" action="{{ route('reviews.store') }}" class="review-form" id="reviewForm">
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
                @php $isAuthor = auth()->check() && auth()->id() === $review->user_id; @endphp
                @if($review->status === 'approved' || $isAuthor)
                    <div class="product-review-card {{ $review->status === 'pending' ? 'pending' : '' }}">
                        <div class="product-stars">
                            @for($i = 0; $i < 5; $i++)
                                <svg width="16" height="16" fill="{{ $i < $review->rating ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.518 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-.363 1.118l1.518-4.674c-.783.57-1.838-.197-1.538-1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976-2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                            @endfor
                        </div>
                        <p class="product-review-text">"{{ $review->comment }}"</p>
                        <div class="product-reviewer">
                            <div class="product-avatar">
                                @if ($review->user->avatarUrl())
                                    <img src="{{ $review->user->avatarUrl() }}" alt="{{ $review->user->name }}" class="product-avatar__img" loading="lazy" decoding="async">
                                @else
                                    {{ substr($review->user->name, 0, 1) }}
                                @endif
                            </div>
                            <span class="product-reviewer-name">{{ $review->user->name }}</span>
                        </div>
                        @if($review->status === 'pending')
                            <span class="review-pending-badge">Awaiting moderation</span>
                        @endif
                    </div>
                @endif
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

    // AJAX Add to Cart
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('addToCartForm');
        if (!form) return;

        form.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const btn = form.querySelector('button[type="submit"]');
            const originalText = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Adding...';

            const formData = new FormData(form);
            formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

            try {
                const response = await fetch('{{ route('cart.add') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                });

                const data = await response.json();

                if (data.status === 'success') {
                    showToast(data.message || 'Product added to bag', 'success');
                    
                    // Update cart count badge
                    const cartCountEl = document.querySelector('.cart-count');
                    if (cartCountEl && data.cart_count !== undefined) {
                        cartCountEl.textContent = data.cart_count;
                        cartCountEl.style.display = 'flex';
                    }
                } else {
                    showToast(data.message || 'Failed to add to bag', 'error');
                }
            } catch (error) {
                console.error('Add to cart error:', error);
                showToast('Network error. Please try again.', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = originalText;
            }
        });
    });
</script>

    <script>
        // AJAX Review Submission
        document.addEventListener('DOMContentLoaded', function() {
            const reviewForm = document.getElementById('reviewForm');
            if (!reviewForm) return;

            reviewForm.addEventListener('submit', async function(e) {
                e.preventDefault();
                
                const btn = reviewForm.querySelector('button[type="submit"]');
                const originalText = btn.textContent;
                btn.disabled = true;
                btn.textContent = 'Submitting...';

                const formData = new FormData(reviewForm);

                try {
                    const response = await fetch('{{ route('reviews.store') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: formData
                    });

                    const data = await response.json();

                    if (data.status === 'success') {
                        showToast(data.message || 'Review submitted for moderation', 'success');
                        // Optionally reload to show the new review
                        setTimeout(() => window.location.reload(), 1500);
                    } else {
                        showToast(data.message || 'Failed to submit review', 'error');
                    }
                } catch (error) {
                    console.error('Review submission error:', error);
                    showToast('Network error. Please try again.', 'error');
                } finally {
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            });
        });
    </script>

</x-app-layout>
