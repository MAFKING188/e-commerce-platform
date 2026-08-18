@section('title', 'Terms & Conditions | SmartShop')

<x-app-layout>
<div class="container legal-page">
    @include('catalogdelivery::legal._hero', ['title' => 'Terms & Conditions', 'updated' => 'August 18, 2026'])

    <div class="legal-prose">
        <h2>1. Acceptance of Terms</h2>
        <p>By creating an account or placing an order on SmartShop, you agree to these Terms & Conditions. If you do not agree, please do not use the platform.</p>

        <h2>2. Accounts</h2>
        <p>You are responsible for safeguarding your credentials and for all activity under your account. Accounts used for automated activity, reselling access, or misrepresentation may be suspended.</p>

        <h2>3. Orders and Payment</h2>
        <p>All prices are displayed in the currency you select and are inclusive of applicable taxes unless stated otherwise. Orders are confirmed once payment is authorised by PayPal. We reserve the right to refuse or cancel an order where fraud or pricing error is suspected.</p>

        <h2>4. Partner Artisans</h2>
        <p>Products are sourced from independent partner artisans. SmartShop facilitates the transaction but does not manufacture the goods. Quality and authenticity concerns are handled through our Returns policy.</p>

        <h2>5. Reviews</h2>
        <p>Reviews must be honest, first-hand, and free of promotional or offensive content. SmartShop moderates reviews and may remove content that violates these rules.</p>

        <h2>6. Limitation of Liability</h2>
        <p>SmartShop is provided "as is". To the maximum extent permitted by law, SmartShop is not liable for indirect or consequential losses arising from use of the platform.</p>

        <h2>7. Changes</h2>
        <p>We may update these terms from time to time. Continued use of the platform after changes constitutes acceptance of the revised terms.</p>
    </div>
</div>
</x-app-layout>