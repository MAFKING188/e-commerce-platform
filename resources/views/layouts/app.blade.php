<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SmartShop | Premium Collection')</title>
    
    <!-- Google Fonts: Geist or Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --brand-primary: #0f172a;
            --brand-accent: #3b82f6;
            --brand-accent-soft: rgba(59, 130, 246, 0.1);
            --surface-100: #ffffff;
            --surface-200: #f8fafc;
            --surface-300: #f1f5f9;
            --text-900: #0f172a;
            --text-600: #475569;
            --text-400: #94a3b8;
            --border: #e2e8f0;
            --success: #10b981;
            --error: #ef4444;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 20px;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 25px 50px -12px rgb(0 0 0 / 0.15);
            --nav-bg: rgba(255, 255, 255, 0.8);
        }

        [data-theme="dark"] {
            --brand-primary: #f8fafc;
            --brand-accent: #60a5fa;
            --brand-accent-soft: rgba(96, 165, 250, 0.1);
            --surface-100: #0f172a;
            --surface-200: #020617;
            --surface-300: #1e293b;
            --text-900: #f8fafc;
            --text-600: #94a3b8;
            --text-400: #64748b;
            --border: #1e293b;
            --nav-bg: rgba(15, 23, 42, 0.8);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            transition: background-color 0.3s ease, border-color 0.3s ease, color 0.3s ease;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--surface-200);
            color: var(--text-900);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 0 2rem;
        }

        @media (max-width: 640px) {
            .container {
                padding: 0 1.25rem;
            }
        }

        /* PREMIUM NAVIGATION */
        nav {
            background: var(--nav-bg);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            padding: 1.25rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        nav .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-900);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            letter-spacing: -0.05em;
        }

        .logo::before {
            content: '';
            width: 32px;
            height: 32px;
            background: var(--brand-accent);
            border-radius: 8px;
            display: inline-block;
        }

        .nav-links {
            display: flex;
            gap: 2.5rem;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--text-600);
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .nav-links a:hover {
            color: var(--brand-accent);
            transform: translateY(-1px);
        }

        .nav-auth {
            display: flex;
            gap: 0.75rem;
            align-items: center;
        }

        /* MOBILE MENU TOGGLE */
        .mobile-menu-btn {
            display: none;
            background: transparent;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            z-index: 1100;
        }

        .mobile-menu-btn span {
            display: block;
            width: 24px;
            height: 2px;
            background: var(--text-900);
            margin: 5px 0;
            transition: 0.3s;
        }

        @media (max-width: 1024px) {
            .mobile-menu-btn {
                display: block;
            }

            .nav-links, .nav-auth {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100vh;
                background: var(--surface-100);
                flex-direction: column;
                justify-content: center;
                align-items: center;
                gap: 2rem;
                z-index: 1050;
                opacity: 0;
                visibility: hidden;
                transition: all 0.3s ease;
            }

            .nav-auth {
                height: auto;
                top: 70%;
                background: transparent;
                pointer-events: none;
            }

            .nav-links.active {
                display: flex;
                opacity: 1;
                visibility: visible;
            }

            .nav-auth.active {
                display: flex;
                opacity: 1;
                visibility: visible;
                pointer-events: auto;
            }

            .mobile-menu-btn.active span:nth-child(1) {
                transform: rotate(-45deg) translate(-5px, 6px);
            }
            .mobile-menu-btn.active span:nth-child(2) {
                opacity: 0;
            }
            .mobile-menu-btn.active span:nth-child(3) {
                transform: rotate(45deg) translate(-5px, -6px);
            }
        }

        /* THEME TOGGLE */
        .theme-toggle {
            background: var(--surface-300);
            border: 1px solid var(--border);
            color: var(--text-900);
            padding: 0.5rem;
            border-radius: 50%;
            cursor: pointer;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }

        /* BUTTONS: TIER 2 UPGRADE */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 700;
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            border: none;
            outline: none;
        }

        .btn-primary {
            background: var(--brand-primary);
            color: var(--surface-100);
            box-shadow: var(--shadow-sm);
        }

        .btn-primary:hover {
            background: var(--text-600);
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-accent {
            background: var(--brand-accent);
            color: white;
        }

        .btn-accent:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3);
        }

        .btn-ghost {
            background: transparent;
            color: var(--text-600);
            border: 1px solid var(--border);
        }

        .btn-ghost:hover {
            background: var(--surface-300);
            color: var(--text-900);
        }

        /* ALERTS */
        .alert {
            padding: 1.25rem;
            border-radius: var(--radius-md);
            margin-bottom: 2rem;
            font-weight: 600;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            animation: slideDown 0.4s ease-out;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success { background: var(--brand-accent-soft); color: var(--brand-accent); border: 1px solid var(--brand-accent); }
        .alert-error { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

        main {
            padding: 4rem 0;
            min-height: 80vh;
        }

        footer {
            background: var(--surface-100);
            padding: 5rem 0;
            color: var(--text-900);
            border-top: 1px solid var(--border);
            margin-top: 4rem;
        }

        .footer-content {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 4rem;
            text-align: left;
        }

        @media (max-width: 768px) {
            .footer-content {
                grid-template-columns: 1fr;
                gap: 2.5rem;
                text-align: center;
            }

            .footer-brand p {
                margin: 0 auto;
            }
        }

        .footer-brand h2 { font-size: 1.5rem; margin-bottom: 1rem; }
        .footer-brand p { color: var(--text-400); max-width: 300px; }
        .footer-links h4 { margin-bottom: 1.5rem; font-size: 1rem; text-transform: uppercase; letter-spacing: 0.1em; }
        .footer-links ul { list-style: none; }
        .footer-links li { margin-bottom: 0.75rem; }
        .footer-links a { color: var(--text-400); text-decoration: none; transition: 0.2s; }
        .footer-links a:hover { color: var(--text-900); }

        .footer-bottom {
            margin-top: 4rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border);
            color: var(--text-400);
            font-size: 0.875rem;
        }
    </style>
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

        <div class="nav-links" id="nav-links">
            <a href="{{ route('home') }}">Discovery</a>
            <a href="{{ route('shop') }}">Collection</a>
            <a href="{{ route('about') }}">Story</a>
            <a href="{{ route('contact') }}">Support</a>
        </div>

        <div class="nav-auth" id="nav-auth">
            <button onclick="toggleTheme()" class="theme-toggle" id="theme-btn">
                🌙
            </button>
            @auth
                <a href="{{ route('profile') }}" class="btn btn-ghost">Profile</a>
                @if(auth()->user()->role === 'admin')
                    <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost" style="color: var(--brand-accent);">Admin</a>
                @endif
                <a href="{{ route('cart.index') }}" class="btn btn-ghost">Cart</a>
                <a href="{{ route('orders.index') }}" class="btn btn-ghost">Orders</a>
                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn btn-primary" style="background: #ef4444;">Sign Out</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="btn btn-ghost">Member Login</a>
                <a href="{{ route('signup') }}" class="btn btn-primary">Join Now</a>
            @endauth
        </div>
    </div>
</nav>

<main class="container">
    @yield('content')
</main>

    <style>
        /* PHASE 11: LUXURY TOAST NOTIFICATIONS */
        .toast-container {
            position: fixed;
            top: 2rem;
            right: 2rem;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            pointer-events: none;
        }

        .toast {
            background: var(--surface-100);
            border: 1px solid var(--border);
            padding: 1.25rem 2rem;
            border-radius: 1rem;
            box-shadow: var(--shadow-lg);
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 300px;
            pointer-events: auto;
            animation: toastSlideIn 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes toastSlideIn {
            from { transform: translateX(100%) scale(0.9); opacity: 0; }
            to { transform: translateX(0) scale(1); opacity: 1; }
        }

        .toast.hiding {
            animation: toastSlideOut 0.5s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes toastSlideOut {
            from { transform: translateX(0) scale(1); opacity: 1; }
            to { transform: translateX(100%) scale(0.9); opacity: 0; }
        }

        .toast-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }

        .toast-success .toast-icon { background: #dcfce7; color: #166534; }
        .toast-error .toast-icon { background: #fee2e2; color: #991b1b; }

        .toast-content { font-weight: 600; font-size: 0.9rem; color: var(--text-900); }
    </style>

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
                    <li><a href="https://www.paypal.com/ncp/payment/Q3SN7Q7K8YDEU" target="_blank" style="color: var(--brand-accent); font-weight: 700;">Support This Project</a></li>
                    <li><a href="#">Shipping</a></li>
                    <li><a href="#">Returns</a></li>
                    <li><a href="#">Privacy</a></li>
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
        const navLinks = document.getElementById('nav-links');
        const navAuth = document.getElementById('nav-auth');
        const btn = document.querySelector('.mobile-menu-btn');
        
        navLinks.classList.toggle('active');
        navAuth.classList.toggle('active');
        btn.classList.toggle('active');
        
        // Prevent scroll when menu is open
        if (navLinks.classList.contains('active')) {
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
        const btn = document.getElementById('theme-btn');
        if(btn) btn.innerHTML = theme === 'dark' ? '☀️' : '🌙';
    }

    function toggleWishlist(btn, productId) {
        btn.classList.toggle('active');
        if (btn.classList.contains('active')) {
            showToast('Added to your Archive Collection', 'success');
        } else {
            showToast('Removed from Archive', 'error');
        }
    }

    updateToggleBtn();
</script>
</body>
</html>
