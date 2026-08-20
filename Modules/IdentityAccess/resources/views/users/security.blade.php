@section('title', 'Address & Security | LUWI')

<x-app-layout>
<x-profile-layout :user="$user" :active="'security'">

    <section class="profile-section">
        <h2 class="pc-card__title">Address</h2>
        <div class="profile-card">
            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Street Address</label>
                        <input type="text" name="line1" class="form-input" value="{{ $address->line1 ?? '' }}" placeholder="Luxury Street, 12">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Apartment / Suite (Optional)</label>
                        <input type="text" name="line2" class="form-input" value="{{ $address->line2 ?? '' }}" placeholder="Apt, floor, building...">
                    </div>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">City</label>
                        <input type="text" name="city" class="form-input" value="{{ $address->city ?? '' }}" placeholder="Milan">
                    </div>
                    <div class="form-group">
                        <label class="form-label">State / Region</label>
                        <input type="text" name="state" class="form-input" value="{{ $address->state ?? '' }}" placeholder="Lombardy">
                    </div>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Postal Code</label>
                        <input type="text" name="zip" class="form-input" value="{{ $address->zip ?? '' }}" placeholder="20121">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Country</label>
                        <input type="text" name="country" class="form-input" value="{{ $address->country ?? '' }}" placeholder="Italy">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary">Save Address</button>
            </form>
        </div>
    </section>

    <section class="profile-section">
        <h2 class="pc-card__title">Password</h2>
        <div class="profile-card">
            <form action="{{ route('profile.password') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-input" required>
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-input" minlength="8" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="password_confirmation" class="form-input" required>
                    </div>
                </div>
                                <div class="form-group">
                        <label class="form-label">Verification Code</label>
                        <input type="text" name="code" class="form-input" inputmode="numeric" maxlength="10" required>
                        <p class="form-hint">A code is sent to your email to confirm this change.</p>
                        <button type="button" class="btn btn-ghost" onclick="document.getElementById('send-password-code').submit()">Send Code</button>
                        @error('code') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                <button type="submit" class="btn btn-primary">Update Password</button>
            </form>
            <form id="send-password-code" action="{{ route('profile.send-password-code') }}" method="POST" class="inline-form">
                @csrf
            </form>
        </div>
    </section>

</x-profile-layout>
</x-app-layout>