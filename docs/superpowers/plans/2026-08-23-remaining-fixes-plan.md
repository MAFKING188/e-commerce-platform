# Remaining Feedback Fixes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implement 7 production-level fixes for credibility, accessibility, and UX polish (excluding checkout UX).

**Architecture:** View/controller updates only. No database migrations, no new services. All changes follow existing Laravel/Blade patterns in the codebase.

**Tech Stack:** Laravel 11, Blade, Tailwind CSS (via Vite), Vanilla JS

## Global Constraints

- Laravel 11 + PHP 8.3+
- Existing test suite: 200 tests must pass
- Dark/light mode via `data-theme` attribute
- Toast system: `showToast(message, type)` in app-layout
- Cart count badge updates via `.cart-count` element
- All views extend `<x-app-layout>` component
- Images self-hosted in `storage/app/public/products/curated/`

---

### Task 1: Contact Page - Replace Email & Remove Marrakech

**Files:**
- Modify: `Modules/CatalogDelivery/resources/views/contact.blade.php:10-23`

**Interfaces:**
- Produces: Updated contact info display

- [ ] **Step 1: Update contact info section**

```php
<!-- Replace lines 10-23 in contact.blade.php -->
<div class="contact-methods">
    <div class="method-item">
        <h4>General Inquiries</h4>
        <p>support@smartshop-luwi.tech</p>
    </div>
    <div class="method-item">
        <h4>Client Support</h4>
        <p>+212 (0) 6 24 54 84 29</p>
    </div>
</div>
```

- [ ] **Step 2: Verify view renders**

Run: `php artisan test --filter=ContactFormTest`
Expected: All contact tests pass

- [ ] **Step 3: Commit**

```bash
git add Modules/CatalogDelivery/resources/views/contact.blade.php
git commit -m "fix: update contact page with professional email, remove Marrakech"
```

---

### Task 2: Footer - Remove PayPal Donation Link

**Files:**
- Modify: `resources/views/components/app-layout.blade.php:172-180`

**Interfaces:**
- Produces: Clean footer without PayPal link

- [ ] **Step 1: Remove PayPal link from footer**

```php
<!-- In app-layout.blade.php, replace lines 172-180 -->
<div class="footer-links">
    <h4>Support</h4>
    <ul>
        <li><a href="{{ route('shipping') }}">Shipping</a></li>
        <li><a href="{{ route('returns') }}">Returns</a></li>
        <li><a href="{{ route('privacy') }}">Privacy</a></li>
        <li><a href="{{ route('terms') }}">Terms</a></li>
    </ul>
</div>
```

- [ ] **Step 2: Verify footer renders**

Run: `php artisan test --filter=LegalPagesTest`
Expected: All legal pages tests pass

- [ ] **Step 3: Commit**

```bash
git add resources/views/components/app-layout.blade.php
git commit -m "fix: remove PayPal donation link from footer"
```

---

### Task 3: Create Branded 404 Page

**Files:**
- Create: `resources/views/errors/404.blade.php`

**Interfaces:**
- Consumes: `<x-app-layout>` component, route helpers
- Produces: Branded 404 page

- [ ] **Step 1: Create 404 blade view**

```php
<!-- resources/views/errors/404.blade.php -->
@extends('components.app-layout')

@section('title', 'Page Not Found | SmartShop')

@section('styles')
<style>
    .error-hero {
        min-height: 60vh;
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
</style>
@endsection

<x-app-layout>
    <section class="error-hero">
        <div>
            <div class="error-code">404</div>
            <p class="error-message">The page you're looking for doesn't exist or has been moved.</p>
            <div class="error-actions">
                <a href="{{ route('shop') }}" class="btn btn-primary">Browse Collection</a>
                <a href="{{ route('home') }}" class="btn btn-ghost">Back to Home</a>
            </div>
        </div>
    </section>
</x-app-layout>
```

- [ ] **Step 2: Test 404 page**

Run: Visit `http://localhost:8001/nonexistent-page` (or use test)
Expected: Branded 404 page renders with buttons

- [ ] **Step 3: Commit**

```bash
git add resources/views/errors/404.blade.php
git commit -m "feat: add branded 404 error page"
```

---

### Task 4: Fix Mobile Header Z-Index Overlap

**Files:**
- Modify: `resources/css/app.css` (header styles around line 92-101, main padding around line 441-444)

**Interfaces:**
- Produces: Fixed header stacking, proper content spacing

- [ ] **Step 1: Update header z-index and add scroll-padding**

```css
/* In app.css, find nav styles (around line 92-101) */
nav {
    background: var(--nav-bg);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border);
    padding: 1.25rem 0;
    position: sticky;
    top: 0;
    z-index: 1000; /* ensure highest */
}

/* Add after main styles (around line 444) */
html {
    scroll-padding-top: 80px; /* accounts for sticky header height */
}

main {
    padding: 4rem 0;
    min-height: 80vh;
}
```

- [ ] **Step 2: Build CSS and verify**

Run: `npm run build`
Expected: Build succeeds

Run: `php artisan test --filter=CollectionPageTest`
Expected: All collection tests pass

- [ ] **Step 3: Commit**

```bash
git add resources/css/app.css
git commit -m "fix: mobile header z-index overlap with scroll-padding"
```

---

### Task 5: Login - Enable "Remember Me" Functionality

**Files:**
- Modify: `Modules/IdentityAccess/app/Http/Controllers/Auth/LoginController.php` (find actual path)

**Interfaces:**
- Consumes: `$request->remember` boolean
- Produces: Persistent login session

- [ ] **Step 1: Find LoginController**

Run: `find Modules/IdentityAccess -name "*LoginController*"`
Expected: Locate controller file

- [ ] **Step 2: Update LoginController to use remember token**

```php
// In LoginController@store or authenticate method
public function store(Request $request)
{
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $remember = $request->boolean('remember');

    if (Auth::attempt($credentials, $remember)) {
        $request->session()->regenerate();
        return redirect()->intended(route('home'));
    }

    return back()->withErrors([
        'email' => 'The provided credentials do not match our records.',
    ]);
}
```

- [ ] **Step 3: Test login with remember me**

Run: `php artisan test --filter=RegistrationTest`
Expected: Registration/login tests pass

- [ ] **Step 4: Commit**

```bash
git add Modules/IdentityAccess/app/Http/Controllers/Auth/LoginController.php
git commit -m "fix: enable remember me functionality in login"
```

---

### Task 6: Review Submission - Toast + Pending Badge

**Files:**
- Modify: `Modules/CatalogDelivery/app/Http/Controllers/ReviewController.php:20-38`
- Modify: `Modules/CatalogDelivery/resources/views/product.blade.php:100-127`
- Modify: `Modules/CatalogDelivery/app/Models/Review.php` (add scope)

**Interfaces:**
- ReviewController@store: Returns JSON for AJAX, redirect with flash for traditional
- Review model: `approvedForUser($userId)` scope
- Product view: Shows approved + current user's pending reviews

- [ ] **Step 1: Add scope to Review model**

```php
// In Modules/CatalogDelivery/app/Models/Review.php, add after line 12
public function scopeApprovedForUser($query, $userId = null)
{
    $query->where('status', 'approved');
    
    if ($userId) {
        $query->orWhere(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->where('status', 'pending');
        });
    }
    
    return $query;
}
```

- [ ] **Step 2: Update ReviewController to return JSON + toast**

```php
// In Modules/CatalogDelivery/app/Http/Controllers/ReviewController.php, replace store method
public function store(Request $request)
{
    $request->validate([
        'product_id' => 'required|exists:products,id',
        'rating' => 'required|integer|min:1|max:5',
        'comment' => 'required|string|min:3|max:1000'
    ]);

    $review = Review::create([
        'user_id' => auth()->id(),
        'product_id' => $request->product_id,
        'rating' => $request->rating,
        'comment' => $request->comment,
        'status' => 'pending',
    ]);

    $message = 'Review submitted for moderation.';

    if ($request->expectsJson()) {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'review' => $review->load('user'),
        ]);
    }

    return redirect()->back()->with('success', $message);
}
```

- [ ] **Step 3: Update product view to show pending reviews for author**

```php
// In Modules/CatalogDelivery/resources/views/product.blade.php, replace lines 100-127
@if($product->reviews->isEmpty())
    <div class="product-empty-reviews">
        <p class="product-empty-text">No testimonials recorded for this piece yet.</p>
    </div>
@else
    <div class="product-reviews-grid">
        @foreach($product->reviews as $review)
            @php $isAuthor = auth()->check() && auth()->id() === $review->user_id; @endphp
            @if($review->status === 'approved' || $isAuthor)
                <div class="product-review-card {{ $review->status === 'pending' ? 'pending' : '' }}">
                    <div class="product-stars">
                        @for($i = 0; $i < 5; $i++)
                            <svg width="16" height="16" fill="{{ $i < $review->rating ? 'currentColor' : 'none' }}" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.518 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-.363 1.118l1.518-4.674c-.783.57-1.838-.197-1.538-1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        @endfor
                    </div>
                    <p class="product-review-text">"{{ $review->comment }}"</p>
                    <div class="product-reviewer">
                        <div class="product-avatar">
                            @if ($review->user->avatarUrl())
                                <img src="{{ $review->user->avatarUrl() }}" alt="{{ $review->user->name }}" class="product-avatar__img" loading="lazy" decoding="async">
                            @else
                                {{ substr($review->user->name, 0, 1) }}
                            @endif
                        </div>
                        <span class="product-reviewer-name">{{ $review->user->name }}</span>
                    </div>
                    @if($review->status === 'pending')
                        <span class="review-pending-badge">Awaiting moderation</span>
                    @endif
                </div>
            @endif
        @endforeach
    </div>
@endif
```

- [ ] **Step 4: Add pending badge styles**

```css
/* In resources/css/app.css, add after review styles (around line 1200) */
.product-review-card.pending {
    border-color: var(--brand-accent);
    background: var(--brand-accent-soft);
}

.review-pending-badge {
    display: inline-block;
    margin-top: 0.75rem;
    padding: 0.25rem 0.75rem;
    font-size: 0.65rem;
    font-weight: 800;
    text-transform: uppercase;
    background: var(--brand-accent);
    color: white;
    border-radius: 999px;
}
```

- [ ] **Step 5: Build CSS and test**

Run: `npm run build`
Expected: Build succeeds

Run: `php artisan test --filter=ReviewSubmissionTest`
Expected: Review tests pass

- [ ] **Step 6: Commit**

```bash
git add Modules/CatalogDelivery/app/Models/Review.php
git add Modules/CatalogDelivery/app/Http/Controllers/ReviewController.php
git add Modules/CatalogDelivery/resources/views/product.blade.php
git add resources/css/app.css
git commit -m "feat: review moderation UX - toast on submit, pending badge for author"
```

---

### Task 7: Image Audit - Verify & Document

**Files:**
- Reference: `Modules/CatalogDelivery/database/seeders/CatalogInventory.php`

**Interfaces:**
- Produces: Verification report (manual)

- [ ] **Step 1: List all products with images**

Run: 
```bash
php artisan tinker --execute="
Modules\CatalogDelivery\Models\Product::with('images')->get()->each(function(\$p) {
    \$img = \$p->images->first();
    echo \$p->name . ' | ' . (\$img ? \$img->url : 'NO IMAGE') . PHP_EOL;
});
"
```

- [ ] **Step 2: Verify semantic matches**

Manual: Visit `/product/{id}` for each product, confirm image matches name.
If mismatch found: Update `CatalogInventory::IMAGES` URL, re-run seeder.

- [ ] **Step 3: Document any fixes**

If changes made to CatalogInventory.php, commit:

```bash
git add Modules/CatalogDelivery/database/seeders/CatalogInventory.php
git commit -m "fix: correct image mappings for [product names]"
```

---

### Task 8: Build, Test, Deploy

**Files:** All modified files

**Interfaces:**
- Produces: Production-ready deployment

- [ ] **Step 1: Run full test suite**

Run: `php artisan test`
Expected: 200 tests pass

- [ ] **Step 2: Build production assets**

Run: `npm run build`
Expected: Build succeeds

- [ ] **Step 3: Commit all changes**

```bash
git add -A
git commit -m "chore: build production assets for remaining fixes"
```

- [ ] **Step 4: Push to GitHub**

Run: `git push origin main`
Expected: Push succeeds

- [ ] **Step 5: Deploy to server**

Run on server:
```bash
cd /var/www/smartshop && git pull origin main && npm run build && php artisan config:clear && php artisan route:clear && php artisan view:clear && php artisan cache:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache && chown -R www-data:www-data storage bootstrap/cache && chmod -R 775 storage bootstrap/cache
```

- [ ] **Step 6: Verify production**

Check: Homepage, product page, login, contact, 404, cart, reviews
Expected: All features work, no console errors

---

## Summary

| Task | Files | Est. Time |
|------|-------|-----------|
| 1. Contact email | 1 view | 10 min |
| 2. Footer PayPal | 1 view | 5 min |
| 3. 404 page | 1 new view | 15 min |
| 4. Mobile header CSS | 1 CSS file | 10 min |
| 5. Login remember me | 1 controller | 20 min |
| 6. Review UX | 1 model, 1 controller, 1 view, 1 CSS | 45 min |
| 7. Image audit | Manual + 1 seeder | 30 min |
| 8. Build/test/deploy | All | 10 min |
| **Total** | **~8 files** | **~2.5 hours** |

---

## Self-Review Checklist

- [ ] All 7 spec items covered by tasks
- [ ] No placeholders - all code shown
- [ ] File paths exact
- [ ] Test commands included
- [ ] Commit messages follow convention
- [ ] No checkout UX changes included
- [ ] Email uses `support@smartshop-luwi.tech`
- [ ] Marrakech removed from contact page