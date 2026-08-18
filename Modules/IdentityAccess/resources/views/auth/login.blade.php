@section('title', 'Sign In | LUWI')

<x-app-layout>
<div class="auth-wrapper">
    <div class="auth-card auth-card--narrow">
        <h1 class="auth-title">Welcome Back</h1>
        <p class="auth-subtitle">Please enter your details to sign in.</p>

        <form method="POST" action="{{ url('/accessaccount') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-input" required autofocus>
            </div>

            <div class="form-group">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-input" required>
            </div>

            <div class="form-footer">
                <label class="remember-me">
                    <input type="checkbox" name="remember"> Remember me
                </label>
            </div>

            <button type="submit" class="auth-button">Sign In</button>
        </form>

        <p class="switch-auth">
            Don't have an account? <a href="{{ url('/signup') }}">Create one</a>
        </p>
    </div>
</div>
</x-app-layout>
