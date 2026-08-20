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
        <form action="{{ route('contact.store') }}" method="POST">
            @csrf
            @if (session('success'))
                <p class="form-success">{{ session('success') }}</p>
            @endif
            <div class="form-grid">
                <div class="form-group">
                    <label class="form-label" for="first_name">First Name</label>
                    <input id="first_name" type="text" name="first_name" class="auth-input" placeholder="e.g. Jane" value="{{ old('first_name') }}" required>
                    @error('first_name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group">
                    <label class="form-label" for="last_name">Last Name</label>
                    <input id="last_name" type="text" name="last_name" class="auth-input" placeholder="e.g. Doe" value="{{ old('last_name') }}" required>
                    @error('last_name') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group full-width">
                    <label class="form-label" for="email">Email Address</label>
                    <input id="email" type="email" name="email" class="auth-input" placeholder="jane@example.com" value="{{ old('email') }}" required>
                    @error('email') <p class="form-error">{{ $message }}</p> @enderror
                </div>
                <div class="form-group full-width">
                    <label class="form-label" for="message">Support Inquiry</label>
                    <textarea id="message" name="message" class="auth-input no-resize" rows="5" placeholder="How can we assist you today?" required>{{ old('message') }}</textarea>
                    @error('message') <p class="form-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <button type="submit" class="btn btn-primary contact-submit-btn">
                Submit Inquiry
            </button>
        </form>
    </div>
</div>

</x-app-layout>