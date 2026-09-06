@section('title', 'Shared Cart | SmartShop')

<x-app-layout>

<div class="cart-header">
    <h1>Shared Bag</h1>
    <p style="color: var(--text-600); margin-top: 0.5rem;">Someone shared their cart with you. Review the items below.</p>
</div>

@if($cart && $cart->items->count())
    <div class="cart-grid">
        <div class="cart-items-list">
            @foreach($cart->items as $item)
                <div class="cart-item">
                    <div class="cart-item-img">
                        <img src="{{ $item->product->image_url }}" alt="{{ $item->product->name }}">
                    </div>
                    <div class="cart-item-info">
                        <h3>{{ $item->product->name }}</h3>
                        <p>Unit Price: @money($item->product->price)</p>
                        <p>Quantity: {{ $item->quantity }}</p>
                        @if($item->product->partners->count())
                            <p class="vendor-badge" style="font-size: 0.75rem; color: #666; margin-top: 0.25rem;">Sold by: {{ $item->product->partners->first()->name }}</p>
                        @endif
                        @if($item->product->stock < 1)
                            <p style="color: #dc2626; font-size: 0.8rem; font-weight: 600;">Out of stock</p>
                        @endif
                    </div>
                    <div class="cart-item-actions">
                        <span class="subtotal-val">@money($item->product->price * $item->quantity)</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="summary-card">
            <h2>Order Summary</h2>
            <div class="summary-row">
                <span>Items</span>
                <span>{{ $cart->items->count() }}</span>
            </div>
            <div class="summary-row">
                <span>Subtotal</span>
                <span>@money($total)</span>
            </div>
            <div class="summary-row">
                <span>Shipping</span>
                <span>Included at delivery</span>
            </div>
            <div class="summary-row total-row">
                <span>Total</span>
                <span>@money($total)</span>
            </div>

            @auth
                <form method="POST" action="{{ route('cart.clone', $cart->share_token) }}">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-checkout" style="width: 100%; margin-top: 1rem;">
                        Clone to My Bag & Checkout
                    </button>
                </form>
                <p style="font-size: 0.8rem; color: var(--text-500); text-align: center; margin-top: 0.75rem;">
                    Items will be added to your existing bag.
                </p>
            @else
                <a href="{{ route('login') }}" class="btn btn-primary btn-checkout" style="width: 100%; margin-top: 1rem; text-align: center; display: block;">
                    Log In to Clone & Checkout
                </a>
                <p style="font-size: 0.8rem; color: var(--text-500); text-align: center; margin-top: 0.75rem;">
                    You need an account to purchase these items.
                </p>
            @endauth
        </div>
    </div>
@else
    <div class="empty-state">
        <h2>This cart is empty</h2>
        <p>The shared cart has no items.</p>
        <a href="{{ route('shop') }}" class="btn btn-primary">Start Shopping</a>
    </div>
@endif

</x-app-layout>
