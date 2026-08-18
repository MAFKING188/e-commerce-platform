@section('title', 'Our Story | SmartShop')

<x-app-layout>

<div class="story-hero">
    <div class="story-hero-content">
        <span>EST. 2026</span>
        <h1>Crafting the <br>Future of Living.</h1>
    </div>
</div>

<div class="values-grid">
    <div class="value-item">
        <h3>Quality First</h3>
        <p>We source only the finest materials to ensure every piece in our collection stands the test of time and trend.</p>
    </div>
    <div class="value-item">
        <h3>Design Integrity</h3>
        <p>Minimalism is not just an aesthetic; it is a philosophy. We believe in purposeful design that enhances daily life.</p>
    </div>
    <div class="value-item">
        <h3>Sustainability</h3>
        <p>Our commitment to the planet is woven into our supply chain. We prioritize ethical manufacturing and eco-conscious packaging.</p>
    </div>
</div>

<div class="manifesto-section">
    <h2>Our Manifesto</h2>
    <div class="manifesto-text">
        SmartShop was born from a desire to bridge the gap between artisanal craftsmanship and modern accessibility. We don't just sell products; we curate experiences that define your personal space.
    </div>
</div>

{{-- SUPPORT THE ARCHIVE SECTION --}}
<section class="support-section about-support-section">
    <div class="support-card about-support-card">
        <h2 class="about-support-title">Support the Archive</h2>
        <p class="about-support-text">
            SmartShop is a continuous pursuit of architectural excellence and artisanal design. 
            Your contributions directly fund server infrastructure and the acquisition of 
            new learning resources for future project iterations.
        </p>
        <div class="about-support-actions">
            <a href="https://www.paypal.com/ncp/payment/Q3SN7Q7K8YDEU" target="_blank" class="btn btn-primary about-support-btn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>
                Support Via PayPal
            </a>
        </div>
    </div>
</section>

</x-app-layout>
