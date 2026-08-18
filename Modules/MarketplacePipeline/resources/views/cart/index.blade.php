@section('title', 'Shopping Cart | SmartShop')

<x-app-layout>

<div class="cart-header">
    <h1>Your Bag</h1>
</div>

@if($cart && $cart->items->count())
    <div class="cart-grid">
        <!-- Main List -->
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
                    </div>

                    <div class="cart-item-actions">
                        <span class="subtotal-val">@money($item->product->price * $item->quantity)</span>
                        <form method="POST" action="{{ route('cart.remove', $item->id) }}">
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

            <button type="submit" class="btn btn-primary btn-checkout" form="checkout-form">
                Confirm & Checkout
            </button>

            <p class="checkout-secure-note">
                Secure Checkout Powered by LUWI
            </p>
        </div>
    </div>

    <form method="POST" action="{{ route('orders.store') }}" id="checkout-form">
        @csrf

        <!-- Delivery Details -->
        <div class="delivery-section">
            <div class="delivery-head">
                <h2>Delivery Details</h2>
                <p>Tell us where to send your pieces. Your primary residence is pre-filled — update if this delivery differs.</p>
            </div>

            <div class="delivery-grid">
                <div class="form-group">
                    <label class="form-label" for="recipient_name">Recipient Full Name</label>
                    <input type="text" name="recipient_name" id="recipient_name" class="form-input" value="{{ old('recipient_name', auth()->user()->name) }}" placeholder="Full name of the person receiving the order" required>
                    @error('recipient_name') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="recipient_phone">Contact Phone</label>
                    <input type="tel" name="recipient_phone" id="recipient_phone" class="form-input" value="{{ old('recipient_phone') }}" placeholder="+1 555 000 0000" required>
                    @error('recipient_phone') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group form-group--full">
                    <label class="form-label" for="shipping_line1">Street Address</label>
                    <input type="text" name="shipping_line1" id="shipping_line1" class="form-input" value="{{ old('shipping_line1', $address->line1 ?? '') }}" placeholder="Street, building, apartment" required>
                    @error('shipping_line1') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group form-group--full">
                    <label class="form-label" for="shipping_line2">Address Line 2 <span class="form-optional">(optional)</span></label>
                    <input type="text" name="shipping_line2" id="shipping_line2" class="form-input" value="{{ old('shipping_line2', $address->line2 ?? '') }}" placeholder="Floor, suite, door code">
                    @error('shipping_line2') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="shipping_city">City</label>
                    <input type="text" name="shipping_city" id="shipping_city" class="form-input" value="{{ old('shipping_city', $address->city ?? '') }}" required>
                    @error('shipping_city') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label class="form-label" for="shipping_state">State / Province <span class="form-optional">(optional)</span></label>
                    <input type="text" name="shipping_state" id="shipping_state" class="form-input" value="{{ old('shipping_state', $address->state ?? '') }}">
                </div>

                <div class="form-group">
                    <label class="form-label" for="shipping_zip">Postal Code <span class="form-optional">(optional)</span></label>
                    <input type="text" name="shipping_zip" id="shipping_zip" class="form-input" value="{{ old('shipping_zip', $address->zip ?? '') }}">
                </div>

                <div class="form-group">
                    <label class="form-label" for="shipping_country">Country</label>
                    <input type="text" name="shipping_country" id="shipping_country" class="form-input" value="{{ old('shipping_country', $address->country ?? '') }}" required>
                    @error('shipping_country') <span class="form-error">{{ $message }}</span> @enderror
                </div>

                <div class="form-group form-group--full">
                    <label class="form-label" for="delivery_notes">Delivery Notes <span class="form-optional">(optional)</span></label>
                    <textarea name="delivery_notes" id="delivery_notes" class="form-input" rows="3" placeholder="Gate code, preferred delivery window, carrier instructions…">{{ old('delivery_notes') }}</textarea>
                </div>
            </div>
        </div>
    </form>
@else
    <div class="empty-state">
        <h2>Your bag is empty</h2>
        <p>Looks like you haven't added anything yet.</p>
        <a href="{{ route('shop') }}" class="btn btn-primary">Start Shopping</a>
    </div>
@endif

</x-app-layout>