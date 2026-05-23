@extends('layouts.app')

@section('title', 'Support | SmartShop')

@section('styles')
<style>
    .contact-layout {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8rem;
        padding: 4rem 0;
    }

    .contact-info h1 {
        font-size: 4rem;
        font-weight: 800;
        letter-spacing: -0.05em;
        line-height: 1;
        margin-bottom: 2.5rem;
    }

    .contact-info p {
        font-size: 1.15rem;
        color: var(--text-600);
        margin-bottom: 4rem;
        max-width: 400px;
    }

    .contact-methods {
        display: flex;
        flex-direction: column;
        gap: 2.5rem;
    }

    .method-item h4 {
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.2em;
        color: var(--brand-accent);
        margin-bottom: 0.5rem;
    }

    .method-item p {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--brand-primary);
        margin: 0;
    }

    .contact-form-box {
        background: var(--surface-100);
        padding: 5rem;
        border-radius: 2.5rem;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-lg);
    }

    .form-group { margin-bottom: 2rem; }
    
    .form-label {
        display: block;
        font-size: 0.75rem;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--text-400);
        margin-bottom: 1rem;
        letter-spacing: 0.1em;
    }

    .auth-input {
        width: 100%;
        padding: 1.25rem 1.5rem;
        border-radius: 16px;
        border: 1px solid var(--border);
        background: var(--surface-200);
        color: var(--text-900);
        font-family: inherit;
        font-size: 1rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .auth-input:focus {
        border-color: var(--brand-accent);
        background: var(--surface-100);
        outline: none;
        box-shadow: 0 0 0 4px var(--brand-accent-soft);
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 2rem;
    }

    .full-width {
        grid-column: span 2;
    }

    @media (max-width: 1024px) {
        .contact-layout { grid-template-columns: 1fr; gap: 4rem; }
        .contact-form-box { padding: 3rem; }
    }
</style>
@endsection

@section('content')

<div class="contact-layout">
    <div class="contact-info">
        <h1>Connect <br>With Us.</h1>
        <p>Our dedicated support team is here to assist you with any inquiries or tailored consultations regarding our archive.</p>

        <div class="contact-methods">
            <div class="method-item">
                <h4>General Inquiries</h4>
                <p>m.luwi0049@uca.ca.ma</p>
            </div>
            <div class="method-item">
                <h4>Studio Address</h4>
                <p>124 Design District, <br>Marrakech, Morocco</p>
            </div>
            <div class="method-item">
                <h4>Client Support</h4>
                <p>+212 (0) 6 24 54 84 29</p>
            </div>
        </div>
    </div>

    <div class="contact-form-box">
        <form action="#" method="POST">
            @csrf
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label">First Name</label>
                    <input type="text" class="auth-input" placeholder="e.g. Jane">
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name</label>
                    <input type="text" class="auth-input" placeholder="e.g. Doe">
                </div>
                <div class="form-group full-width">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="auth-input" placeholder="jane@example.com">
                </div>
                <div class="form-group full-width">
                    <label class="form-label">Support Inquiry</label>
                    <textarea class="auth-input" rows="5" placeholder="How can we assist you today?" style="resize: none;"></textarea>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-top: 2rem; padding: 1.5rem; border-radius: 16px; font-size: 1rem;">
                Submit Inquiry
            </button>
        </form>
    </div>
</div>

@endsection
