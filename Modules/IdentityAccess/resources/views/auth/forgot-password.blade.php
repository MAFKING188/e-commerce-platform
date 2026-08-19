@section('title', 'Reset Password | LUWI')

<x-app-layout>
<div class="auth-wrapper">
    <div class="auth-card">
        <h1 class="auth-title">Reset Password</h1>
        <p class="auth-subtitle">Enter your account email and we'll send you a secure reset link.</p>

        @if (session('status'))
            <div class="auth-alert">{{ session('status') }}</div>
        @endif

        <form action="{{ route('password.email') }}" method="POST">
            @csrf
            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus class="form-input">
                @error('email')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="auth-button">Send Reset Link</button>
        </form>

        <p class="switch-auth">
            Remembered it? <a href="{{ url('/login') }}">Sign in here</a>
        </p>
    </div>
</div>
</x-app-layout>