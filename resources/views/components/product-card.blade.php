<div class="card-wrapper" style="position: relative;">
    <a href="{{ route('product.show', $product->id) }}" class="card-luxury">
        <div class="img-container shimmer">
            <img src="{{ $product->image_url }}" 
                 alt="" 
                 onerror="this.src='https://images.unsplash.com/photo-1441984904996-e0b6ba687e12?w=800'; this.onerror=null;">
        </div>
        <div class="info-wrap">
            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                <span class="cat-badge">{{ $product->category->name ?? 'Collection' }}</span>
                @if($product->vendors->isNotEmpty())
                    <span style="font-size: 0.6rem; font-weight: 700; color: var(--text-400); text-transform: uppercase;">By {{ $product->vendors->first()->name }}</span>
                @endif
            </div>
            <h3>{{ $product->name }}</h3>
            <div class="price-tag">${{ number_format($product->price, 0) }}</div>
        </div>
    </a>

    {{-- PHASE 10: THE WISHLIST (HEART TOGGLE) --}}
    <button class="wishlist-btn @if(auth()->check() && $product->isWishlistedByUser(auth()->id())) active @endif" onclick="toggleWishlist(this, {{ $product->id }})" title="Save to Archive">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
    </button>
</div>

<style>
    .wishlist-btn {
        position: absolute;
        top: 2rem;
        right: 2rem;
        z-index: 50;
        background: var(--surface-100);
        border: 1px solid var(--border);
        color: var(--text-400);
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    @media (max-width: 768px) {
        .wishlist-btn {
            top: 1rem;
            right: 1rem;
            width: 32px;
            height: 32px;
        }
    }

    .wishlist-btn:hover {
        color: var(--error);
        transform: scale(1.1);
        border-color: var(--error);
    }

    .wishlist-btn.active {
        background: var(--error);
        color: white;
        border-color: var(--error);
    }

    .card-luxury {
        display: flex;
        flex-direction: column;
        text-decoration: none;
        color: inherit;
        background: var(--surface-100);
        padding: 1.25rem;
        border-radius: 1.5rem;
        border: 1px solid var(--border);
        transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1), box-shadow 0.4s ease;
        height: 100%;
    }

    @media (max-width: 640px) {
        .card-luxury {
            padding: 0.75rem;
            border-radius: 1rem;
        }

        .info-wrap h3 {
            font-size: 0.95rem;
        }

        .price-tag {
            font-size: 1rem;
        }
    }

    .card-luxury:hover {
        transform: translateY(-8px);
        box-shadow: var(--shadow-lg);
    }

    .img-container {
        aspect-ratio: 1/1.2;
        border-radius: 1rem;
        overflow: hidden;
        background: var(--surface-200);
        margin-bottom: 1.25rem;
        position: relative;
    }

    .img-container img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .info-wrap {
        display: flex;
        flex-direction: column;
        flex-grow: 1;
    }

    .info-wrap h3 {
        font-size: 1.15rem;
        font-weight: 700;
        margin: 0.25rem 0 0.75rem 0;
        color: var(--text-900);
        line-height: 1.2;
    }

    .cat-badge {
        font-size: 0.65rem;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--brand-accent);
        letter-spacing: 0.1em;
    }

    .price-tag {
        font-size: 1.1rem;
        font-weight: 800;
        color: var(--text-900);
        margin-top: auto;
    }
</style>
