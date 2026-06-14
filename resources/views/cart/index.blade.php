@extends('layouts.app')

@section('title', 'Shopping Cart | SmartShop')

@section('styles')
<style>
    .cart-header {
        margin-bottom: 3rem;
    }

    .cart-header h1 {
        font-size: 2.5rem;
        font-weight: 800;
        letter-spacing: -0.04em;
        color: var(--text-900);
    }

    /* CART ITEMS */
    .cart-items-list {
        background: var(--surface-100);
        border-radius: 12px;
        border: 1px solid var(--border);
        overflow: hidden;
    }

    .cart-item {
        display: grid;
        grid-template-columns: 100px 1fr auto;
        gap: 1.5rem;
        padding: 1.5rem;
        border-bottom: 1px solid var(--border);
        align-items: center;
    }

    .cart-item:last-child {
        border-bottom: none;
    }

    .cart-item-img {
        width: 100px;
        height: 100px;
        border-radius: 8px;
        background: var(--surface-200);
        overflow: hidden;
    }

    .cart-item-info h3 {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        color: var(--text-900);
    }

    .cart-item-info p {
        font-size: 0.875rem;
        color: var(--text-600);
    }

    .subtotal-val {
        font-size: 1.1rem;
        font-weight: 700;
        color: var(--text-900);
        display: block;
        margin-bottom: 0.5rem;
    }

    /* SUMMARY CARD */
    .summary-card {
        background: var(--surface-100);
        padding: 2rem;
        border-radius: 12px;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-sm);
        position: sticky;
        top: 8rem;
    }

    .summary-card h2 {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid var(--border);
        color: var(--text-900);
    }

    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 1rem;
        color: var(--text-600);
    }

    .summary-total {
        display: flex;
        justify-content: space-between;
        margin-top: 1.5rem;
        padding-top: 1.5rem;
        border-top: 2px solid var(--surface-200);
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--text-900);
    }

    .empty-state {
        text-align: center;
        padding: 6rem 0;
        background: var(--surface-100);
        border-radius: 12px;
        border: 1px dashed var(--border);
        color: var(--text-900);
    }

    .cart-grid {
        display: grid;
        grid-template-columns: 1fr 380px;
        gap: 3rem;
    }

    @media (max-width: 1024px) {
        .cart-grid { grid-template-columns: 1fr; gap: 2rem; }
        .summary-card { position: relative; top: 0; }
    }

    @media (max-width: 640px) {
        .cart-header h1 { font-size: 2rem; }
        .cart-item {
            grid-template-columns: 80px 1fr;
            gap: 1rem;
            padding: 1.25rem;
        }
        .cart-item-actions {
            grid-column: span 2;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-top: 1px solid var(--border);
            padding-top: 1rem;
            margin-top: 0.5rem;
        }
    }
</style>
@endsection

@section('content')

<div class="cart-header">
    <h1>Your Bag</h1>
</div>

@if($cart && $cart->items->count())
    <div class="cart-grid">
        <!-- Main List -->
        <div class="cart-items-list">
            @php $total = 0; @endphp
            @foreach($cart->items as $item)
                @php
                    $subtotal = $item->product->price * $item->quantity;
                    $total += $subtotal;
                @endphp
                <div class="cart-item">
                    <div class="cart-item-img">
                        <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}">
                    </div>
                    
                    <div class="cart-item-info">
                        <h3>{{ $item->product->name }}</h3>
                        <p>Unit Price: @money($item->product->price)</p>
                        <p>Quantity: {{ $item->quantity }}</p>
                    </div>

                    <div class="cart-item-actions">
                        <span class="subtotal-val">@money($subtotal)</span>
                        <form method="POST" action="{{ route('cart.remove', $item->id) }}" style="margin: 0;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-remove">Remove</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Summary -->
        <div class="summary-card">
            <h2>Order Summary</h2>
            <div class="summary-row total-row">
                <span>Total</span>
                <span>@money($total)</span>
            </div>
            <div class="summary-row">
                <span>Shipping</span>
                <span style="color: #10b981; font-weight: 600;">Calculated at next step</span>
            </div>
            
            <div class="summary-row total-row">
                <span>Total</span>
                <span>@money($total)</span>
            </div>

            <form method="POST" action="{{ route('orders.store') }}" style="margin-top: 2rem;">
                @csrf
                <button type="submit" class="btn btn-primary" style="width: 100%; padding: 1rem;">
                    Confirm & Checkout
                </button>
            </form>
            
            <p style="text-align: center; font-size: 0.75rem; color: #94a3b8; margin-top: 1rem;">
                Secure Checkout Powered by LUWI
            </p>
        </div>
    </div>
@else
    <div class="empty-state">
        <h2 style="font-weight: 700; margin-bottom: 1rem; color: #1e293b;">Your bag is empty</h2>
        <p style="color: #64748b; margin-bottom: 2rem;">Looks like you haven't added anything yet.</p>
        <a href="{{ route('shop') }}" class="btn btn-primary">Start Shopping</a>
    </div>
@endif

@endsection
