@section('title', 'Verify Your Email | LUWI')

<x-app-layout>
<div class="auth-wrapper">
    <div class="auth-card auth-card--narrow">
        <h1 class="auth-title">Verify Your Email</h1>
        <p class="auth-subtitle">Enter the code we sent to <strong>{{ $user->email }}</strong> to activate your account.</p>

        <form method="POST" action="{{ route('verify-email.post') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Verification Code</label>
                <input type="text" name="code" class="form-input" inputmode="numeric" maxlength="10" required autofocus>
                @error('code') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            @if (session('status'))
                <p class="form-success">{{ session('status') }}</p>
            @endif

            <button type="submit" class="auth-button">Verify Email</button>
        </form>

        <form method="POST" action="{{ route('verify-email.resend') }}" class="twofa-form">
            @csrf
            <button type="submit" class="btn btn-ghost">Resend Code</button>
        </form>
    </div>
</div>
</x-app-layout>