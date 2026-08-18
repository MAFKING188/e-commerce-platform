@section('title', 'Shipping & Delivery | SmartShop')

<x-app-layout>
<div class="container legal-page">
    @include('catalogdelivery::legal._hero', ['title' => 'Shipping & Delivery', 'updated' => 'August 18, 2026'])

    <div class="legal-prose">
        <h2>1. Processing Time</h2>
        <p>Orders are prepared and dispatched within 2–4 business days of payment confirmation. Each partner artisan inspects and packs their own pieces before dispatch.</p>

        <h2>2. Delivery Times</h2>
        <p>Estimated delivery is 5–10 business days for domestic orders and 10–20 business days internationally. Times are estimates and may vary with carrier conditions.</p>

        <h2>3. Tracking</h2>
        <p>Once dispatched, the delivery details recorded at checkout — recipient name, phone, and shipping address — are used by the carrier. Ensure these are accurate; SmartShop is not responsible for delivery failures caused by incorrect recipient details.</p>

        <h2>4. Delivery Notes</h2>
        <p>You may add delivery notes at checkout (e.g., "leave with concierge"). While we pass notes to the carrier, we cannot guarantee every instruction can be honoured.</p>

        <h2>5. Delays</h2>
        <p>Unforeseen carrier or customs delays may occur. We will keep you informed where we are able to, and support is available via the Contact page.</p>
    </div>
</div>
</x-app-layout>