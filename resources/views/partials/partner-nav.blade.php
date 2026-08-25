@section('styles')
@vite('resources/css/partner.css')
@endsection

<nav class="pc-nav" aria-label="Partner console">
    <a href="{{ route('partner.dashboard') }}" class="pc-nav__tab {{ request()->routeIs('partner.dashboard') ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="8" height="8" rx="2"/><rect x="13" y="3" width="8" height="8" rx="2"/><rect x="3" y="13" width="8" height="8" rx="2"/><rect x="13" y="13" width="8" height="8" rx="2"/></svg>
        Dashboard
    </a>
    <a href="{{ route('partner.inventory.index') }}" class="pc-nav__tab {{ request()->routeIs('partner.inventory.*') ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24"><path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5"/><path d="M12 13v8"/></svg>
        Inventory
    </a>
    <a href="{{ route('partner.orders.index') }}" class="pc-nav__tab {{ request()->routeIs('partner.orders.*') ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24"><path d="M6 2h12v20l-2-1.5L14 22l-2-1.5L10 22l-2-1.5L6 22V2z"/><path d="M9 7h6"/><path d="M9 11h6"/></svg>
        Orders
    </a>
    <a href="{{ route('partner.payouts.index') }}" class="pc-nav__tab {{ request()->routeIs('partner.payouts.*') ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v10"/><path d="M15.5 9.5a3.5 3.5 0 0 0-3.5-2.5c-2 0-3.5 1.2-3.5 2.5s1.5 2.5 3.5 2.5 3.5 1.2 3.5 2.5-1.5 2.5-3.5 2.5a3.5 3.5 0 0 1-3.5-2.5"/></svg>
        Earnings
    </a>
    <a href="{{ route('partner.bank-details.index') }}" class="pc-nav__tab {{ request()->routeIs('partner.bank-details*') ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2z"/><path d="M12 6v6l4 4"/></svg>
        Bank Details
    </a>
    <a href="{{ route('partner.profile.edit') }}" class="pc-nav__tab {{ request()->routeIs('partner.profile.edit') ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/></svg>
        Profile
    </a>
    <a href="{{ route('partner.email.compose') }}" class="pc-nav__tab {{ request()->routeIs('partner.email.*') ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
        Email Center
    </a>
</nav>

@include('partials.partner.confirm-modal')