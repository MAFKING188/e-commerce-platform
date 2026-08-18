@section('styles')
@vite('resources/css/partner.css')
@endsection

<nav class="pc-nav" aria-label="Admin console">
    <a href="{{ route('admin.dashboard') }}" class="pc-nav__tab {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="8" height="8" rx="2"/><rect x="13" y="3" width="8" height="8" rx="2"/><rect x="3" y="13" width="8" height="8" rx="2"/><rect x="13" y="13" width="8" height="8" rx="2"/></svg>
        Overview
    </a>
    <a href="{{ route('admin.orders.index') }}" class="pc-nav__tab {{ request()->routeIs('admin.orders.*') ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24"><path d="M6 2h12v20l-2-1.5L14 22l-2-1.5L10 22l-2-1.5L6 22V2z"/><path d="M9 7h6"/><path d="M9 11h6"/></svg>
        Orders
    </a>
    <a href="{{ route('admin.products.index') }}" class="pc-nav__tab {{ request()->routeIs('admin.products.*') ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24"><path d="M21 8l-9-5-9 5v8l9 5 9-5V8z"/><path d="M3 8l9 5 9-5"/><path d="M12 13v8"/></svg>
        Products
    </a>
    <a href="{{ route('admin.partners.index') }}" class="pc-nav__tab {{ request()->routeIs('admin.partners.*') ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/></svg>
        Partners
    </a>
    <a href="{{ route('admin.payouts.index') }}" class="pc-nav__tab {{ request()->routeIs('admin.payouts.*') ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v10"/><path d="M15.5 9.5a3.5 3.5 0 0 0-3.5-2.5c-2 0-3.5 1.2-3.5 2.5s1.5 2.5 3.5 2.5 3.5 1.2 3.5 2.5-1.5 2.5-3.5 2.5a3.5 3.5 0 0 1-3.5-2.5"/></svg>
        Payouts
    </a>
    <a href="{{ route('admin.reviews.index') }}" class="pc-nav__tab {{ request()->routeIs('admin.reviews.*') ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
        Reviews
    </a>
    <a href="{{ route('admin.categories.index') }}" class="pc-nav__tab {{ request()->routeIs('admin.categories.*') ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
        Categories
    </a>
    <a href="{{ route('admin.users.index') }}" class="pc-nav__tab {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24"><circle cx="9" cy="7" r="4"/><path d="M2 21v-2a4 4 0 0 1 4-4h6a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/></svg>
        Members
    </a>
    <a href="{{ route('admin.profile') }}" class="pc-nav__tab {{ request()->routeIs('admin.profile') ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/></svg>
        Profile
    </a>
</nav>