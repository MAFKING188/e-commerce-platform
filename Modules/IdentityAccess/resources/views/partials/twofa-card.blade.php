<div class="profile-card">
    <h2 class="pc-card__title">Two-Factor Authentication</h2>

    @if ($user->twoFactorEnabled())
        <p class="twofa-status">Enabled via <span class="twofa-badge">Email Codes</span>
            since {{ $user->two_factor_confirmed_at->format('M j, Y') }}.</p>
        <form action="{{ route('profile.settings.twofa.disable') }}" method="POST" class="twofa-form">
            @csrf
            <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" name="password" class="form-input" required>
                @error('password') <p class="form-error">{{ $message }}</p> @enderror
            </div>
            <button type="submit" class="btn btn-ghost btn-danger">Disable Two-Factor Authentication</button>
        </form>
    @else
        <p class="twofa-status">Your account is protected by a password only. Add a second verification step to keep it secure.</p>

        @if (session('twofa.pending_type') === 'email')
            <div class="twofa-setup">
                <p class="twofa-hint">A 6-digit code was sent to <strong>{{ $user->email }}</strong>. It expires in 10 minutes.</p>
                <form action="{{ route('profile.settings.twofa.confirm') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Verification Code</label>
                        <input type="text" name="code" class="form-input" inputmode="numeric" maxlength="10" required autofocus>
                        @error('code') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                    <button type="submit" class="btn btn-primary">Confirm &amp; Enable</button>
                </form>
            </div>
        @else
            <form action="{{ route('profile.settings.twofa.enable-email') }}" method="POST" class="twofa-option">
                @csrf
                <p class="twofa-option-title">Email Codes</p>
                <p class="twofa-option-desc">Receive a 6-digit code by email at each sign-in.</p>
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="password" class="form-input" required>
                </div>
                <button type="submit" class="btn btn-primary">Enable Email Codes</button>
            </form>
        @endif

        @if ($errors->has('twofa'))
            <p class="form-error">{{ $errors->first('twofa') }}</p>
        @endif
    @endif
</div>

<div class="profile-card">
    <h2 class="pc-card__title">Google Account</h2>
    @if ($user->google_id)
        <p class="twofa-status">
            Connected as
            @if ($user->avatar) <img src="{{ $user->avatar }}" alt="" class="twofa-google-avatar"> @endif
            <strong>{{ $user->email }}</strong>.
        </p>
    @else
        <p class="twofa-status">Link your Google account to sign in without a password.</p>
        <a href="{{ route('auth.google.redirect') }}" class="btn btn-primary">Connect Google Account</a>
    @endif
</div>