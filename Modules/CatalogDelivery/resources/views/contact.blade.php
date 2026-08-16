@section('title', 'Support | SmartShop')

<x-app-layout>

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
                    <textarea class="auth-input no-resize" rows="5" placeholder="How can we assist you today?"></textarea>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary contact-submit-btn">
                Submit Inquiry
            </button>
        </form>
    </div>
</div>

</x-app-layout>
