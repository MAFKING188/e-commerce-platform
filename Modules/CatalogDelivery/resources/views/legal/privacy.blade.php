@section('title', 'Privacy Policy | SmartShop')

<x-app-layout>
<div class="container legal-page">
    @include('catalogdelivery::legal._hero', ['title' => 'Privacy Policy', 'updated' => 'August 18, 2026'])

    <div class="legal-prose">
        <h2>1. What We Collect</h2>
        <p>SmartShop collects the information you provide when creating an account — your name, email address, and password (stored encrypted). We also record the delivery details you enter at checkout so orders can be shipped, and we log standard technical data such as IP address and browser type to keep the platform secure and reliable.</p>

        <h2>2. How We Use Your Information</h2>
        <p>We use your data to operate your account, process and deliver orders, respond to support requests, and improve the shopping experience. Payment transactions are processed by PayPal; SmartShop never stores your card details. We do not sell your personal information to third parties.</p>

        <h2>3. Reviews and Public Content</h2>
        <p>Reviews you write on the platform are published with your first name and avatar so other members can see genuine feedback. Anything you choose to publish is visible to the public and cannot be fully removed once moderated as approved — you may request deletion at any time by contacting support.</p>

        <h2>4. Data Retention</h2>
        <p>We retain your account data while your account is active. Orders are retained for record-keeping and tax purposes as required by law. You may request account deletion, after which personal data is removed or anonymised within 30 days, except where retention is legally required.</p>

        <h2>5. Your Rights</h2>
        <p>You may access, correct, or delete your personal data at any time from your profile settings, or by contacting support. You may also export a copy of the data we hold about you on request.</p>

        <h2>6. Cookies</h2>
        <p>The platform uses essential cookies for authentication (session and CSRF protection) and a preference cookie to remember your display currency and theme. No third-party tracking cookies are used.</p>

        <h2>7. Contact</h2>
        <p>Questions about this policy can be sent to the support channel listed on the Contact page.</p>
    </div>
</div>
</x-app-layout>