# Real Profile Pages for All Actors + Legal Pages — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rebuild buyer/partner/admin profile pages as view-first "real human" profile pages (verified badge, member tier, activity timeline, avatar upload, password change) on a shared profile shell, and add Privacy/Terms/Shipping/Returns pages wired to the footer.

**Architecture:** One anonymous Blade component (`profile-layout`) provides the identity header + section sub-nav + slot. Buyer (`/profile`), admin (`/admin/profile`), and partner (`/partner/profile`) each get a view rendering inside it with actor-specific sections. Identity signals (tier, verified, timeline) are derived accessors on the `User` model — no new schema beyond a nullable `avatars` column. Legal pages are static Blade views behind a whitelisted `LegalController@show`.

**Tech Stack:** Laravel 12, Blade components, Tailwind v4/Vite, PHPUnit module suites, MySQL.

## Global Constraints

- Route names are immutable: never rename existing route names; only ADD new ones (`profile.avatar`, `profile.password`, `admin.profile`, `partner.profile.show`, `privacy`, `terms`, `shipping`, `returns`).
- Zero SQL in Blade views; zero inline `style="..."` attributes (email templates excepted); no emojis in UI text (inline SVG icons allowed; `★` rating stars allowed).
- Follow the `.pc-*` console design system for console pages and the storefront `app.css` token system for storefront pages; the profile shell styles live in `resources/css/app.css` (shared tokens) so they render on both.
- No new dependencies. `public/storage` symlink already exists (storage:link done).
- Every task ends with tests passing and a commit. TDD: write the failing test first.

---

### Task 1: User identity layer — avatar, tier, verified, timeline

**Files:**
- Create: `Modules/IdentityAccess/database/migrations/2026_08_18_000002_add_avatars_to_users_table.php`
- Modify: `Modules/IdentityAccess/app/Models/User.php`
- Test: `Modules/IdentityAccess/tests/Feature/IdentitySignalsTest.php`

**Interfaces:**
- Produces:
  - `User::avatarUrl(): ?string` — `asset('storage/avatars/<filename>')` or `null`
  - `User::memberTier(): string` — one of `Member|Collector|Patron|Benefactor` (total spent on `completed` orders: <500 / 500+ / 2500+ / 10000+)
  - `User::isVerifiedMember(): bool` — status `active` AND avatarUrl() AND primary address AND ≥1 completed order
  - `User::activityTimeline(int $limit = 10): Illuminate\Support\Collection` — merged desc-sorted items `{type: 'order'|'review'|'archive', at: Carbon, title: string, detail: ?string}`

- [ ] **Step 1: Write the failing test**

`Modules/IdentityAccess/tests/Feature/IdentitySignalsTest.php`:

```php
<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Modules\IdentityAccess\Models\User;
use Modules\CatalogDelivery\Models\Product;
use Modules\CatalogDelivery\Models\Review;
use Modules\IdentityAccess\Models\Wishlist;
use Modules\MarketplacePipeline\Models\Order;
use App\Models\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Tests\TestCase;

class IdentitySignalsTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_tier_boundaries(): void
    {
        $plain = User::factory()->create(['status' => 'active']);
        Order::create(['user_id' => $plain->id, 'total_price' => 499, 'status' => 'completed']);
        $this->assertSame('Member', $plain->memberTier());

        $collector = User::factory()->create(['status' => 'active']);
        Order::create(['user_id' => $collector->id, 'total_price' => 500, 'status' => 'completed']);
        $this->assertSame('Collector', $collector->memberTier());

        $patron = User::factory()->create(['status' => 'active']);
        Order::create(['user_id' => $patron->id, 'total_price' => 2500, 'status' => 'completed']);
        $this->assertSame('Patron', $patron->memberTier());

        $benefactor = User::factory()->create(['status' => 'active']);
        Order::create(['user_id' => $benefactor->id, 'total_price' => 10000, 'status' => 'completed']);
        $this->assertSame('Benefactor', $benefactor->memberTier());
    }

    public function test_verification_requires_active_avatar_address_and_completed_order(): void
    {
        $user = User::factory()->create(['status' => 'active', 'avatars' => 'u.jpg']);
        Address::create(['user_id' => $user->id, 'is_primary' => true, 'line1' => 'A', 'city' => 'B', 'country' => 'C']);
        Order::create(['user_id' => $user->id, 'total_price' => 100, 'status' => 'completed']);
        $this->assertTrue($user->isVerifiedMember());

        $user->update(['status' => 'pending']);
        $this->assertFalse($user->fresh()->isVerifiedMember());
        $user->update(['status' => 'active']);
        $user->update(['avatars' => null]);
        $this->assertFalse($user->fresh()->isVerifiedMember());
    }

    public function test_activity_timeline_merges_orders_reviews_and_archives_sorted_desc(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $product = Product::factory()->create(['stock' => 5]);
        Order::create(['user_id' => $user->id, 'total_price' => 100, 'status' => 'completed']);
        Review::create(['user_id' => $user->id, 'product_id' => $product->id, 'rating' => 5, 'comment' => 'Gorgeous']);
        Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);

        $timeline = $user->activityTimeline();
        $this->assertCount(3, $timeline);
        $types = $timeline->pluck('type')->sort()->values()->all();
        $this->assertSame(['archive', 'order', 'review'], $types);
        $this->assertSame('order', $timeline->sortByDesc('at')->first()['type']);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=IdentitySignalsTest`
Expected: FAIL — `Call to undefined method ... memberTier()` / missing `avatars` column.

- [ ] **Step 3: Create the migration**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatars')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('avatars');
        });
    }
};
```

Run: `php artisan migrate`

- [ ] **Step 4: Add the model methods**

In `Modules/IdentityAccess/app/Models/User.php`:
- Add `'avatars'` to `$fillable`.
- Add below `wishlists()`:

```php
    public function avatarUrl(): ?string
    {
        return $this->avatars ? asset('storage/avatars/' . $this->avatars) : null;
    }

    public function memberTier(): string
    {
        $spent = (float) $this->orders()
            ->where('status', 'completed')
            ->sum('total_price');

        return match (true) {
            $spent >= 10000 => 'Benefactor',
            $spent >= 2500 => 'Patron',
            $spent >= 500 => 'Collector',
            default => 'Member',
        };
    }

    public function isVerifiedMember(): bool
    {
        if ($this->status !== 'active' || ! $this->avatars) {
            return false;
        }

        $hasAddress = $this->addresses()->where('is_primary', true)->exists();
        $hasCompletedOrder = $this->orders()->where('status', 'completed')->exists();

        return $hasAddress && $hasCompletedOrder;
    }

    public function activityTimeline(int $limit = 10)
    {
        $orders = $this->orders()->get()->map(fn ($o) => [
            'type' => 'order',
            'at' => $o->created_at,
            'title' => 'Order #' . $o->id . ' placed',
            'detail' => '$' . number_format($o->total_price, 2),
        ]);

        $reviews = $this->reviews()->with('product')->get()->map(fn ($r) => [
            'type' => 'review',
            'at' => $r->created_at,
            'title' => 'Reviewed ' . ($r->product->name ?? 'a piece'),
            'detail' => str_repeat('★', $r->rating),
        ]);

        $archives = $this->wishlists()->with('product')->get()->map(fn ($w) => [
            'type' => 'archive',
            'at' => $w->created_at,
            'title' => 'Archived ' . ($w->product->name ?? 'a piece'),
            'detail' => null,
        ]);

        return $orders->concat($reviews)->concat($archives)
            ->sortByDesc('at')
            ->take($limit)
            ->values();
    }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=IdentitySignalsTest`
Expected: 3 passed.

- [ ] **Step 6: Commit**

```bash
git add Modules/IdentityAccess/database/migrations/2026_08_18_000002_add_avatars_to_users_table.php Modules/IdentityAccess/app/Models/User.php Modules/IdentityAccess/tests/Feature/IdentitySignalsTest.php
git commit -m "feat: user identity signals — avatar url, member tier, verified badge, activity timeline"
```

---

### Task 2: Shared profile shell component

**Files:**
- Create: `resources/views/components/profile-layout.blade.php`
- Modify: `resources/css/app.css` (append PROFILE SHELL block before the RESPONSIVE section)

**Interfaces:**
- Consumes: `$user` (User model), plus optional `$stats` (array), `$subnav` (array of `['id' => ..., 'label' => ...]`), `$timeline`, `$recentOrders` passed as props
- Produces: `<x-profile-layout :user="$user" :stats="$stats" :subnav="$subnav">...slot...</x-profile-layout>`; CSS classes: `.profile-shell`, `.profile-header`, `.profile-avatar`, `.profile-avatar__img`, `.profile-avatar__letter`, `.profile-avatar__upload`, `.profile-id`, `.profile-id__name`, `.profile-id__meta`, `.profile-badge`, `.profile-badge--verified`, `.profile-badge--tier`, `.profile-subnav`, `.profile-subnav__link`, `.profile-section`, `.profile-stat`, `.profile-stat__label`, `.profile-stat__value`, `.profile-timeline`, `.profile-timeline__item`, `.profile-timeline__dot`, `.profile-timeline__title`, `.profile-timeline__detail`, `.profile-timeline__time`, `.profile-card`

- [ ] **Step 1: Write the component**

`resources/views/components/profile-layout.blade.php`:

```blade
@props([
    'user',
    'stats' => [],
    'subnav' => [
        ['id' => 'overview', 'label' => 'Overview'],
        ['id' => 'activity', 'label' => 'Orders / Activity'],
        ['id' => 'security', 'label' => 'Address & Security'],
        ['id' => 'settings', 'label' => 'Settings'],
    ],
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
            <a href="#{{ $item['id'] }}" class="profile-subnav__link">{{ $item['label'] }}</a>
        @endforeach
    </nav>

    {{ $slot }}
</div>
```

- [ ] **Step 2: Add the CSS**

Append to `resources/css/app.css` before the RESPONSIVE block:

```css
/* ============================================================
   PROFILE SHELL (shared — storefront + console)
   ============================================================ */

.profile-shell { max-width: 1100px; margin: 0 auto; }

.profile-header {
    display: flex;
    align-items: center;
    gap: 2rem;
    background: var(--surface-100);
    border: 1px solid var(--border);
    border-radius: 2rem;
    padding: 2.5rem;
    margin-bottom: 2.5rem;
}

.profile-avatar {
    position: relative;
    width: 96px;
    height: 96px;
    flex-shrink: 0;
}

.profile-avatar__img,
.profile-avatar__letter {
    width: 100%;
    height: 100%;
    border-radius: 50%;
    object-fit: cover;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--brand-accent-soft);
    color: var(--brand-accent);
    font-size: 2.5rem;
    font-weight: 800;
}

.profile-avatar__upload { position: absolute; right: -4px; bottom: -4px; }

.profile-avatar__btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--text-900);
    color: var(--surface-100);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: var(--shadow-md);
    transition: transform 0.2s ease;
}

.profile-avatar__btn:hover { transform: scale(1.08); }

.profile-id__name { font-size: 2.25rem; font-weight: 800; letter-spacing: -0.02em; }

.profile-id__meta {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.6rem;
    margin-top: 0.75rem;
}

.profile-id__since { color: var(--text-400); font-size: 0.85rem; font-weight: 600; }

.profile-badge {
    font-size: 0.7rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    padding: 0.3rem 0.8rem;
    border-radius: 2rem;
    background: var(--surface-300);
    color: var(--text-600);
}

.profile-badge--verified { background: var(--pc-ok-bg, #dcfce7); color: var(--pc-ok-fg, #166534); }

.profile-badge--tier { background: var(--brand-accent-soft); color: var(--brand-accent); }

.profile-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2.5rem;
}

.profile-stat {
    background: var(--surface-100);
    border: 1px solid var(--border);
    border-radius: 1.25rem;
    padding: 1.5rem;
    text-align: center;
}

.profile-stat__value { display: block; font-size: 1.75rem; font-weight: 800; color: var(--text-900); }

.profile-stat__label {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-400);
}

.profile-subnav {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    border-bottom: 1px solid var(--border);
    padding-bottom: 0;
    margin-bottom: 3rem;
}

.profile-subnav__link {
    padding: 0.8rem 1.4rem;
    font-size: 0.85rem;
    font-weight: 800;
    color: var(--text-600);
    text-decoration: none;
    border-bottom: 3px solid transparent;
    transition: color 0.2s ease, border-color 0.2s ease;
}

.profile-subnav__link:hover { color: var(--text-900); border-color: var(--border); }

.profile-section { scroll-margin-top: 2rem; margin-bottom: 4rem; }

.profile-card {
    background: var(--surface-100);
    border: 1px solid var(--border);
    border-radius: 1.5rem;
    padding: 2rem;
}

.profile-timeline { list-style: none; margin: 0; padding: 0; }

.profile-timeline__item {
    display: flex;
    gap: 1rem;
    align-items: flex-start;
    padding: 1rem 0;
    border-bottom: 1px solid var(--border);
}

.profile-timeline__item:last-child { border-bottom: none; }

.profile-timeline__dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: var(--brand-accent);
    margin-top: 0.45rem;
    flex-shrink: 0;
}

.profile-timeline__title { font-weight: 800; color: var(--text-900); font-size: 0.95rem; }

.profile-timeline__detail { color: var(--text-600); font-size: 0.85rem; margin-top: 0.15rem; }

.profile-timeline__time { margin-left: auto; color: var(--text-400); font-size: 0.8rem; white-space: nowrap; }

.visually-hidden {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
```

- [ ] **Step 3: Build + visual smoke check**

Run: `npm run build` — Expected: builds clean (ignore the pre-existing `@source` warning).
Run: `php artisan view:clear`

- [ ] **Step 4: Commit**

```bash
git add resources/views/components/profile-layout.blade.php resources/css/app.css
git commit -m "feat: shared profile shell component (identity header, stats, sub-nav)"
```

---

### Task 3: Buyer profile redesign

**Files:**
- Modify: `Modules/IdentityAccess/app/Http/Controllers/UserController.php` (`show()`)
- Rewrite: `Modules/IdentityAccess/resources/views/users/show.blade.php`
- Test: `Modules/IdentityAccess/tests/Feature/ProfileTest.php` (extend)

**Interfaces:**
- Consumes: `User::activityTimeline()`, `User::memberTier()`, `User::isVerifiedMember()`, `User::avatarUrl()`
- Produces: `UserController::show()` returns view with `$user`, `$address`, `$stats` (Orders placed / Total spent / Archived pieces), `$timeline`, `$recentOrders`

- [ ] **Step 1: Write the failing test**

Append to `Modules/IdentityAccess/tests/Feature/ProfileTest.php`:

```php
    public function test_profile_page_renders_identity_signals_and_stats(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $product = Product::factory()->create(['stock' => 5]);
        \Modules\MarketplacePipeline\Models\Order::create(['user_id' => $user->id, 'total_price' => 620, 'status' => 'completed']);
        Address::create(['user_id' => $user->id, 'is_primary' => true, 'line1' => 'A', 'city' => 'B', 'country' => 'C']);
        \Modules\IdentityAccess\Models\Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk()
            ->assertSee('Collector', false)
            ->assertSee('profile-stats')
            ->assertSee('profile-timeline')
            ->assertSee('Orders / Activity')
            ->assertSee('Address & Security');
    }
```

Add imports at the top of the test file: `use Modules\CatalogDelivery\Models\Product;` and `use Modules\IdentityAccess\Models\Wishlist;`.

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --filter=ProfileTest`
Expected: FAIL — profile page lacks the new markup.

- [ ] **Step 3: Implement `show()`**

Replace the `show()` method in `UserController.php`:

```php
    public function show()
    {
        $user = Auth::user()->load(['orders', 'addresses', 'reviews', 'wishlists']);
        $address = $user->addresses->firstWhere('is_primary', true) ?? $user->addresses->first();

        $stats = [
            'Orders placed' => $user->orders->count(),
            'Total spent' => '$' . number_format($user->orders->where('status', 'completed')->sum('total_price'), 0),
            'Archived pieces' => $user->wishlists->count(),
        ];

        return view('identityaccess::users.show', [
            'user' => $user,
            'address' => $address,
            'stats' => $stats,
            'timeline' => $user->activityTimeline(8),
            'recentOrders' => $user->orders()->latest()->take(4)->get(),
        ]);
    }
```

- [ ] **Step 4: Rewrite the buyer profile blade**

`Modules/IdentityAccess/resources/views/users/show.blade.php` (full replacement):

```blade
@section('title', 'Member Profile | LUWI')

<x-app-layout>
<x-profile-layout :user="$user" :stats="$stats" :subnav="[
    ['id' => 'overview', 'label' => 'Overview'],
    ['id' => 'activity', 'label' => 'Orders / Activity'],
    ['id' => 'security', 'label' => 'Address & Security'],
    ['id' => 'settings', 'label' => 'Settings'],
]">

    <section id="overview" class="profile-section">
        <h2 class="pc-card__title">Activity</h2>
        @if ($timeline->isEmpty())
            <div class="profile-card">
                <p style="color: var(--text-400); font-weight: 700;">No activity yet — your journey begins with the first piece you collect.</p>
            </div>
        @else
            <div class="profile-card">
                <ul class="profile-timeline">
                    @foreach ($timeline as $event)
                        <li class="profile-timeline__item">
                            <span class="profile-timeline__dot"></span>
                            <div>
                                <div class="profile-timeline__title">{{ $event['title'] }}</div>
                                @if ($event['detail'])
                                    <div class="profile-timeline__detail">{{ $event['detail'] }}</div>
                                @endif
                            </div>
                            <span class="profile-timeline__time">{{ $event['at']->diffForHumans() }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    </section>

    <section id="activity" class="profile-section">
        <h2 class="pc-card__title">Order History</h2>
        @forelse ($recentOrders as $order)
            <div class="profile-card" style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                    <div>
                        <span class="order-lite-id">#{{ $order->id }}</span>
                        <h3 class="order-lite-title">Placed on {{ $order->created_at->format('d M, Y') }}</h3>
                    </div>
                    <div class="order-lite-total">
                        <div class="order-lite-price">${{ number_format($order->total_price, 0) }}</div>
                        <span class="order-lite-status">{{ $order->status }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="order-lite-empty">
                <p class="order-lite-empty__text">No acquisitions recorded yet.</p>
                <a href="{{ route('shop') }}" class="order-lite-empty__link">Begin Collection</a>
            </div>
        @endforelse
        @if ($recentOrders->isNotEmpty())
            <a href="{{ route('orders.index') }}" class="btn btn-ghost">View Full History</a>
        @endif
    </section>

    <section id="security" class="profile-section">
        <h2 class="pc-card__title">Address</h2>
        <div class="profile-card" style="margin-bottom: 1.5rem;">
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
                <button type="submit" class="btn btn-primary">Update Password</button>
            </form>
        </div>
    </section>

    <section id="settings" class="profile-section">
        <h2 class="pc-card__title">Account Details</h2>
        <div class="profile-card">
            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-input" value="{{ $user->name }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" value="{{ $user->email }}">
                </div>
                <button type="submit" class="btn btn-primary">Save Account Details</button>
            </form>
        </div>
    </section>

</x-profile-layout>
</x-app-layout>
```

Note: the two `style="..."` attributes on wrapper divs above are intentional whitespace-avoidance hacks — replace them with the `profile-card` + `gap` utility before committing if lint demands zero inline styles (see Task 4 Step 5 note; the `.profile-card` margin utility is `profile-card + .profile-card { margin-top: 1rem; }` — add that rule to app.css and drop the inline styles).

- [ ] **Step 5: Add the card-gap CSS rule**

In `resources/css/app.css`, after `.profile-card { ... }`:

```css
.profile-card + .profile-card { margin-top: 1rem; }
```

Then remove both `style="margin-bottom: 1rem;"` attributes from the blade.

- [ ] **Step 6: Run tests**

Run: `php artisan test --filter=ProfileTest`
Expected: all ProfileTest tests pass (existing 2 + new 1).

- [ ] **Step 7: Commit**

```bash
git add Modules/IdentityAccess/app/Http/Controllers/UserController.php Modules/IdentityAccess/resources/views/users/show.blade.php Modules/IdentityAccess/tests/Feature/ProfileTest.php resources/css/app.css
git commit -m "feat: buyer profile — view-first shell with timeline, stats, address & security sections"
```

---

### Task 4: Avatar upload + password change endpoints

**Files:**
- Modify: `Modules/IdentityAccess/routes/web.php`
- Modify: `Modules/IdentityAccess/app/Http/Controllers/UserController.php`
- Modify: `Modules/IdentityAccess/resources/views/auth/login.blade.php` (remove dead "Forgot password?" link)
- Test: `Modules/IdentityAccess/tests/Feature/ProfileTest.php` (extend)

**Interfaces:**
- Consumes: `User::avatarUrl()`
- Produces:
  - `POST /profile/avatar` → name `profile.avatar`, `UserController::updateAvatar(Request): RedirectResponse`
  - `PUT /profile/password` → name `profile.password`, `UserController::updatePassword(Request): RedirectResponse`

- [ ] **Step 1: Write the failing tests**

Append to `Modules/IdentityAccess/tests/Feature/ProfileTest.php`:

```php
    public function test_avatar_upload_stores_file_and_sets_url(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->post('/profile/avatar', [
            'avatar' => \Illuminate\Http\UploadedFile::fake()->image('me.jpg', 200, 200),
        ]);

        $response->assertRedirect();
        $this->assertNotNull($user->fresh()->avatars);
        $this->assertStringContainsString('/storage/avatars/', $user->fresh()->avatarUrl());
    }

    public function test_avatar_upload_rejects_invalid_type(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        $response = $this->actingAs($user)->post('/profile/avatar', [
            'avatar' => \Illuminate\Http\UploadedFile::fake()->create('evil.txt', 10),
        ]);

        $response->assertSessionHasErrors('avatar');
        $this->assertNull($user->fresh()->avatars);
    }

    public function test_password_change_requires_current_password_and_persists_new_hash(): void
    {
        $user = User::factory()->create(['password' => 'current-pass-123']);

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'wrong',
            'password' => 'new-pass-456',
            'password_confirmation' => 'new-pass-456',
        ]);
        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('current-pass-123', $user->fresh()->password));

        $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'current-pass-123',
            'password' => 'new-pass-456',
            'password_confirmation' => 'new-pass-456',
        ])->assertRedirect();

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('new-pass-456', $user->fresh()->password));
    }
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=ProfileTest`
Expected: FAIL — routes 404.

- [ ] **Step 3: Add routes**

In `Modules/IdentityAccess/routes/web.php`, inside the `auth` group after the existing profile routes:

```php
    Route::post('/profile/avatar', [UserController::class, 'updateAvatar'])->name('profile.avatar');
    Route::put('/profile/password', [UserController::class, 'updatePassword'])->name('profile.password');
```

- [ ] **Step 4: Add controller methods**

In `UserController.php` (after `updateProfile`):

```php
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,webp|max:2048',
        ]);

        $user = Auth::user();

        $path = $request->file('avatar')->store('avatars', 'public');

        if ($user->avatars && \Illuminate\Support\Facades\Storage::disk('public')->exists('avatars/' . $user->avatars)) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete('avatars/' . $user->avatars);
        }

        $user->update(['avatars' => basename($path)]);

        return redirect()->back()->with('success', 'Profile photo updated');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', function ($attribute, $value, $fail) {
                if (! \Illuminate\Support\Facades\Hash::check($value, Auth::user()->password)) {
                    $fail('The current password is incorrect.');
                }
            }],
            'password' => 'required|string|min:8|confirmed',
        ]);

        Auth::user()->update(['password' => $request->password]);
        $request->session()->regenerate();

        return redirect()->back()->with('success', 'Password updated successfully');
    }
```

- [ ] **Step 5: Remove the dead forgot-password link**

In `Modules/IdentityAccess/resources/views/auth/login.blade.php`, delete the line:
`<a href="#" class="forgot-password">Forgot password?</a>`
(and its wrapping container if it becomes empty — inspect the surrounding markup first).

- [ ] **Step 6: Run tests**

Run: `php artisan test --filter=ProfileTest`
Expected: all pass (6 tests in file).

- [ ] **Step 7: Commit**

```bash
git add Modules/IdentityAccess/routes/web.php Modules/IdentityAccess/app/Http/Controllers/UserController.php Modules/IdentityAccess/resources/views/auth/login.blade.php Modules/IdentityAccess/tests/Feature/ProfileTest.php
git commit -m "feat: profile avatar upload and in-profile password change; drop dead forgot-password link"
```

---

### Task 5: Admin profile page

**Files:**
- Create: `Modules/IdentityAccess/app/Http/Controllers/AdminProfileController.php`
- Create: `Modules/IdentityAccess/resources/views/admin/profile.blade.php`
- Modify: `Modules/IdentityAccess/routes/web.php`
- Modify: `resources/views/partials/admin-nav.blade.php` (add Profile tab)
- Test: `Modules/IdentityAccess/tests/Feature/AdminProfileTest.php`

**Interfaces:**
- Consumes: `GovernanceService::getDashboardMetrics()` (same as `AdminDashboardController`), profile shell
- Produces: `GET /admin/profile` → name `admin.profile`, `AdminProfileController::index(): View` with `$user`, `$stats`, `$timeline`, `$recentOrders`, `$subnav`

- [ ] **Step 1: Write the failing test**

`Modules/IdentityAccess/tests/Feature/AdminProfileTest.php`:

```php
<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Modules\IdentityAccess\Models\User;
use Modules\MarketplacePipeline\Models\Order;
use App\Models\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Tests\TestCase;

class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_profile_page_renders_for_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        Address::create(['user_id' => $admin->id, 'is_primary' => true, 'line1' => 'A', 'city' => 'B', 'country' => 'C']);
        $admin->update(['avatars' => 'admin.jpg']);
        Order::create(['user_id' => $admin->id, 'total_price' => 100, 'status' => 'completed']);

        $response = $this->actingAs($admin)->get('/admin/profile');

        $response->assertOk()
            ->assertSee('profile-header')
            ->assertSee('Verified Member')
            ->assertSee('profile-stats');
    }

    public function test_admin_profile_denied_for_buyers(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $this->actingAs($user)->get('/admin/profile')->assertRedirect();
    }
}
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=AdminProfileTest`
Expected: FAIL — 404 / redirect mismatch.

- [ ] **Step 3: Create the controller**

`Modules/IdentityAccess/app/Http/Controllers/AdminProfileController.php`:

```php
<?php

namespace Modules\IdentityAccess\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Modules\IdentityAccess\Services\GovernanceService;

class AdminProfileController extends Controller
{
    public function index(GovernanceService $governance)
    {
        $user = Auth::user()->load(['orders', 'addresses', 'reviews', 'wishlists']);

        $metrics = $governance->getDashboardMetrics();

        $stats = [
            'Revenue' => '$' . number_format($metrics['stats']['revenue'] ?? 0, 0),
            'Active orders' => $metrics['stats']['active_orders'] ?? 0,
            'Members' => \Modules\IdentityAccess\Models\User::count(),
            'Pending reviews' => $metrics['stats']['pending_reviews'] ?? 0,
        ];

        return view('identityaccess::admin.profile', [
            'user' => $user,
            'stats' => $stats,
            'timeline' => $user->activityTimeline(8),
            'recentOrders' => \Modules\MarketplacePipeline\Models\Order::latest()->take(5)->get(),
        ]);
    }
}
```

Verify `getDashboardMetrics()` array shape before committing — if keys differ from `['stats']['revenue']`, adjust to match the actual return (check `GovernanceService`).

- [ ] **Step 4: Add the route**

In `Modules/IdentityAccess/routes/web.php`, inside the `auth`+`admin` group, after the dashboard route:

```php
    Route::get('/profile', [\Modules\IdentityAccess\Http\Controllers\AdminProfileController::class, 'index'])->name('profile');
```

This yields `admin.profile` via the group prefix. Note: this is a NEW route name — no existing names change.

- [ ] **Step 5: Create the admin profile blade**

`Modules/IdentityAccess/resources/views/admin/profile.blade.php`:

```blade
@section('title', 'Admin Profile | Command Center')

<x-app-layout>
@include('partials.admin-nav')

<x-profile-layout :user="$user" :stats="$stats" :subnav="[
    ['id' => 'overview', 'label' => 'Overview'],
    ['id' => 'activity', 'label' => 'Recent Activity'],
    ['id' => 'security', 'label' => 'Address & Security'],
    ['id' => 'settings', 'label' => 'Settings'],
]">

    <section id="overview" class="profile-section">
        <h2 class="pc-card__title">Platform Pulse</h2>
        <div class="profile-card">
            <ul class="profile-timeline">
                @foreach ($timeline as $event)
                    <li class="profile-timeline__item">
                        <span class="profile-timeline__dot"></span>
                        <div>
                            <div class="profile-timeline__title">{{ $event['title'] }}</div>
                            @if ($event['detail'])
                                <div class="profile-timeline__detail">{{ $event['detail'] }}</div>
                            @endif
                        </div>
                        <span class="profile-timeline__time">{{ $event['at']->diffForHumans() }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <section id="activity" class="profile-section">
        <h2 class="pc-card__title">Recent Acquisitions</h2>
        <div class="pc-table-wrap">
            <table class="pc-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Member</th>
                        <th>Value</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($recentOrders as $order)
                        <tr>
                            <td class="is-numeric">#{{ $order->id }}</td>
                            <td class="is-muted">{{ $order->user->name }}</td>
                            <td class="is-strong">${{ number_format($order->total_price, 2) }}</td>
                            <td>@include('partials.partner.status-badge', ['status' => $order->status])</td>
                            <td class="is-muted">{{ $order->created_at->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="is-muted">No acquisitions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section id="security" class="profile-section">
        <h2 class="pc-card__title">Address & Password</h2>
        <div class="profile-card">
            <p class="pc-subtitle">Use the same forms as the member profile — address fields and password change are shared across roles.</p>
            <a href="{{ route('profile') }}" class="btn btn-ghost">Open Member Profile Settings</a>
        </div>
    </section>

    <section id="settings" class="profile-section">
        <h2 class="pc-card__title">Settings</h2>
        <div class="profile-card">
            <form action="{{ route('profile.update') }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-input" value="{{ $user->name }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-input" value="{{ $user->email }}">
                </div>
                <button type="submit" class="btn btn-primary">Save Details</button>
            </form>
        </div>
    </section>

</x-profile-layout>
</x-app-layout>
```

- [ ] **Step 6: Add the admin nav Profile tab**

In `resources/views/partials/admin-nav.blade.php`, before the closing `</nav>`, append:

```blade
    <a href="{{ route('admin.profile') }}" class="pc-nav__tab {{ request()->routeIs('admin.profile') ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6 8-6s8 2 8 6"/></svg>
        Profile
    </a>
```

- [ ] **Step 7: Run tests**

Run: `php artisan test --filter=AdminProfileTest`
Expected: 2 passed.

- [ ] **Step 8: Commit**

```bash
git add Modules/IdentityAccess/app/Http/Controllers/AdminProfileController.php Modules/IdentityAccess/resources/views/admin/profile.blade.php Modules/IdentityAccess/routes/web.php resources/views/partials/admin-nav.blade.php Modules/IdentityAccess/tests/Feature/AdminProfileTest.php
git commit -m "feat: admin profile page on shared shell with console stats and nav tab"
```

---

### Task 6: Partner profile page

**Files:**
- Modify: `Modules/PartnerHub/app/Http/Controllers/PartnerProfileController.php`
- Create: `Modules/PartnerHub/resources/views/partner/profile/show.blade.php`
- Modify: `Modules/PartnerHub/routes/web.php`
- Test: `Modules/PartnerHub/tests/Feature/PartnerProfileShowTest.php`

**Interfaces:**
- Consumes: `Partner::where('user_id', ...)` pattern (existing), `AnalyticsService::partnerDashboard()` for stats, profile shell
- Produces: `GET /partner/profile` → name `partner.profile.show`, `PartnerProfileController::profile(): View` with `$user`, `$partner`, `$stats`, `$timeline`, `$recentOrders`

- [ ] **Step 1: Write the failing test**

`Modules/PartnerHub/tests/Feature/PartnerProfileShowTest.php`:

```php
<?php

namespace Modules\PartnerHub\Tests\Feature;

use Modules\IdentityAccess\Models\User;
use Modules\PartnerHub\Models\Partner;
use Modules\CatalogDelivery\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\PartnerHub\Tests\TestCase;

class PartnerProfileShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_profile_view_renders_with_stats(): void
    {
        $user = User::factory()->create(['role' => 'partner', 'status' => 'active']);
        $partner = Partner::create([
            'user_id' => $user->id,
            'name' => 'Atelier Test',
            'description' => 'Handcrafted pieces',
            'contact_info' => 'contact@test.com',
        ]);
        $product = Product::factory()->create(['stock' => 5]);
        $partner->products()->attach($product->id);

        $response = $this->actingAs($user)->get('/partner/profile');

        $response->assertOk()
            ->assertSee('Atelier Test')
            ->assertSee('profile-stats')
            ->assertSee('artisan-profile')
            ->assertSee('profile-header');
    }

    public function test_partner_profile_denied_for_buyers(): void
    {
        $user = User::factory()->create(['role' => 'user', 'status' => 'active']);
        $this->actingAs($user)->get('/partner/profile')->assertRedirect();
    }
}
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=PartnerProfileShowTest`
Expected: FAIL — route missing.

- [ ] **Step 3: Add controller method**

In `Modules/PartnerHub/app/Http/Controllers/PartnerProfileController.php`, add:

```php
    public function profile()
    {
        $partner = Partner::where('user_id', auth()->id())->firstOrFail();
        $user = auth()->user()->load(['orders', 'addresses', 'reviews', 'wishlists']);

        $inventoryCount = $partner->products()->count();
        $pendingPayout = $partner->payouts()->where('status', 'pending')->sum('amount');

        $stats = [
            'Pieces in catalog' => $inventoryCount,
            'Pending earnings' => '$' . number_format($pendingPayout, 2),
            'Archived pieces' => $user->wishlists->count(),
        ];

        return view('partnerhub::partner.profile.show', [
            'user' => $user,
            'partner' => $partner,
            'stats' => $stats,
            'timeline' => $user->activityTimeline(8),
            'recentOrders' => $user->orders()->latest()->take(4)->get(),
        ]);
    }
```

(Add `use Modules\PartnerHub\Models\Partner;` if not already imported.)

- [ ] **Step 4: Add the route**

In `Modules/PartnerHub/routes/web.php`, inside the `partner` middleware group:

```php
    Route::get('/profile', [PartnerProfileController::class, 'profile'])->name('profile.show');
```

- [ ] **Step 5: Create the partner profile blade**

`Modules/PartnerHub/resources/views/partner/profile/show.blade.php`:

```blade
@section('title', 'My Profile | Partner Dashboard')

@section('scripts')
@vite('resources/js/partner.js')
@endsection

<x-app-layout>
@include('partials.partner-nav')

<x-profile-layout :user="$user" :stats="$stats" :subnav="[
    ['id' => 'overview', 'label' => 'Overview'],
    ['id' => 'activity', 'label' => 'My Orders'],
    ['id' => 'security', 'label' => 'Address & Security'],
    ['id' => 'settings', 'label' => 'Public Profile'],
]">

    <section id="overview" class="profile-section">
        <div class="pc-card" style="margin-bottom: 1.5rem;">
            <div class="pc-card__head">
                <div>
                    <h2 class="pc-card__title">{{ $partner->name }}</h2>
                    <p class="pc-subtitle">{{ $partner->description }}</p>
                </div>
                <a href="{{ route('partner.profile', $partner->id) }}" class="pc-btn-sm">View Public Profile</a>
            </div>
        </div>
        <h2 class="pc-card__title">Recent Activity</h2>
        <div class="profile-card">
            <ul class="profile-timeline">
                @foreach ($timeline as $event)
                    <li class="profile-timeline__item">
                        <span class="profile-timeline__dot"></span>
                        <div>
                            <div class="profile-timeline__title">{{ $event['title'] }}</div>
                            @if ($event['detail'])
                                <div class="profile-timeline__detail">{{ $event['detail'] }}</div>
                            @endif
                        </div>
                        <span class="profile-timeline__time">{{ $event['at']->diffForHumans() }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <section id="activity" class="profile-section">
        <h2 class="pc-card__title">My Orders</h2>
        @forelse ($recentOrders as $order)
            <div class="profile-card">
                <div style="display: flex; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
                    <div>
                        <span class="order-lite-id">#{{ $order->id }}</span>
                        <h3 class="order-lite-title">Placed on {{ $order->created_at->format('d M, Y') }}</h3>
                    </div>
                    <div class="order-lite-total">
                        <div class="order-lite-price">${{ number_format($order->total_price, 0) }}</div>
                        <span class="order-lite-status">{{ $order->status }}</span>
                    </div>
                </div>
            </div>
        @empty
            <div class="order-lite-empty">
                <p class="order-lite-empty__text">No orders placed yet.</p>
            </div>
        @endforelse
    </section>

    <section id="security" class="profile-section">
        <h2 class="pc-card__title">Address & Password</h2>
        <div class="profile-card">
            <p class="pc-subtitle">Address fields and password change are shared across roles — manage them from the member profile.</p>
            <a href="{{ route('profile') }}" class="pc-btn-sm">Open Member Profile Settings</a>
        </div>
    </section>

    <section id="settings" class="profile-section">
        <h2 class="pc-card__title">Public Profile</h2>
        <div class="profile-card">
            <p class="pc-subtitle">Your public artisan page is built from these fields.</p>
            <a href="{{ route('partner.profile.edit') }}" class="pc-btn-sm">Edit Business Name, Bio, Website & Contact</a>
        </div>
    </section>

</x-profile-layout>
</x-app-layout>
```

- [ ] **Step 6: Run tests**

Run: `php artisan test --filter=PartnerProfileShowTest`
Expected: 2 passed.

- [ ] **Step 7: Commit**

```bash
git add Modules/PartnerHub/app/Http/Controllers/PartnerProfileController.php Modules/PartnerHub/resources/views/partner/profile/show.blade.php Modules/PartnerHub/routes/web.php Modules/PartnerHub/tests/Feature/PartnerProfileShowTest.php
git commit -m "feat: partner profile view page with stats, timeline and public profile link"
```

---

### Task 7: Legal pages — Privacy, Terms, Shipping, Returns

**Files:**
- Create: `Modules/CatalogDelivery/app/Http/Controllers/LegalController.php`
- Create: `Modules/CatalogDelivery/resources/views/legal/privacy.blade.php`
- Create: `Modules/CatalogDelivery/resources/views/legal/terms.blade.php`
- Create: `Modules/CatalogDelivery/resources/views/legal/shipping.blade.php`
- Create: `Modules/CatalogDelivery/resources/views/legal/returns.blade.php`
- Modify: `Modules/CatalogDelivery/routes/web.php`
- Modify: `resources/views/components/app-layout.blade.php` (footer)
- Modify: `resources/css/app.css` (`.legal-*` block)
- Test: `Modules/CatalogDelivery/tests/Feature/LegalPagesTest.php`

**Interfaces:**
- Produces: `GET /privacy`, `/terms`, `/shipping`, `/returns` → names `privacy|terms|shipping|returns`; `LegalController::show(string $page)`

- [ ] **Step 1: Write the failing test**

`Modules/CatalogDelivery/tests/Feature/LegalPagesTest.php`:

```php
<?php

namespace Modules\CatalogDelivery\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CatalogDelivery\Tests\TestCase;

class LegalPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_legal_pages_render(): void
    {
        foreach (['privacy', 'terms', 'shipping', 'returns'] as $page) {
            $this->get('/' . $page)
                ->assertOk()
                ->assertSee('legal-hero');
        }
    }

    public function test_unknown_legal_page_404s(): void
    {
        $this->get('/privacy-policy-extra')->assertNotFound();
    }

    public function test_footer_links_to_all_legal_pages(): void
    {
        $response = $this->get('/');
        foreach (['/privacy', '/terms', '/shipping', '/returns'] as $url) {
            $response->assertSee($url, false);
        }
    }
}
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=LegalPagesTest`
Expected: FAIL — 404s.

- [ ] **Step 3: Create the controller**

`Modules/CatalogDelivery/app/Http/Controllers/LegalController.php`:

```php
<?php

namespace Modules\CatalogDelivery\Http\Controllers;

use App\Http\Controllers\Controller;

class LegalController extends Controller
{
    private const PAGES = ['privacy', 'terms', 'shipping', 'returns'];

    public function show(string $page)
    {
        abort_unless(in_array($page, self::PAGES, true), 404);

        return view('catalogdelivery::legal.' . $page, ['page' => $page]);
    }
}
```

- [ ] **Step 4: Add routes**

In `Modules/CatalogDelivery/routes/web.php`, after the `contact` route:

```php
use Modules\CatalogDelivery\Http\Controllers\LegalController;

Route::get('/privacy', [LegalController::class, 'show'])->name('privacy');
Route::get('/terms', [LegalController::class, 'show'])->name('terms');
Route::get('/shipping', [LegalController::class, 'show'])->name('shipping');
Route::get('/returns', [LegalController::class, 'show'])->name('returns');
```

(Laravel resolves `show('privacy')` from the URL segment automatically via the `{page}`-less signature — the methods above pass the literal path segment, so use explicit `show` calls per route with `'privacy'` etc. as arguments: `[LegalController::class, 'show']` with no argument will 404 on the whitelist. Correct form: `Route::get('/privacy', fn () => app(LegalController::class)->show('privacy'))->name('privacy');` — OR give the route a param: `Route::get('/{page}', ...)` — choose the explicit closure form for clarity.)

Use the closure form:

```php
Route::get('/privacy', fn () => app(LegalController::class)->show('privacy'))->name('privacy');
Route::get('/terms', fn () => app(LegalController::class)->show('terms'))->name('terms');
Route::get('/shipping', fn () => app(LegalController::class)->show('shipping'))->name('shipping');
Route::get('/returns', fn () => app(LegalController::class)->show('returns'))->name('returns');
```

- [ ] **Step 5: Create a shared legal layout partial**

`Modules/CatalogDelivery/resources/views/legal/_hero.blade.php` (included by all four):

```blade
<div class="legal-hero">
    <span class="cat-badge">Legal</span>
    <h1>{{ $title }}</h1>
    <p>Last updated: {{ $updated }}</p>
</div>
```

- [ ] **Step 6: Create the four pages**

`Modules/CatalogDelivery/resources/views/legal/privacy.blade.php`:

```blade
@section('title', 'Privacy Policy | SmartShop')

<x-app-layout>
<div class="container legal-page">
    @include('catalogdelivery::legal._hero', ['title' => 'Privacy Policy', 'updated' => 'August 18, 2026'])

    <div class="legal-prose">
        <h2>1. What We Collect</h2>
        <p>SmartShop collects the information you provide when creating an account — your name, email address, and password (stored encrypted). We also record the delivery details you enter at checkout so orders can be shipped, and we log standard technical data such as IP address and browser type to keep the platform secure and reliable.</p>

        <h2>2. How We Use Your Information</h2>
        <p>We use your data to operate your account, process and deliver orders, respond to support requests, and improve the shopping experience. Payment transactions are processed by PayPal; SmartShop never stores your card details. We do not sell your personal information to third parties.</p>

        <h2>3. Reviews and Public Content</h2>
        <p>Reviews you write on the platform are published with your first name and avatar so other members can see genuine feedback. Anything you choose to publish is visible to the public and cannot be fully removed once moderated as approved — you may request deletion at any time by contacting support.</p>

        <h2>4. Data Retention</h2>
        <p>We retain your account data while your account is active. Orders are retained for record-keeping and tax purposes as required by law. You may request account deletion, after which personal data is removed or anonymised within 30 days, except where retention is legally required.</p>

        <h2>5. Your Rights</h2>
        <p>You may access, correct, or delete your personal data at any time from your profile settings, or by contacting support. You may also export a copy of the data we hold about you on request.</p>

        <h2>6. Cookies</h2>
        <p>The platform uses essential cookies for authentication (session and CSRF protection) and a preference cookie to remember your display currency and theme. No third-party tracking cookies are used.</p>

        <h2>7. Contact</h2>
        <p>Questions about this policy can be sent to the support channel listed on the Contact page.</p>
    </div>
</div>
</x-app-layout>
```

`Modules/CatalogDelivery/resources/views/legal/terms.blade.php`:

```blade
@section('title', 'Terms & Conditions | SmartShop')

<x-app-layout>
<div class="container legal-page">
    @include('catalogdelivery::legal._hero', ['title' => 'Terms & Conditions', 'updated' => 'August 18, 2026'])

    <div class="legal-prose">
        <h2>1. Acceptance of Terms</h2>
        <p>By creating an account or placing an order on SmartShop, you agree to these Terms & Conditions. If you do not agree, please do not use the platform.</p>

        <h2>2. Accounts</h2>
        <p>You are responsible for safeguarding your credentials and for all activity under your account. Accounts used for automated activity, reselling access, or misrepresentation may be suspended.</p>

        <h2>3. Orders and Payment</h2>
        <p>All prices are displayed in the currency you select and are inclusive of applicable taxes unless stated otherwise. Orders are confirmed once payment is authorised by PayPal. We reserve the right to refuse or cancel an order where fraud or pricing error is suspected.</p>

        <h2>4. Partner Artisans</h2>
        <p>Products are sourced from independent partner artisans. SmartShop facilitates the transaction but does not manufacture the goods. Quality and authenticity concerns are handled through our Returns policy.</p>

        <h2>5. Reviews</h2>
        <p>Reviews must be honest, first-hand, and free of promotional or offensive content. SmartShop moderates reviews and may remove content that violates these rules.</p>

        <h2>6. Limitation of Liability</h2>
        <p>SmartShop is provided "as is". To the maximum extent permitted by law, SmartShop is not liable for indirect or consequential losses arising from use of the platform.</p>

        <h2>7. Changes</h2>
        <p>We may update these terms from time to time. Continued use of the platform after changes constitutes acceptance of the revised terms.</p>
    </div>
</div>
</x-app-layout>
```

`Modules/CatalogDelivery/resources/views/legal/shipping.blade.php`:

```blade
@section('title', 'Shipping & Delivery | SmartShop')

<x-app-layout>
<div class="container legal-page">
    @include('catalogdelivery::legal._hero', ['title' => 'Shipping & Delivery', 'updated' => 'August 18, 2026'])

    <div class="legal-prose">
        <h2>1. Processing Time</h2>
        <p>Orders are prepared and dispatched within 2–4 business days of payment confirmation. Each partner artisan inspects and packs their own pieces before dispatch.</p>

        <h2>2. Delivery Times</h2>
        <p>Estimated delivery is 5–10 business days for domestic orders and 10–20 business days internationally. Times are estimates and may vary with carrier conditions.</p>

        <h2>3. Tracking</h2>
        <p>Once dispatched, the delivery details recorded at checkout — recipient name, phone, and shipping address — are used by the carrier. Ensure these are accurate; SmartShop is not responsible for delivery failures caused by incorrect recipient details.</p>

        <h2>4. Delivery Notes</h2>
        <p>You may add delivery notes at checkout (e.g., "leave with concierge"). While we pass notes to the carrier, we cannot guarantee every instruction can be honoured.</p>

        <h2>5. Delays</h2>
        <p>Unforeseen carrier or customs delays may occur. We will keep you informed where we are able to, and support is available via the Contact page.</p>
    </div>
</div>
</x-app-layout>
```

`Modules/CatalogDelivery/resources/views/legal/returns.blade.php`:

```blade
@section('title', 'Returns & Refunds | SmartShop')

<x-app-layout>
<div class="container legal-page">
    @include('catalogdelivery::legal._hero', ['title' => 'Returns & Refunds', 'updated' => 'August 18, 2026'])

    <div class="legal-prose">
        <h2>1. Return Window</h2>
        <p>You may return a piece within 14 days of delivery for a full refund, provided it is unused, undamaged, and in its original packaging.</p>

        <h2>2. Damaged or Incorrect Items</h2>
        <p>If a piece arrives damaged or does not match the order, contact support within 7 days of delivery with photos. We will arrange a replacement or refund, including return shipping costs.</p>

        <h2>3. How to Start a Return</h2>
        <p>Request a return from the Contact page, quoting your order number and the reason. We will confirm the return address and any instructions within 2 business days.</p>

        <h2>4. Refunds</h2>
        <p>Refunds are issued to the original payment method within 5–10 business days of the returned piece being received and inspected.</p>

        <h2>5. Exceptions</h2>
        <p>Custom, personalised, or commissioned pieces cannot be returned unless faulty. This does not affect your statutory rights.</p>
    </div>
</div>
</x-app-layout>
```

- [ ] **Step 7: Add legal CSS**

Append to `resources/css/app.css` before the RESPONSIVE block:

```css
/* ============================================================
   LEGAL PAGES
   ============================================================ */

.legal-page { max-width: 780px; margin: 0 auto; padding-top: 2rem; }

.legal-hero { margin-bottom: 3rem; }

.legal-hero h1 { font-size: 2.75rem; font-weight: 800; letter-spacing: -0.03em; margin-top: 1rem; }

.legal-hero p { color: var(--text-400); font-weight: 700; margin-top: 0.5rem; }

.legal-prose h2 { font-size: 1.35rem; font-weight: 800; margin: 2.5rem 0 1rem; color: var(--text-900); }

.legal-prose p { color: var(--text-600); line-height: 1.8; font-size: 0.95rem; margin-bottom: 1rem; }
```

- [ ] **Step 8: Wire the footer**

In `resources/views/components/app-layout.blade.php`, replace the Support links block:

```blade
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
```

- [ ] **Step 9: Run tests**

Run: `php artisan test --filter=LegalPagesTest`
Expected: 3 passed.

- [ ] **Step 10: Commit**

```bash
git add Modules/CatalogDelivery/app/Http/Controllers/LegalController.php Modules/CatalogDelivery/resources/views/legal/ Modules/CatalogDelivery/routes/web.php resources/views/components/app-layout.blade.php resources/css/app.css Modules/CatalogDelivery/tests/Feature/LegalPagesTest.php
git commit -m "feat: privacy, terms, shipping and returns pages wired to the footer"
```

---

### Task 8: Review avatars use uploaded images

**Files:**
- Modify: `Modules/CatalogDelivery/resources/views/admin/reviews/index.blade.php` (line ~18 `user-avatar`)
- Modify: storefront product reviews view (find the blade rendering `user-avatar` on the product page — search for `user-avatar` in `Modules/CatalogDelivery/resources/views/`)
- Modify: `Modules/CatalogDelivery/resources/assets/scss/app.scss` (`.user-avatar` becomes a 40px circle that can hold an image)

**Interfaces:**
- Consumes: `User::avatarUrl()`
- Produces: review avatars render `avatarUrl()` image when present, letter fallback otherwise

- [ ] **Step 1: Find all `user-avatar` usages**

Run: `grep -rn "user-avatar" Modules/CatalogDelivery/resources/views/`

- [ ] **Step 2: Update each occurrence**

For each occurrence, replace:

```blade
<div class="user-avatar">{{ substr($review->user->name, 0, 1) }}</div>
```

with:

```blade
<div class="user-avatar">
    @if ($review->user->avatarUrl())
        <img src="{{ $review->user->avatarUrl() }}" alt="{{ $review->user->name }}" class="user-avatar__img">
    @else
        {{ substr($review->user->name, 0, 1) }}
    @endif
</div>
```

- [ ] **Step 3: Update the SCSS**

In `Modules/CatalogDelivery/resources/assets/scss/app.scss`, ensure `.user-avatar` has `overflow: hidden` and add:

```scss
.user-avatar__img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
```

(If `.user-avatar` is currently just a text circle, give it `overflow: hidden;`.)

- [ ] **Step 4: Build + verify**

Run: `npm run build` — expected clean (ignore pre-existing `@source` warning).

- [ ] **Step 5: Commit**

```bash
git add Modules/CatalogDelivery/resources/views/ Modules/CatalogDelivery/resources/assets/scss/app.scss
git commit -m "feat: review avatars show uploaded profile photos with letter fallback"
```

---

### Task 9: Full verification and release

- [ ] **Step 1: Full test suite**

Run: `php artisan test`
Expected: all pass (28 existing + new: IdentitySignalsTest 3, ProfileTest +4, AdminProfileTest 2, PartnerProfileShowTest 2, LegalPagesTest 3 = 42 total, exact count may vary if factories diverge — just require 0 failures).

- [ ] **Step 2: Build**

Run: `npm run build`
Expected: clean build (pre-existing `@source` warning is fine).

- [ ] **Step 3: Zero-inline-style sweep**

Run: `grep -rn 'style="' Modules/*/resources/views/ resources/views/ --include="*.blade.php" | grep -v "emails\|@error"`
Expected: only email templates remain (they must keep inline styles for mail clients).

- [ ] **Step 4: HTTP smoke checks (server on :8001)**

Login as admin via curl (token + POST `/accessaccount`), then:
- `GET /profile` → 200, contains `profile-header`, `profile-timeline`
- `GET /admin/profile` → 200, contains `profile-stats`, admin nav Profile tab active
- Login as artisan (role partner) → `GET /partner/profile` → 200, contains `Atelier`, `profile-stats`
- `GET /privacy`, `/terms`, `/shipping`, `/returns` → 200, contain `legal-hero`
- `GET /` → footer contains the four legal links

- [ ] **Step 5: Avatar E2E via curl**

```bash
# login, then:
curl -s -b jar.txt -F "_token=$TOKEN" -F "avatar=@/path/to/test.jpg;type=image/jpeg" http://127.0.0.1:8001/profile/avatar
# verify: user row avatars != null; GET /profile renders the img
```

- [ ] **Step 6: Commit any leftovers**

```bash
git status --short
git add -A && git commit -m "chore: final verification for real profile pages + legal pages"
```

---

## Self-Review Notes

- Spec coverage: shell (Task 2), buyer (3), avatar+password (4), admin (5), partner (6), legal (7), review avatars (8), testing/verification (9). "Remove dead forgot-password link" lives in Task 4 Step 5. "storage:link" already done globally.
- Type consistency: `activityTimeline()` returns a Collection of arrays with keys `type/at/title/detail` — every blade uses `$event['title']` / `$event['detail']` / `$event['at']`. `avatarUrl()` returns `?string`. `memberTier()` / `isVerifiedMember()` return `string` / `bool`. All consumers match.
- No placeholders: all code, copy, and commands are written out.