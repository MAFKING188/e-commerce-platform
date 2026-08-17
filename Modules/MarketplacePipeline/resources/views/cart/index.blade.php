@section('title', 'Shopping Cart | SmartShop')

<x-app-layout>

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

</x-app-layout>
