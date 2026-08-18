<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SmartShop | Premium Collection')</title>
    
    <!-- SEO & OpenGraph -->
    <meta name="description" content="@yield('description', 'SmartShop: High-Fidelity E-Commerce Ecosystem. Curating exceptional products with a focus on quality and timeless design.')">
    <meta property="og:title" content="@yield('title', 'SmartShop | Premium Collection')">
    <meta property="og:description" content="@yield('description', 'SmartShop: High-Fidelity E-Commerce Ecosystem. Curating exceptional products with a focus on quality and timeless design.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="@yield('og_image', asset('favicon.ico'))">
    <meta name="twitter:card" content="summary_large_image">
    
    <!-- Google Fonts: Geist or Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(array_merge(['resources/css/app.css'], $moduleAssets))
    @yield('styles')
    <script>
        // Check for saved theme or default to light
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        } else {
            document.documentElement.setAttribute('data-theme', 'light');
        }
    </script>
</head>
<body>

<nav>
    <div class="container">
        <a href="{{ route('home') }}" class="logo">SmartShop</a>
        
        <button class="mobile-menu-btn" onclick="toggleMobileMenu()" aria-label="Toggle Menu">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="nav-menu" id="nav-menu">
            <div class="nav-links">
                <a href="{{ route('home') }}">Discovery</a>
                <a href="{{ route('shop') }}">Collection</a>
                <a href="{{ route('about') }}">Story</a>
                <a href="{{ route('contact') }}">Support</a>
            </div>

            <div class="nav-auth">
                <form action="{{ url()->current() }}" method="GET" id="currency-form" class="form-inline">
                    @foreach(request()->except('currency') as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <select name="currency" onchange="this.form.submit()" class="theme-toggle currency-select">
                        @foreach(config('currency.supported') as $code => $details)
                            <option value="{{ $code }}" {{ \App\Services\CurrencyService::getCurrent() === $code ? 'selected' : '' }}>
                                {{ $code }} ({{ $details['symbol'] }})
                            </option>
                        @endforeach
                    </select>
                </form>
                <button onclick="toggleTheme()" class="theme-toggle" id="theme-btn" aria-label="Toggle dark mode">
                    <svg id="theme-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
                </button>
                @auth
                    <div class="user-dropdown">
                        <button class="btn btn-ghost">
                            {{ auth()->user()->name }} <span class="dropdown-caret">▼</span>
                        </button>
                        <div class="dropdown-content">
                            <a href="{{ route('profile') }}">My Profile</a>
                            <a href="{{ route('profile.wishlist') }}">My Archive</a>
                            <a href="{{ route('orders.index') }}">Order History</a>
                            <a href="{{ route('cart.index') }}">Shopping Bag</a>
                            
                            @if(auth()->user()->role === 'admin')
                                <div class="dropdown-divider"></div>
                                <a href="{{ route('admin.dashboard') }}" class="dropdown-accent">Admin Command Center</a>
                            @endif
                            
                            @if(auth()->user()->role === 'partner')
                                <div class="dropdown-divider"></div>
                                <a href="{{ route('partner.dashboard') }}" class="dropdown-accent">Artisan Portal</a>
                            @endif
                            
                            <div class="dropdown-divider"></div>
                            <form action="{{ route('logout') }}" method="POST" class="form-inline">
                                @csrf
                                <button type="submit" class="logout-btn">Sign Out</button>
                            </form>
                        </div>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="btn btn-ghost">Member Login</a>
                    <a href="{{ route('signup') }}" class="btn btn-primary">Join Now</a>
                @endauth
            </div>
        </div>
    </div>
</nav>

<main class="container app-main">
    @yield('content')
</main>

<div class="toast-container" id="toast-container"></div>

<script>
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        const icon = type === 'success' ? '✓' : '✕';
        
        toast.innerHTML = `
            <div class="toast-icon">${icon}</div>
            <div class="toast-content">${message}</div>
        `;
        
        container.appendChild(toast);

        // Auto-remove after 4 seconds
        setTimeout(() => {
            toast.classList.add('hiding');
            setTimeout(() => toast.remove(), 500);
        }, 4000);
    }

    // Capture Laravel Session Messages
    @if(session('status') || session('success'))
        showToast("{{ session('status') ?? session('success') }}", 'success');
    @endif

    @if($errors->any())
        @foreach ($errors->all() as $error)
            showToast("{{ $error }}", 'error');
        @endforeach
    @endif
</script>

@yield('scripts')

<footer>
    <div class="container">
        <div class="footer-content">
            <div class="footer-brand">
                <h2>SmartShop</h2>
                <p>Curating exceptional products with a focus on quality, sustainability, and timeless design.</p>
            </div>
            <div class="footer-links">
                <h4>Explore</h4>
                <ul>
                    <li><a href="{{ route('shop') }}">All Products</a></li>
                    <li><a href="#">New Arrivals</a></li>
                    <li><a href="#">Featured</a></li>
                </ul>
            </div>
            <div class="footer-links">
                <h4>Support</h4>
                <ul>
                    <li><a href="https://www.paypal.com/ncp/payment/Q3SN7Q7K8YDEU" target="_blank" class="footer-cta">Support This Project</a></li>
                    <li><a href="{{ route('shipping') }}">Shipping</a></li>
                    <li><a href="{{ route('returns') }}">Returns</a></li>
                    <li><a href="{{ route('privacy') }}">Privacy</a></li>
                    <li><a href="{{ route('terms') }}">Terms</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; {{ date('Y') }} SmartShop Premium E-Commerce Platform. All rights reserved.</p>
        </div>
    </div>
</footer>

<script>
    function toggleMobileMenu() {
        const navMenu = document.getElementById('nav-menu');
        const btn = document.querySelector('.mobile-menu-btn');
        
        navMenu.classList.toggle('active');
        btn.classList.toggle('active');
        
        // Prevent scroll when menu is open
        if (navMenu.classList.contains('active')) {
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = 'auto';
        }
    }

    function toggleTheme() {
        const currentTheme = document.documentElement.getAttribute('data-theme');
        const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
        
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
        updateToggleBtn();
    }

    function updateToggleBtn() {
        const theme = document.documentElement.getAttribute('data-theme');
        const icon = document.getElementById('theme-icon');
        if(icon) icon.innerHTML = theme === 'dark'
            ? '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/>'
            : '<path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/>';
    }

    document.addEventListener('submit', function (e) {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        const message = form.getAttribute('data-confirm');
        if (message && !window.confirm(message)) {
            e.preventDefault();
        }
    });

    function toggleWishlist(btn, productId) {
        @guest
            showToast('Please login to save pieces to your Archive.', 'error');
            return;
        @endguest

        fetch("{{ url('/wishlist/toggle') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': "{{ csrf_token() }}"
            },
            body: JSON.stringify({ product_id: productId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                btn.classList.toggle('active');
                showToast(data.message, 'success');
            } else {
                showToast(data.message || 'Unable to update Archive.', 'error');
            }
        })
        .catch(error => {
            console.error('Wishlist Error:', error);
            showToast('Network error while saving to Archive.', 'error');
        });
    }

    updateToggleBtn();
</script>
</body>
</html>
