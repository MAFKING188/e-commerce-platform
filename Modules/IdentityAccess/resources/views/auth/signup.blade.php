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
                <label class="form-label">Password</label>
                <input type="password" name="password" required placeholder="Min. 8 characters" class="form-input">
            </div>

            <div class="form-group">
                <label class="form-label">Account Type</label>
                <select name="role" class="form-input" required>
                    <option value="user">Private Member (Instant Access)</option>
                    <option value="partner">Partner Artisan (Requires Confirmation)</option>
                    <option value="admin">System Administrator (Requires Confirmation)</option>
                </select>
            </div>

            <button type="submit" class="auth-button">Create Account</button>
        </form>

        <p class="switch-auth">
            Already a member? <a href="{{ url('/login') }}">Sign in here</a>
        </p>
    </div>
</div>
</x-app-layout>
