@section('title', 'Join the Collection | LUWI')

<x-app-layout>
<div class="auth-wrapper">
    <div class="auth-card">
        <h1 class="auth-title">Join the Collection</h1>
        <p class="auth-subtitle">Create your account to start shopping.</p>

        <form action="{{ url('/createaccount') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone" value="{{ old('phone') }}" required placeholder="+33 6 12 34 56 78" class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">Country</label>
                <select name="country" class="form-input" required>
                    <option value="" disabled {{ old('country') ? '' : 'selected' }}>Select your country</option>
                    @foreach (\Modules\IdentityAccess\Support\Countries::all() as $code => $name)
                        <option value="{{ $code }}" {{ old('country') === $code ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" required placeholder="Min. 8 characters" class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="password_confirmation" required class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">Account Type</label>
                <select name="role" class="form-input" required>
                    <option value="user">Private Member (Instant Access)</option>
                    <option value="partner">Artisan — Sell Your Work (Reviewed Before Activation)</option>
                </select>
            </div>

            <label class="auth-checkbox">
                <input type="checkbox" name="newsletter_optin" value="1" {{ old('newsletter_optin') ? 'checked' : '' }}>
                <span>Keep me updated on new acquisitions and exclusive offers.</span>
            </label>

            <button type="submit" class="auth-button">Create Account</button>
        </form>

        @include('identityaccess::partials.auth-google')

        <p class="switch-auth">
            Already a member? <a href="{{ url('/login') }}">Sign in here</a>
        </p>
    </div>
</div>
</x-app-layout>