@section('title', 'Shopping Cart | SmartShop')

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const bankTransferRadio = document.getElementById('bank-transfer');
    const paypalRadio = document.getElementById('paypal');
    const vendorBankDetails = document.getElementById('vendor-bank-details');
    const hiddenPaymentMethod = document.getElementById('hidden-payment-method');

    function toggleBankDetails() {
        if (bankTransferRadio && vendorBankDetails) {
            vendorBankDetails.style.display = bankTransferRadio.checked ? 'block' : 'none';
        }
        if (hiddenPaymentMethod) {
            hiddenPaymentMethod.value = bankTransferRadio && bankTransferRadio.checked ? 'bank_transfer' : 'paypal';
        }
    }

    if (bankTransferRadio) {
        bankTransferRadio.addEventListener('change', toggleBankDetails);
    }
    if (paypalRadio) {
        paypalRadio.addEventListener('change', toggleBankDetails);
    }

    toggleBankDetails();
});
</script>
@endsection

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
                        @if($item->product->partners->count())
                            <p class="vendor-badge" style="font-size: 0.75rem; color: #666; margin-top: 0.25rem;">Sold by: {{ $item->product->partners->first()->name }}</p>
                        @endif
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

            <!-- Payment Method Selection -->
            <div class="payment-methods">
                <h3>Payment Method</h3>
                <div class="payment-method-radio">
                    <input type="radio" id="paypal" name="payment_method" value="paypal" {{ old('payment_method', 'paypal') === 'paypal' ? 'checked' : '' }}>
                    <label for="paypal">PayPal (recommended)</label>
                </div>
                <div class="payment-method-radio">
                    <input type="radio" id="bank-transfer" name="payment_method" value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'checked' : '' }}>
                    <label for="bank-transfer">Bank Transfer</label>
                    <span class="payment-method-note">Pay each vendor directly</span>
                </div>
            </div>

            <!-- Vendor Bank Details (shown when Bank Transfer selected) -->
            <div id="vendor-bank-details" class="vendor-bank-details" style="display: none; margin-top: 1rem; max-height: 220px; overflow-y: auto; border: 1px solid var(--border); border-radius: 8px; padding: 1rem;">
                <h3 style="font-size: 0.9rem; margin-bottom: 0.5rem;">Vendor Bank Details</h3>
                <p class="text-muted small" style="margin-bottom: 0.75rem;">Transfer to each vendor's account and upload proof of payment.</p>

                @php
                    $vendors = $cart->items->groupBy(function($item) {
                        return $item->product->partners->first()->id ?? 'unknown';
                    });
                @endphp

                @foreach($vendors as $vendorId => $items)
                    @php
                        $vendor = $items->first()->product->partners->first();
                        $bankDetail = $vendor ? $vendor->bankDetails : null;
                        $vendorTotal = $items->sum(fn($item) => $item->product->price * $item->quantity);
                    @endphp

                    @if($vendor && $bankDetail && $bankDetail->is_active)
                    <div class="vendor-bank-card" style="border: 1px solid #e0e0e0; border-radius: 6px; padding: 0.75rem; margin-bottom: 0.75rem; background: #fafafa;">
                        <h4 style="font-size: 0.85rem; margin-bottom: 0.25rem;">{{ $vendor->name }}</h4>
                        <p class="text-muted small" style="margin-bottom: 0.25rem;">Amount: <strong>@money($vendorTotal)</strong></p>

                        @if($bankDetail->bank_details_image)
                            <a href="{{ asset('storage/' . $bankDetail->bank_details_image) }}" target="_blank">
                                <img src="{{ asset('storage/' . $bankDetail->bank_details_image) }}" alt="Bank Details for {{ $vendor->name }}" style="max-width: 100%; max-height: 120px; border-radius: 4px; margin: 0.25rem 0;">
                            </a>
                        @endif

                        <div class="bank-details-text" style="font-size: 0.8rem; line-height: 1.5;">
                            @if($bankDetail->account_holder)
                                <strong>Account Holder:</strong> {{ $bankDetail->account_holder }}<br>
                            @endif
                            @if($bankDetail->iban)
                                <strong>IBAN:</strong> {{ $bankDetail->iban }}<br>
                            @endif
                            @if($bankDetail->bank_name)
                                <strong>Bank:</strong> {{ $bankDetail->bank_name }}<br>
                            @endif
                            @if($bankDetail->swift_bic)
                                <strong>SWIFT/BIC:</strong> {{ $bankDetail->swift_bic }}<br>
                            @endif
                            @if($bankDetail->additional_info)
                                <br><strong>Note:</strong> {{ $bankDetail->additional_info }}
                            @endif
                        </div>

                        <p class="text-muted small" style="margin-top: 0.5rem;">Reference: <code>ORDER-{{ $cart->id ?? 'XXXX' }}-{{ $vendor->id }}</code></p>
                    </div>
                    @elseif($vendor)
                    <div class="vendor-bank-card" style="border: 1px solid #ffcccc; border-radius: 6px; padding: 0.75rem; margin-bottom: 0.75rem; background: #fff5f5;">
                        <h4 style="font-size: 0.85rem;">{{ $vendor->name }}</h4>
                        <p class="text-muted small">Amount: <strong>@money($vendorTotal)</strong></p>
                        <div class="pc-alert pc-alert--warning" style="margin-top: 0.25rem; font-size: 0.8rem;">
                            No bank details configured. Please use PayPal.
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>

            <button type="submit" class="btn btn-primary btn-checkout" form="checkout-form">
                Confirm & Checkout
            </button>

            @if($cart->share_token ?? false)
            <button type="button" onclick="shareCart()" style="width: 100%; margin-top: 0.75rem; padding: 0.6rem; border: 1px solid var(--border); border-radius: 8px; background: var(--surface-100); color: var(--text-600); font-size: 0.85rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><line x1="8.59" y1="13.51" x2="15.42" y2="17.49"/><line x1="15.41" y1="6.51" x2="8.59" y2="10.49"/></svg>
                Share This Bag
            </button>
            <span id="cart-share-toast" style="display: none; text-align: center; font-size: 0.8rem; color: #16a34a; font-weight: 600; margin-top: 0.5rem;">Link copied!</span>
            <script>
            function shareCart() {
                const url = '{{ url("/cart/shared/{$cart->share_token}") }}';
                const text = 'Check out my SmartShop bag — {{ $cart->items->count() }} item(s) worth ${{ number_format($total, 2) }}';
                if (navigator.share) {
                    navigator.share({ title: 'My SmartShop Bag', text, url }).catch(() => {});
                } else {
                    navigator.clipboard.writeText(url).then(() => {
                        const toast = document.getElementById('cart-share-toast');
                        toast.style.display = 'block';
                        setTimeout(() => { toast.style.display = 'none'; }, 2000);
                    });
                }
            }
            </script>
            @endif

            <p class="checkout-secure-note">
                SSL-secured payment · Full refund if your piece does not arrive as described
            </p>
        </div>

        <form method="POST" action="{{ route('orders.store') }}" id="checkout-form">
        @csrf
        <input type="hidden" name="payment_method" id="hidden-payment-method" value="{{ old('payment_method', 'paypal') }}">
        @if ($errors->has('checkout'))
            <div class="form-error checkout-error" role="alert">{{ $errors->first('checkout') }}</div>
        @endif

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
    @if (session('stepup.pending') || $errors->has('code'))
            <div class="delivery-section">
                <div class="delivery-head">
                    <h2>Verify Your Email</h2>
                    <p>A 6-digit code was sent to <strong>{{ auth()->user()->email }}</strong>. Enter it to confirm your order. It expires in 10 minutes.</p>
                </div>
                <div class="form-group form-group--full">
                    <label class="form-label" for="code">Verification Code</label>
                    <input type="text" name="code" id="code" class="form-input" inputmode="numeric" maxlength="10" required autofocus>
                    @error('code') <span class="form-error">{{ $message }}</span> @enderror
                </div>
            </div>
        @endif

        <button type="submit" class="btn btn-primary btn-checkout btn-checkout--inline">
            @if (session('stepup.pending') || $errors->has('code')) Confirm & Place Order @else Confirm & Checkout @endif
        </button>
    </form>
@else
    <div class="empty-state">
        <h2>Your bag is empty</h2>
        <p>Looks like you haven't added anything yet.</p>
        <a href="{{ route('shop') }}" class="btn btn-primary">Start Shopping</a>
    </div>
@endif

</x-app-layout>