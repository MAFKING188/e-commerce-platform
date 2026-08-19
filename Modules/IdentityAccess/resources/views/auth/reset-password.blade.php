@section('title', 'Set New Password | LUWI')

<x-app-layout>
<div class="auth-wrapper">
    <div class="auth-card">
        <h1 class="auth-title">Set New Password</h1>
        <p class="auth-subtitle">Choose a new password for your account.</p>

        <form action="{{ route('password.store') }}" method="POST">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="form-group">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus class="form-input">
                @error('email')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="password" required placeholder="Min. 8 characters" class="form-input">
                @error('password')
                    <span class="form-error">{{ $message }}</span>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="password_confirmation" required class="form-input">
            </div>

            <button type="submit" class="auth-button">Update Password</button>
        </form>

        <p class="switch-auth">
            <a href="{{ url('/login') }}">Back to sign in</a>
        </p>
    </div>
</div>
</x-app-layout>