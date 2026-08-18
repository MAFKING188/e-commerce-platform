# Profile Separation & Full-Detail Collection — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Split each actor profile into separate light pages (Overview / Orders / Address & Security / Settings) with real subnav tabs, and collect every person attribute — new `users.phone` column, status chip, member number, partner website/contact surfaced.

**Architecture:** The shared `profile-layout` shell gains a role-aware tabs partial (`partials/profile-tabs`), an identity details card (email, phone, status chip, member number), and responsive rules. Buyer/partner/admin overviews keep identity + stats + timeline only; orders live on their existing pages. New shared `profile.security` and `profile.settings` pages (auth-only, any role) hold the address/password and name/email/phone forms.

**Tech Stack:** Laravel 12, Blade components + partials, Tailwind v4/Vite, PHPUnit module suites, MySQL.

## Global Constraints

- Route names immutable: never rename existing names; only ADD `profile.security` and `profile.settings`.
- Zero SQL in Blade views; zero inline `style="..."` attributes (email templates excepted); no emojis in UI text (SVG icons allowed; `★` rating stars allowed).
- Profile shell styles live in `resources/css/app.css` (shared tokens) so they render on storefront AND console pages; `partner.css` console classes only on console pages.
- No new dependencies. `public/storage` symlink already exists.
- Every task ends with tests passing and a commit. TDD: write the failing test first.
- Baseline suite: 42 passed / 144 assertions (commit `4268fea`).

---

### Task 1: Data layer — phone column, status chip, member number

**Files:**
- Create: `Modules/IdentityAccess/database/migrations/2026_08_18_000003_add_phone_to_users_table.php`
- Modify: `Modules/IdentityAccess/app/Models/User.php`
- Test: `Modules/IdentityAccess/tests/Feature/UserDetailsTest.php` (new)

**Interfaces:**
- Produces:
  - `User::statusChip(): array` — `['label' => 'Active'|'Pending'|'Suspended', 'tone' => 'ok'|'warn'|'danger']`
  - `User::memberNumber(): string` — `'Member #' . str_pad(id, 6, '0', STR_PAD_LEFT)`
  - `User::$fillable` includes `'phone'`
  - DB column `users.phone` nullable string

- [ ] **Step 1: Write the failing test**

`Modules/IdentityAccess/tests/Feature/UserDetailsTest.php`:

```php
<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Modules\IdentityAccess\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Tests\TestCase;

class UserDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_chip_maps_all_statuses(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->assertSame(['label' => 'Active', 'tone' => 'ok'], $user->statusChip());

        $user->update(['status' => 'pending']);
        $this->assertSame(['label' => 'Pending', 'tone' => 'warn'], $user->fresh()->statusChip());

        $user->update(['status' => 'suspended']);
        $this->assertSame(['label' => 'Suspended', 'tone' => 'danger'], $user->fresh()->statusChip());
    }

    public function test_member_number_is_zero_padded_to_six_digits(): void
    {
        $user = User::factory()->create();
        $this->assertSame('Member #' . str_pad((string) $user->id, 6, '0', STR_PAD_LEFT), $user->memberNumber());
    }

    public function test_phone_is_fillable_and_saved(): void
    {
        $user = User::factory()->create();
        $user->update(['phone' => '+33 6 12 34 56 78']);
        $this->assertSame('+33 6 12 34 56 78', $user->fresh()->phone);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=UserDetailsTest`
Expected: FAIL — `Call to undefined method statusChip()` / missing column `phone`.

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
            $table->string('phone')->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
```

Run: `php artisan migrate --force`

- [ ] **Step 4: Add model methods + fillable**

In `Modules/IdentityAccess/app/Models/User.php`:
- Add `'phone'` to `$fillable`.
- Add below `avatarUrl()`:

```php
    public function statusChip(): array
    {
        return match ($this->status) {
            'pending' => ['label' => 'Pending', 'tone' => 'warn'],
            'suspended' => ['label' => 'Suspended', 'tone' => 'danger'],
            default => ['label' => 'Active', 'tone' => 'ok'],
        };
    }

    public function memberNumber(): string
    {
        return 'Member #' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }
```

- [ ] **Step 5: Run test to verify it passes**

Run: `php artisan test --filter=UserDetailsTest`
Expected: 3 passed.

- [ ] **Step 6: Commit**

```bash
git add Modules/IdentityAccess/database/migrations/2026_08_18_000003_add_phone_to_users_table.php Modules/IdentityAccess/app/Models/User.php Modules/IdentityAccess/tests/Feature/UserDetailsTest.php
git commit -m "feat: user phone column, status chip and member number helpers"
```

---

### Task 2: Profile shell — real tab links, identity card, responsive rules

**Files:**
- Modify: `resources/views/components/profile-layout.blade.php`
- Create: `resources/views/partials/profile-tabs.blade.php`
- Modify: `resources/css/app.css` (PROFILE SHELL block)

**Interfaces:**
- Consumes: `User::statusChip()`, `User::memberNumber()` (Task 1), routes `profile.security`, `profile.settings` (created in Task 3 — partial renders at request time only, safe)
- Produces:
  - `$subnav` items become `['href' => string, 'label' => string, 'active' => bool]`
  - `@include('partials.profile-tabs', ['active' => 'overview'|'orders'|'security'|'settings'|'dashboard'])` defines `$profileTabs` array for the current role
  - `$identity` prop (bool, default false) renders the identity details card
  - CSS classes: `.profile-subnav__link.is-active`, `.profile-identity__row`, `.profile-identity__label`, `.profile-identity__value`, `.profile-badge--status-ok|warn|danger`, `.profile-quicklinks`, `.profile-quicklinks__link`

- [ ] **Step 1: Create the tabs partial**

`resources/views/partials/profile-tabs.blade.php`:

```blade
@php
$profileTabs = match (auth()->user()->role) {
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
```

- [ ] **Step 2: Update the shell component**

In `resources/views/components/profile-layout.blade.php`:
- Add prop `'identity' => false`.
- Replace the subnav `@foreach` with:

```blade
    <nav class="profile-subnav" aria-label="Profile sections">
        @foreach ($subnav as $item)
            <a href="{{ $item['href'] }}" class="profile-subnav__link {{ $item['active'] ? 'is-active' : '' }}">{{ $item['label'] }}</a>
        @endforeach
    </nav>
```

- After the `</nav>`, insert the identity card (rendered only on overview pages):

```blade
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
```

- [ ] **Step 3: Add the CSS**

Append inside the PROFILE SHELL block in `resources/css/app.css` (after `.profile-subnav__link:hover`):

```css
.profile-subnav__link.is-active { color: var(--text-900); border-color: var(--brand-accent); }

.profile-badge--status-ok { background: var(--pc-ok-bg, #dcfce7); color: var(--pc-ok-fg, #166534); }
.profile-badge--status-warn { background: #fef3c7; color: #92400e; }
.profile-badge--status-danger { background: #fee2e2; color: #991b1b; }

.profile-identity__row {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 0.9rem 0;
    border-bottom: 1px solid var(--border);
}

.profile-identity__row:last-child { border-bottom: none; }

.profile-identity__label {
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-400);
}

.profile-identity__value { font-weight: 700; color: var(--text-900); }

.profile-quicklinks { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }

.profile-quicklinks__link {
    display: block;
    background: var(--surface-100);
    border: 1px solid var(--border);
    border-radius: 1.25rem;
    padding: 1.5rem;
    font-weight: 800;
    color: var(--text-900);
    text-decoration: none;
    transition: border-color 0.2s ease, transform 0.2s ease;
}

.profile-quicklinks__link:hover { border-color: var(--brand-accent); transform: translateY(-2px); }
```

And at the end of the PROFILE SHELL block (before the LEGAL PAGES block), add the responsive rules:

```css
@media (max-width: 768px) {
    .profile-header { flex-direction: column; text-align: center; padding: 2rem 1.5rem; }
    .profile-id__meta { justify-content: center; }
    .profile-subnav { overflow-x: auto; flex-wrap: nowrap; -webkit-overflow-scrolling: touch; }
    .profile-subnav__link { white-space: nowrap; }
    .profile-card { padding: 1.25rem; }
    .profile-identity__row { flex-direction: column; gap: 0.25rem; }
}
```

- [ ] **Step 4: Build + view clear**

Run: `npm run build` (ignore pre-existing `@source` warning) and `php artisan view:clear`.

- [ ] **Step 5: Commit**

```bash
git add resources/views/components/profile-layout.blade.php resources/views/partials/profile-tabs.blade.php resources/css/app.css
git commit -m "feat: profile shell — role-aware tab links, identity card, responsive rules"
```

---

### Task 3: Shared Address & Security + Settings pages

**Files:**
- Modify: `Modules/IdentityAccess/routes/web.php`
- Modify: `Modules/IdentityAccess/app/Http/Controllers/UserController.php` (`security()`, `settings()`, `updateProfile()` phone)
- Create: `Modules/IdentityAccess/resources/views/users/security.blade.php`
- Create: `Modules/IdentityAccess/resources/views/users/settings.blade.php`
- Test: `Modules/IdentityAccess/tests/Feature/ProfileTest.php` (extend)

**Interfaces:**
- Consumes: `partials/profile-tabs` (Task 2), shell
- Produces:
  - `GET /profile/security` → name `profile.security`, `UserController::security(): View` with `$user`, `$address`
  - `GET /profile/settings` → name `profile.settings`, `UserController::settings(): View` with `$user`
  - `updateProfile()` accepts and persists `phone` (nullable|string|max:30)
  - Both pages render for ANY authenticated role (buyer, admin, partner)

- [ ] **Step 1: Write the failing tests**

Append to `Modules/IdentityAccess/tests/Feature/ProfileTest.php`:

```php
    public function test_security_page_renders_address_and_password_forms_for_all_roles(): void
    {
        foreach (['user', 'admin', 'partner'] as $role) {
            $user = User::factory()->create(['role' => $role, 'status' => 'active']);
            $response = $this->actingAs($user)->get('/profile/security');
            $response->assertOk()
                ->assertSee('Street Address')
                ->assertSee('Current Password')
                ->assertSee('Address & Security');
        }
    }

    public function test_settings_page_renders_and_phone_is_saved_via_profile_update(): void
    {
        $user = User::factory()->create(['role' => 'partner', 'status' => 'active']);

        $this->actingAs($user)->get('/profile/settings')
            ->assertOk()
            ->assertSee('Full Name')
            ->assertSee('Email Address')
            ->assertSee('Phone');

        $this->actingAs($user)->put('/profile/update', [
            'name' => $user->name,
            'email' => $user->email,
            'phone' => '+33 6 11 22 33 44',
        ])->assertRedirect();

        $this->assertSame('+33 6 11 22 33 44', $user->fresh()->phone);
    }

    public function test_security_and_settings_require_authentication(): void
    {
        $this->get('/profile/security')->assertRedirect('/login');
        $this->get('/profile/settings')->assertRedirect('/login');
    }
```

- [ ] **Step 2: Run to verify they fail**

Run: `php artisan test --filter=ProfileTest`
Expected: FAIL — routes 404 / phone not saved.

- [ ] **Step 3: Add routes**

In `Modules/IdentityAccess/routes/web.php` inside the `auth` group after the password route:

```php
    Route::get('/profile/security', [UserController::class, 'security'])->name('profile.security');
    Route::get('/profile/settings', [UserController::class, 'settings'])->name('profile.settings');
```

- [ ] **Step 4: Add controller methods + phone validation**

In `UserController.php`:
- In `updateProfile()`, add `'phone' => 'nullable|string|max:30',` to the validation array and change the update call to `$user->update($request->only(['name', 'email', 'phone']));`
- Add:

```php
    public function security()
    {
        $user = Auth::user()->load('addresses');
        $address = $user->addresses->firstWhere('is_primary', true) ?? $user->addresses->first();

        return view('identityaccess::users.security', compact('user', 'address'));
    }

    public function settings()
    {
        return view('identityaccess::users.settings', ['user' => Auth::user()]);
    }
```

- [ ] **Step 5: Create the security blade**

`Modules/IdentityAccess/resources/views/users/security.blade.php`:

```blade
@section('title', 'Address & Security | LUWI')

<x-app-layout>
@include('partials.profile-tabs', ['active' => 'security'])

<x-profile-layout :user="$user" :subnav="$profileTabs">

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
                <button type="submit" class="btn btn-primary">Update Password</button>
            </form>
        </div>
    </section>

</x-profile-layout>
</x-app-layout>
```

- [ ] **Step 6: Create the settings blade**

`Modules/IdentityAccess/resources/views/users/settings.blade.php`:

```blade
@section('title', 'Settings | LUWI')

<x-app-layout>
@include('partials.profile-tabs', ['active' => 'settings'])

<x-profile-layout :user="$user" :subnav="$profileTabs">

    <section class="profile-section">
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
                <div class="form-group">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-input" value="{{ $user->phone }}" placeholder="+33 6 12 34 56 78">
                </div>
                <button type="submit" class="btn btn-primary">Save Account Details</button>
            </form>
        </div>
    </section>

</x-profile-layout>
</x-app-layout>
```

- [ ] **Step 7: Run tests**

Run: `php artisan test --filter=ProfileTest`
Expected: all pass (existing 6 + 3 new = 9 tests in file).

- [ ] **Step 8: Commit**

```bash
git add Modules/IdentityAccess/routes/web.php Modules/IdentityAccess/app/Http/Controllers/UserController.php Modules/IdentityAccess/resources/views/users/security.blade.php Modules/IdentityAccess/resources/views/users/settings.blade.php Modules/IdentityAccess/tests/Feature/ProfileTest.php
git commit -m "feat: shared address & security and settings pages with phone collection"
```

---

### Task 4: Buyer overview — identity, timeline, quick links, no orders

**Files:**
- Modify: `Modules/IdentityAccess/app/Http/Controllers/UserController.php` (`show()`)
- Rewrite: `Modules/IdentityAccess/resources/views/users/show.blade.php`
- Test: `Modules/IdentityAccess/tests/Feature/ProfileTest.php` (update overview test)

**Interfaces:**
- Consumes: `partials/profile-tabs` with `$active = 'overview'`, `User::statusChip()`, `User::memberNumber()`
- Produces: `UserController::show()` passes `$user`, `$stats`, `$timeline` (no `$address`, no `$recentOrders`); overview shows quick links to `orders.index`, `profile.wishlist`, `profile.security`, `profile.settings`

- [ ] **Step 1: Update the failing test**

Replace `test_profile_page_renders_identity_signals_and_stats` in `Modules/IdentityAccess/tests/Feature/ProfileTest.php` with:

```php
    public function test_profile_page_renders_identity_signals_stats_and_quick_links(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $product = Product::factory()->create(['stock' => 5]);
        \Modules\MarketplacePipeline\Models\Order::create(['user_id' => $user->id, 'total_price' => 620, 'status' => 'completed']);
        Address::create(['user_id' => $user->id, 'is_primary' => true, 'line1' => 'A', 'city' => 'B', 'country' => 'C']);
        Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk()
            ->assertSee('Collector', false)
            ->assertSee('profile-stats')
            ->assertSee('profile-timeline')
            ->assertSee('Identity')
            ->assertSee('Member #')
            ->assertSee('/orders')
            ->assertSee('/profile/security')
            ->assertSee('/profile/settings')
            ->assertDontSee('Order History')
            ->assertDontSee('No acquisitions recorded yet.');
    }
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --filter=ProfileTest`
Expected: FAIL — old overview still shows `Order History` and lacks the identity card/links.

- [ ] **Step 3: Rework `show()`**

Replace the `show()` method in `Modules/IdentityAccess/app/Http/Controllers/UserController.php`:

```php
    public function show()
    {
        $user = Auth::user()->load(['orders', 'addresses', 'reviews', 'wishlists']);

        $stats = [
            'Orders placed' => $user->orders->count(),
            'Total spent' => '$' . number_format($user->orders->where('status', 'completed')->sum('total_price'), 0),
            'Archived pieces' => $user->wishlists->count(),
        ];

        return view('identityaccess::users.show', [
            'user' => $user,
            'stats' => $stats,
            'timeline' => $user->activityTimeline(8),
        ]);
    }
```

- [ ] **Step 4: Rewrite the overview blade**

`Modules/IdentityAccess/resources/views/users/show.blade.php` (full replacement):

```blade
@section('title', 'Member Profile | LUWI')

<x-app-layout>
@include('partials.profile-tabs', ['active' => 'overview'])

<x-profile-layout :user="$user" :stats="$stats" :subnav="$profileTabs" identity>

    <section class="profile-section">
        <h2 class="pc-card__title">Activity</h2>
        @if ($timeline->isEmpty())
            <div class="profile-card">
                <p class="profile-empty">No activity yet — your journey begins with the first piece you collect.</p>
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

    <section class="profile-section">
        <h2 class="pc-card__title">Quick Links</h2>
        <div class="profile-quicklinks">
            <a href="{{ route('orders.index') }}" class="profile-quicklinks__link">Order History</a>
            <a href="{{ route('profile.wishlist') }}" class="profile-quicklinks__link">My Archive</a>
            <a href="{{ route('profile.security') }}" class="profile-quicklinks__link">Address & Security</a>
            <a href="{{ route('profile.settings') }}" class="profile-quicklinks__link">Settings</a>
        </div>
    </section>

</x-profile-layout>
</x-app-layout>
```

- [ ] **Step 5: Run tests**

Run: `php artisan test --filter=ProfileTest`
Expected: all pass.

- [ ] **Step 6: Commit**

```bash
git add Modules/IdentityAccess/app/Http/Controllers/UserController.php Modules/IdentityAccess/resources/views/users/show.blade.php Modules/IdentityAccess/tests/Feature/ProfileTest.php
git commit -m "feat: buyer overview — identity card, timeline and quick links, orders moved out"
```

---
### Task 5: Admin overview — pulse stats, timeline, no orders table

**Files:**
- Modify: `Modules/IdentityAccess/app/Http/Controllers/AdminProfileController.php`
- Rewrite: `Modules/IdentityAccess/resources/views/admin/profile.blade.php`
- Test: `Modules/IdentityAccess/tests/Feature/AdminProfileTest.php` (update)

**Interfaces:**
- Consumes: `partials/profile-tabs` with `$active = 'overview'`
- Produces: `AdminProfileController::index()` passes `$user`, `$stats` (Revenue / Active orders / Members / Pending reviews), `$timeline` — NO `$recentOrders`; blade has NO acquisitions table

- [ ] **Step 1: Update the failing test**

Replace `test_admin_profile_page_renders_for_admin` in `Modules/IdentityAccess/tests/Feature/AdminProfileTest.php`:

```php
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
            ->assertSee('profile-stats')
            ->assertSee('Identity')
            ->assertSee('Member #')
            ->assertDontSee('Recent Acquisitions');
    }
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --filter=AdminProfileTest`
Expected: FAIL — `Recent Acquisitions` still present.

- [ ] **Step 3: Rework the controller**

Replace the `index()` method in `Modules/IdentityAccess/app/Http/Controllers/AdminProfileController.php`:

```php
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
        ]);
    }
```

- [ ] **Step 4: Rewrite the admin blade**

`Modules/IdentityAccess/resources/views/admin/profile.blade.php` (full replacement):

```blade
@section('title', 'Admin Profile | Command Center')

<x-app-layout>
@include('partials.admin-nav')

@include('partials.profile-tabs', ['active' => 'overview'])

<x-profile-layout :user="$user" :stats="$stats" :subnav="$profileTabs" identity>

    <section class="profile-section">
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

    <section class="profile-section">
        <h2 class="pc-card__title">Command Center</h2>
        <div class="profile-card">
            <p class="pc-subtitle">Orders, payouts, reviews and members live in the Command Center.</p>
            <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost">Open Command Center</a>
        </div>
    </section>

</x-profile-layout>
</x-app-layout>
```

- [ ] **Step 5: Run tests**

Run: `php artisan test --filter=AdminProfileTest`
Expected: 2 passed.

- [ ] **Step 6: Commit**

```bash
git add Modules/IdentityAccess/app/Http/Controllers/AdminProfileController.php Modules/IdentityAccess/resources/views/admin/profile.blade.php Modules/IdentityAccess/tests/Feature/AdminProfileTest.php
git commit -m "feat: admin overview — pulse stats and timeline, acquisitions table removed"
```

---

### Task 6: Partner overview — business card, stats, no orders list

**Files:**
- Modify: `Modules/PartnerHub/app/Http/Controllers/PartnerProfileController.php` (`profile()`)
- Rewrite: `Modules/PartnerHub/resources/views/partner/profile/show.blade.php`
- Test: `Modules/PartnerHub/tests/Feature/PartnerProfileShowTest.php` (update)

**Interfaces:**
- Consumes: `partials/profile-tabs` with `$active = 'overview'`
- Produces: `PartnerProfileController::profile()` passes `$user`, `$partner` (with `website`, `contact_info`), `$stats`, `$timeline` — NO `$recentOrders`; business card shows name, description, website link, contact info

- [ ] **Step 1: Update the failing test**

Replace `test_partner_profile_view_renders_with_stats` in `Modules/PartnerHub/tests/Feature/PartnerProfileShowTest.php`:

```php
    public function test_partner_profile_view_renders_with_stats(): void
    {
        $user = User::factory()->create(['role' => 'partner', 'status' => 'active']);
        $partner = Partner::create([
            'user_id' => $user->id,
            'name' => 'Atelier Test',
            'description' => 'Handcrafted pieces',
            'contact_info' => 'contact@test.com',
            'website' => 'https://atelier-test.example',
        ]);
        $product = Product::factory()->create(['stock' => 5]);
        $partner->products()->attach($product->id);

        $response = $this->actingAs($user)->get('/partner/profile');

        $response->assertOk()
            ->assertSee('Atelier Test')
            ->assertSee('profile-stats')
            ->assertSee('artisan-profile')
            ->assertSee('profile-header')
            ->assertSee('contact@test.com')
            ->assertSee('atelier-test.example')
            ->assertSee('Identity')
            ->assertDontSee('My Orders');
    }
```

- [ ] **Step 2: Run to verify it fails**

Run: `php artisan test --filter=PartnerProfileShowTest`
Expected: FAIL — website/contact missing, `My Orders` still present.

- [ ] **Step 3: Rework the controller method**

Replace the `profile()` method in `Modules/PartnerHub/app/Http/Controllers/PartnerProfileController.php`:

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
        ]);
    }
```

- [ ] **Step 4: Rewrite the partner blade**

`Modules/PartnerHub/resources/views/partner/profile/show.blade.php` (full replacement):

```blade
@section('title', 'My Profile | Partner Dashboard')

@section('scripts')
@vite('resources/js/partner.js')
@endsection

<x-app-layout>
@include('partials.partner-nav')

@include('partials.profile-tabs', ['active' => 'overview'])

<x-profile-layout :user="$user" :stats="$stats" :subnav="$profileTabs" identity>

    <section class="profile-section">
        <h2 class="pc-card__title">Atelier</h2>
        <div class="pc-stack">
            <div class="pc-card">
                <div class="pc-card__head">
                    <div>
                        <h2 class="pc-card__title">{{ $partner->name }}</h2>
                        <p class="pc-subtitle">{{ $partner->description }}</p>
                    </div>
                    <a href="{{ route('partner.profile', $partner->id) }}" class="pc-btn-sm">View Public Profile</a>
                </div>
            </div>
            @if ($partner->website || $partner->contact_info)
                <div class="profile-card">
                    @if ($partner->website)
                        <div class="profile-identity__row">
                            <span class="profile-identity__label">Website</span>
                            <span class="profile-identity__value"><a href="{{ $partner->website }}" target="_blank" rel="noopener">{{ $partner->website }}</a></span>
                        </div>
                    @endif
                    @if ($partner->contact_info)
                        <div class="profile-identity__row">
                            <span class="profile-identity__label">Contact</span>
                            <span class="profile-identity__value">{{ $partner->contact_info }}</span>
                        </div>
                    @endif
                </div>
            @endif
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
        </div>
    </section>

</x-profile-layout>
</x-app-layout>
```

- [ ] **Step 5: Run tests**

Run: `php artisan test --filter=PartnerProfileShowTest`
Expected: 2 passed.

- [ ] **Step 6: Commit**

```bash
git add Modules/PartnerHub/app/Http/Controllers/PartnerProfileController.php Modules/PartnerHub/resources/views/partner/profile/show.blade.php Modules/PartnerHub/tests/Feature/PartnerProfileShowTest.php
git commit -m "feat: partner overview — full atelier business card and stats, orders moved out"
```

---

### Task 7: Full verification and release

- [ ] **Step 1: Full test suite**

Run: `php artisan test`
Expected: all pass (42 baseline + UserDetailsTest 3 + ProfileTest +3 (replaced overview test keeps count, +2 net: security/settings/auth) + AdminProfileTest 0 net + PartnerProfileShowTest 0 net ≈ 47 tests / 0 failures — exact count may vary; just require 0 failures).

- [ ] **Step 2: Build**

Run: `npm run build`
Expected: clean build (pre-existing `@source` warning is fine).

- [ ] **Step 3: Zero-inline-style sweep**

Run: `grep -rn 'style="' Modules/*/resources/views/ resources/views/ --include="*.blade.php" | grep -v emails`
Expected: only email templates remain.

- [ ] **Step 4: HTTP smoke checks (server on :8001)**

Login as admin via curl (token + POST `/accessaccount`), then:
- `GET /profile` → 200, contains `Identity`, `Member #`, quick links, NO `Order History` list
- `GET /profile/security` → 200, contains address + password forms, tabs with `Address & Security` active (`is-active`)
- `GET /profile/settings` → 200, contains Phone field
- `GET /admin/profile` → 200, contains `Platform Pulse`, NO `Recent Acquisitions`
- Login as artisan (role partner) → `GET /partner/profile` → 200, contains atelier website/contact, NO `My Orders` list
- `GET /` → 200 footer intact

- [ ] **Step 5: Commit any leftovers**

```bash
git status --short
git add -A && git commit -m "chore: final verification for profile separation and full-detail collection"
```

---

## Self-Review Notes

- Spec coverage: phone (Task 1), status chip + member number (Task 1), shell tabs + identity card + responsive (Task 2), security/settings pages (Task 3), buyer overview (Task 4), admin overview strip orders (Task 5), partner business card (Task 6), verification (Task 7).
- Type consistency: `$subnav` items always `['href', 'label', 'active']`; `$profileTabs` defined by the partial; `statusChip()` returns `['label','tone']`; `memberNumber()` returns string; `show()`/`index()`/`profile()` all pass `$user, $stats, $timeline` (admin adds nothing else, partner adds `$partner`).
- No placeholders: all code, copy, and commands written out.
