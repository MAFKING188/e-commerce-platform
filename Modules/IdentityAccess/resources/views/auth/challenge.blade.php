@section('title', 'Verify Your Identity | LUWI')

<x-app-layout>
<div class="auth-wrapper">
    <div class="auth-card auth-card--narrow">
        <h1 class="auth-title">Verify Your Identity</h1>
        <p class="auth-subtitle">Enter the code to complete your sign-in.</p>

        <p class="twofa-hint">A 6-digit code was sent to <strong>{{ $user->email }}</strong>. It expires in 10 minutes.</p>
        <form method="POST" action="{{ route('2fa.resend') }}" class="twofa-form">
            @csrf
            <button type="submit" class="btn btn-ghost">Resend Code</button>
        </form>

        <form method="POST" action="{{ route('2fa.verify') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Verification Code</label>
                <input type="text" name="code" class="form-input" inputmode="numeric" maxlength="10" required autofocus>
                @error('code') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            @if (session('status'))
                <p class="form-success">{{ session('status') }}</p>
            @endif

            <button type="submit" class="auth-button">Verify &amp; Sign In</button>
        </form>

        <p class="switch-auth">
            <a href="{{ url('/logout') }}">Not you? Sign out</a>
        </p>
    </div>
</div>
</x-app-layout>