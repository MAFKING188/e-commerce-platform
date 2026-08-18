@php
$tabs = match ($user->role) {
    'admin' => [
        ['href' => route('admin.profile'), 'label' => 'Overview', 'active' => $active === 'overview'],
        ['href' => route('admin.dashboard'), 'label' => 'Command Center', 'active' => $active === 'dashboard'],
        ['href' => route('profile.security'), 'label' => 'Address & Security', 'active' => $active === 'security'],
        ['href' => route('profile.settings'), 'label' => 'Settings', 'active' => $active === 'settings'],
    ],
    'partner' => [
        ['href' => route('partner.profile.show'), 'label' => 'Overview', 'active' => $active === 'overview'],
        ['href' => route('partner.orders.index'), 'label' => 'My Orders', 'active' => $active === 'orders'],
        ['href' => route('profile.security'), 'label' => 'Address & Security', 'active' => $active === 'security'],
        ['href' => route('partner.profile.edit'), 'label' => 'Public Profile', 'active' => $active === 'settings'],
    ],
    default => [
        ['href' => route('profile'), 'label' => 'Overview', 'active' => $active === 'overview'],
        ['href' => route('orders.index'), 'label' => 'Orders', 'active' => $active === 'orders'],
        ['href' => route('profile.security'), 'label' => 'Address & Security', 'active' => $active === 'security'],
        ['href' => route('profile.settings'), 'label' => 'Settings', 'active' => $active === 'settings'],
    ],
};
@endphp

<nav class="profile-subnav" aria-label="Profile sections">
    @foreach ($tabs as $tab)
        <a href="{{ $tab['href'] }}" class="profile-subnav__link {{ $tab['active'] ? 'is-active' : '' }}">{{ $tab['label'] }}</a>
    @endforeach
</nav>
