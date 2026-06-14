@extends('layouts.app')

@section('title', $product->name . ' | SmartShop')
@section('description', \Illuminate\Support\Str::limit($product->description, 160))
@section('og_image', $product->image_url)

@section('styles')
<style>
    .product-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 4rem;
        align-items: start;
    }

    /* GALLERY */
    .gallery {
        position: sticky;
        top: 6rem;
    }

    .main-image-container {
        background: var(--surface-100);
        border-radius: 1.5rem;
        overflow: hidden;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-sm);
        margin-bottom: 1.5rem;
        aspect-ratio: 1;
    }

    .thumbnails {
        display: flex;
        gap: 1rem;
    }

    .thumbnail {
        width: 80px;
        height: 80px;
        border-radius: 0.75rem;
        overflow: hidden;
        border: 2px solid transparent;
        cursor: pointer;
        transition: all 0.2s ease;
        opacity: 0.6;
    }

    .thumbnail:hover, .thumbnail.active {
        opacity: 1;
        border-color: var(--brand-accent);
    }

    .thumbnail img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* INFO */
    .product-info h1 {
        font-size: 2.5rem;
        font-weight: 800;
        margin-bottom: 0.5rem;
        letter-spacing: -0.025em;
        color: var(--text-900);
    }

    .price-tag {
        font-size: 2rem;
        font-weight: 700;
        color: var(--brand-accent);
        margin: 1.5rem 0;
    }

    .description-box {
        margin: 2rem 0;
        padding: 2rem 0;
        border-top: 1px solid var(--border);
        border-bottom: 1px solid var(--border);
        color: var(--text-600);
        line-height: 1.8;
    }

    .cart-form {
        background: var(--surface-100);
        padding: 2rem;
        border-radius: 1rem;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-md);
    }

    .quantity-control {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
        color: var(--text-900);
    }

    .quantity-input {
        width: 80px;
        padding: 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid var(--border);
        background: var(--surface-200);
        color: var(--text-900);
        text-align: center;
        font-weight: 600;
    }

    @media (max-width: 768px) {
        .product-details {
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        .product-info {
            padding: 0 1rem;
        }

        .gallery {
            position: relative;
            top: 0;
        }

        .product-info h1 {
            font-size: 2rem;
        }

        .price-tag {
            font-size: 1.5rem;
        }

        .thumbnails {
            overflow-x: auto;
            padding-bottom: 0.5rem;
        }

        .thumbnail {
            flex-shrink: 0;
        }

        section {
            margin-top: 4rem !important;
            padding-top: 3rem !important;
        }

        section h2 {
            font-size: 1.75rem !important;
        }

        .suggestion-grid, .reviews-grid {
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)) !important;
            gap: 1.25rem !important;
        }
    }
</style>
@endsection

@section('content')

<div class="product-details">
    <!-- Left: Gallery -->
    <div class="gallery">
        <div class="main-image-container">
            <img id="mainImage" src="{{ $product->image_url }}" alt="{{ $product->name }}">
        </div>

        @if($product->images->count() > 1)
            <div class="thumbnails">
                @foreach($product->images as $image)
                    @php
                        $url = str_starts_with($image->url, 'http') ? $image->url : asset('storage/' . str_replace('storage/', '', ltrim($image->url, '/')));
                    @endphp
                    <div class="thumbnail {{ $loop->first ? 'active' : '' }}" onclick="updateMainImage(this, '{{ $url }}')">
                        <img src="{{ $url }}" alt="Thumbnail">
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <!-- Right: Info -->
    <div class="product-info">
        <span style="font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: var(--brand-accent); letter-spacing: 0.05em;">{{ $product->category->name ?? 'Collection' }}</span>
        <h1>{{ $product->name }}</h1>
        
        @if($product->stock > 0)
            <span style="font-size: 0.75rem; background: #dcfce7; color: #166534; padding: 0.25rem 0.75rem; border-radius: 2rem; font-weight: 700;">IN STOCK</span>
        @else
            <span style="font-size: 0.75rem; background: #fee2e2; color: #991b1b; padding: 0.25rem 0.75rem; border-radius: 2rem; font-weight: 700;">OUT OF STOCK</span>
        @endif

        <div class="price-tag">@money($product->price)</div>

        <div class="description-box">
            <h3 style="color: #1e293b; margin-bottom: 1rem; font-size: 1.1rem;">Description</h3>
            <p>{{ $product->description }}</p>
        </div>

        @if($product->stock > 0)
            <div class="cart-form">
                <form action="{{ route('cart.add') }}" method="POST">
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    
                    <div class="quantity-control">
                        <label style="font-weight: 600; font-size: 0.9rem;">Quantity</label>
                        <input type="number" name="quantity" value="1" min="1" max="{{ $product->stock }}" class="quantity-input">
                    </div>

                    <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem; font-size: 1rem;">
                        Add to Bag
                    </button>
                </form>
            </div>
        @else
            <div style="background: rgba(239, 68, 68, 0.05); border: 1px solid rgba(239, 68, 68, 0.1); color: var(--error); padding: 1.5rem; border-radius: 12px; font-weight: 600; text-align: center;">
                This curated piece is currently out of the archive.
            </div>
        @endif
    </div>
</div>

{{-- PHASE 7: THE TESTIMONIAL (REVIEWS) --}}
<section style="margin-top: 8rem; border-top: 1px solid var(--border); padding-top: 6rem;">
    <div style="margin-bottom: 4rem;">
        <span class="cat-badge">Member Feedback</span>
        <h2 style="font-size: 2.5rem; font-weight: 800; color: var(--text-900);">The Archive Reviews.</h2>
    </div>

    @if($product->reviews->isEmpty())
        <div style="padding: 4rem; background: var(--surface-100); border-radius: 2rem; border: 1px dashed var(--border); text-align: center;">
            <p style="color: var(--text-400); font-weight: 600;">No testimonials recorded for this piece yet.</p>
        </div>
    @else
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            @foreach($product->reviews as $review)
                <div style="background: var(--surface-100); padding: 2.5rem; border-radius: 1.5rem; border: 1px solid var(--border);">
                    <div style="display: flex; gap: 0.25rem; color: #f59e0b; margin-bottom: 1rem;">
                        @for($i = 0; $i < 5; $i++)
                            <svg width="16" height="16" fill="{{ $i < $review->rating ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.518 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        @endfor
                    </div>
                    <p style="color: var(--text-900); font-weight: 500; font-style: italic; line-height: 1.6; margin-bottom: 1.5rem;">"{{ $review->comment }}"</p>
                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                        <div style="width: 32px; height: 32px; background: var(--brand-accent-soft); color: var(--brand-accent); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.7rem;">
                            {{ substr($review->user->name, 0, 1) }}
                        </div>
                        <span style="font-size: 0.875rem; font-weight: 700; color: var(--text-600);">{{ $review->user->name }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</section>

{{-- PHASE 8: THE SUGGESTION (RELATED ITEMS) --}}
@php
    $relatedProducts = \App\Models\Product::where('category_id', $product->category_id)
        ->where('id', '!=', $product->id)
        ->take(4)
        ->get();
@endphp

@if($relatedProducts->isNotEmpty())
    <section style="margin-top: 10rem; margin-bottom: 5rem;">
        <div style="margin-bottom: 4rem; text-align: center;">
            <span class="cat-badge">Discovery</span>
            <h2 style="font-size: 2.5rem; font-weight: 800; color: var(--text-900);">Complete The Look.</h2>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 3rem;">
            @foreach($relatedProducts as $related)
                @include('components.product-card', ['product' => $related])
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

@endsection
