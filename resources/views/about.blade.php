@section('title', 'Our Story | SmartShop')

@section('styles')
<style>
    .story-hero {
        height: 60vh;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        background: #0f172a;
        color: white;
        border-radius: var(--radius-lg);
        margin-bottom: 6rem;
        position: relative;
        overflow: hidden;
    }

    .story-hero::before {
        content: '';
        position: absolute;
        inset: 0;
        background: url('https://images.unsplash.com/photo-1497366216548-37526070297c?q=80&w=2000&auto=format&fit=crop') center/cover;
        opacity: 0.4;
    }

    .story-hero-content {
        position: relative;
        z-index: 10;
        max-width: 800px;
        padding: 0 2rem;
    }

    .story-hero h1 {
        font-size: 4.5rem;
        font-weight: 800;
        letter-spacing: -0.05em;
        line-height: 1;
        margin-bottom: 1.5rem;
    }

    .values-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 4rem;
        margin-bottom: 8rem;
    }

    .value-item h3 {
        font-size: 1.5rem;
        font-weight: 800;
        margin-bottom: 1rem;
        letter-spacing: -0.02em;
    }

    .value-item p {
        color: var(--text-600);
        line-height: 1.8;
    }

    .manifesto-section {
        background: white;
        padding: 6rem;
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        margin-bottom: 8rem;
        text-align: center;
    }

    .manifesto-section h2 {
        font-size: 3rem;
        font-weight: 800;
        margin-bottom: 2rem;
        letter-spacing: -0.04em;
    }

    .manifesto-text {
        font-size: 1.25rem;
        color: var(--text-600);
        max-width: 700px;
        margin: 0 auto;
        line-height: 1.8;
    }

    @media (max-width: 768px) {
        .values-grid { grid-template-columns: 1fr; }
        .story-hero h1 { font-size: 3rem; }
        .manifesto-section { padding: 3rem 2rem; }
    }
</style>
@endsection

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
<section class="support-section" style="margin-top: 10rem; padding-top: 6rem; border-top: 1px solid var(--border); text-align: center;">
    <div class="support-card" style="background: var(--surface-100); padding: 4rem; border-radius: var(--radius-lg); border: 1px solid var(--brand-accent); box-shadow: var(--shadow-lg); max-width: 800px; margin: 0 auto; position: relative; overflow: hidden;">
        <h2 style="font-size: 2.5rem; font-weight: 800; margin-bottom: 1.5rem; color: var(--text-900);">Support the Archive</h2>
        <p style="font-size: 1.1rem; color: var(--text-600); margin-bottom: 3rem; line-height: 1.6; max-width: 600px; margin-left: auto; margin-right: auto;">
            SmartShop is a continuous pursuit of architectural excellence and artisanal design. 
            Your contributions directly fund server infrastructure and the acquisition of 
            new learning resources for future project iterations.
        </p>
        <div style="display: flex; justify-content: center;">
            <a href="https://www.paypal.com/ncp/payment/Q3SN7Q7K8YDEU" target="_blank" class="btn btn-primary" style="padding: 1.25rem 3rem; font-size: 1rem; border-radius: 12px; background: var(--brand-accent); letter-spacing: 0.05em;">
                💳 SUPPORT VIA PAYPAL
            </a>
        </div>
    </div>
</section>

</x-app-layout>
