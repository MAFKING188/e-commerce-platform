@props([
    'user',
    'stats' => [],
    'subnav' => [
        ['id' => 'overview', 'label' => 'Overview'],
        ['id' => 'activity', 'label' => 'Orders / Activity'],
        ['id' => 'security', 'label' => 'Address & Security'],
        ['id' => 'settings', 'label' => 'Settings'],
    ],
    'identity' => false,
])

<div class="profile-shell">
    <header class="profile-header">
        <div class="profile-avatar">
            @if ($user->avatarUrl())
                <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="profile-avatar__img">
            @else
                <span class="profile-avatar__letter">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
            @endif
            <form action="{{ route('profile.avatar') }}" method="POST" enctype="multipart/form-data" class="profile-avatar__upload">
                @csrf
                <label for="avatar-input" class="profile-avatar__btn" title="Upload photo">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M17 8l-5-5-5 5"/><path d="M12 3v12"/></svg>
                </label>
                <input type="file" id="avatar-input" name="avatar" accept="image/jpeg,image/png,image/webp" class="visually-hidden" onchange="this.form.submit()">
            </form>
        </div>
        <div class="profile-id">
            <h1 class="profile-id__name">{{ $user->name }}</h1>
            <div class="profile-id__meta">
                <span class="profile-badge">{{ ucfirst($user->role) }}</span>
                @if ($user->isVerifiedMember())
                    <span class="profile-badge profile-badge--verified">Verified Member</span>
                @endif
                <span class="profile-badge profile-badge--tier">{{ $user->memberTier() }}</span>
                <span class="profile-id__since">Member since {{ $user->created_at->format('M Y') }}</span>
            </div>
        </div>
    </header>

    @if (count($stats))
        <div class="profile-stats">
            @foreach ($stats as $label => $value)
                <div class="profile-stat">
                    <span class="profile-stat__value">{{ $value }}</span>
                    <span class="profile-stat__label">{{ $label }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <nav class="profile-subnav" aria-label="Profile sections">
        @foreach ($subnav as $item)
            <a href="{{ $item['href'] }}" class="profile-subnav__link {{ $item['active'] ? 'is-active' : '' }}">{{ $item['label'] }}</a>
        @endforeach
    </nav>

    @if ($identity)
        <section class="profile-section">
            <h2 class="pc-card__title">Identity</h2>
            <div class="profile-card profile-identity">
                <div class="profile-identity__row">
                    <span class="profile-identity__label">Email</span>
                    <span class="profile-identity__value">{{ $user->email }}</span>
                </div>
                <div class="profile-identity__row">
                    <span class="profile-identity__label">Phone</span>
                    <span class="profile-identity__value">{{ $user->phone ?: 'Not set — add it in Settings' }}</span>
                </div>
                <div class="profile-identity__row">
                    <span class="profile-identity__label">Account Status</span>
                    <span class="profile-identity__value">
                        @php($chip = $user->statusChip())
                        <span class="profile-badge profile-badge--status-{{ $chip['tone'] }}">{{ $chip['label'] }}</span>
                    </span>
                </div>
                <div class="profile-identity__row">
                    <span class="profile-identity__label">Member Number</span>
                    <span class="profile-identity__value">{{ $user->memberNumber() }}</span>
                </div>
                <div class="profile-identity__row">
                    <span class="profile-identity__label">Member Since</span>
                    <span class="profile-identity__value">{{ $user->created_at->format('F Y') }}</span>
                </div>
            </div>
        </section>
    @endif

    {{ $slot }}
</div>