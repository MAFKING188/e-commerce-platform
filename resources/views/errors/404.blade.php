<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page Not Found | SmartShop</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --brand-primary: #0f172a;
            --brand-accent: #3b82f6;
            --surface-100: #ffffff;
            --surface-200: #f8fafc;
            --text-900: #0f172a;
            --text-600: #475569;
            --border: #e2e8f0;
        }
        
        [data-theme="dark"] {
            --brand-primary: #f8fafc;
            --brand-accent: #60a5fa;
            --surface-100: #0f172a;
            --surface-200: #020617;
            --text-900: #f8fafc;
            --text-600: #94a3b8;
            --border: #1e293b;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; background: var(--surface-200); color: var(--text-900); line-height: 1.6; }
        
        .error-hero {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 4rem 2rem;
        }
        .error-code {
            font-size: clamp(6rem, 15vw, 12rem);
            font-weight: 800;
            color: var(--brand-accent);
            line-height: 1;
            margin-bottom: 1rem;
        }
        .error-message {
            font-size: 1.5rem;
            color: var(--text-600);
            margin-bottom: 2rem;
            max-width: 400px;
        }
        .error-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            font-size: 0.875rem;
            font-weight: 700;
            border-radius: 12px;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            border: none;
        }
        .btn-primary { background: var(--brand-primary); color: var(--surface-100); }
        .btn-primary:hover { background: var(--text-600); transform: translateY(-2px); }
        .btn-ghost { background: transparent; color: var(--text-600); border: 1px solid var(--border); }
        .btn-ghost:hover { background: var(--surface-300); color: var(--text-900); }
        
        script { display: none; }
    </style>
    
    <script>
        // Check for saved theme
        if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
</head>
<body>
    <section class="error-hero">
        <div>
            <div class="error-code">404</div>
            <p class="error-message">The page you're looking for doesn't exist or has been moved.</p>
            <div class="error-actions">
                <a href="/" class="btn btn-primary">Browse Collection</a>
                <a href="/" class="btn btn-ghost">Back to Home</a>
            </div>
        </div>
    </section>
</body>
</html>