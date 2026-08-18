@section('title', 'Returns & Refunds | SmartShop')

<x-app-layout>
<div class="container legal-page">
    @include('catalogdelivery::legal._hero', ['title' => 'Returns & Refunds', 'updated' => 'August 18, 2026'])

    <div class="legal-prose">
        <h2>1. Return Window</h2>
        <p>You may return a piece within 14 days of delivery for a full refund, provided it is unused, undamaged, and in its original packaging.</p>

        <h2>2. Damaged or Incorrect Items</h2>
        <p>If a piece arrives damaged or does not match the order, contact support within 7 days of delivery with photos. We will arrange a replacement or refund, including return shipping costs.</p>

        <h2>3. How to Start a Return</h2>
        <p>Request a return from the Contact page, quoting your order number and the reason. We will confirm the return address and any instructions within 2 business days.</p>

        <h2>4. Refunds</h2>
        <p>Refunds are issued to the original payment method within 5–10 business days of the returned piece being received and inspected.</p>

        <h2>5. Exceptions</h2>
        <p>Custom, personalised, or commissioned pieces cannot be returned unless faulty. This does not affect your statutory rights.</p>
    </div>
</div>
</x-app-layout>