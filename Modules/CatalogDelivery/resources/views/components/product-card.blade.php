<div class="card-wrapper">
    <a href="{{ route('product.show', $product->id) }}" class="card-luxury">
        <div class="img-container shimmer">
            <img src="{{ $product->image_url }}" 
                 alt="" 
                 loading="lazy" 
                 decoding="async"
                 onerror="this.src='https://images.unsplash.com/photo-1441984904996-e0b6ba687e12?w=800'; this.onerror=null;">
        </div>
        <div class="info-wrap">
            <div class="card-top-row">
                <span class="cat-badge">{{ $product->category->name ?? 'Collection' }}</span>
                @if($product->partners->isNotEmpty())
                    <span class="card-partner">By {{ $product->partners->first()->name }}</span>
                @endif
            </div>
            <h3>{{ $product->name }}</h3>
            <div class="price-tag">@money($product->price)</div>
        </div>
    </a>

    {{-- PHASE 10: THE WISHLIST (HEART TOGGLE) --}}
    <button class="wishlist-btn @if(auth()->check() && $product->isWishlistedByUser(auth()->id())) active @endif" onclick="toggleWishlist(this, {{ $product->id }})" title="Save to Archive">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
    </button>
</div>
