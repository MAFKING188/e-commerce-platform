# Catalog Images, Collection Catalog, Mobile Filter + Sweep Fixes — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make the SmartShop catalog visually coherent (curated product images), turn `/collection` into a real browsable catalog distinct from the homepage, fix the mobile filter drawer, and close the eight sweep defects (E1–E8) from `docs/AUDIT-2026-08-20-full-app-sweep.md`.

**Architecture:** Single source of truth `CatalogInventory::IMAGES` (name → verified Unsplash URL) shared by `ProductSeeder` and a live-DB migration; collection page driven by a new `CatalogQueryService::collection()`; auth fixes in middleware + Google controller; FK-safe delete handling via try/catch; form-level "send code" actions on settings/security screens.

**Tech Stack:** Laravel 12 modules (IdentityAccess, CatalogDelivery, MarketplacePipeline, PartnerHub), SCSS via Vite, PHPUnit, SQLite test DB.

## Global Constraints

- Conventional commits; targeted `git add <paths>` (never `git add .`).
- Route names are immutable — only ADD new routes, never rename existing ones.
- No inline `style=` attributes in blades.
- Local `.env` keeps `QUEUE_CONNECTION=sync` (no local queue worker).
- Full suite must stay green: `php artisan test` (currently 113 tests / 401 assertions).
- Module tests extend `Tests\TestCase`; DB-dependent tests use `Illuminate\Foundation\Testing\RefreshDatabase`.
- Every new/changed Unsplash image URL MUST be verified reachable before commit: `curl -sfL -o /dev/null -w "%{http_code}\n" "<url>"` must print `200`.
- Deploy: `git push` → `ssh root@104.248.163.215 "cd /var/www/smartshop && git pull -q && php artisan migrate --force && php artisan route:clear -q && php artisan config:clear -q && php artisan queue:restart"`.
- Multi-vendor marketplace: category names stay generic; nobody may rename categories.

---

### Task 1: Curated product images (spec §A)

**Files:**
- Modify: `Modules/CatalogDelivery/database/seeders/CatalogInventory.php` (replace `IMAGES` + index arrays with a `name => url` map)
- Modify: `Modules/CatalogDelivery/database/seeders/ProductSeeder.php:26-33`
- Create: `Modules/CatalogDelivery/database/migrations/2026_08_20_180001_curated_product_images.php`
- Modify: `Modules/CatalogDelivery/tests/Feature/CatalogCoherenceTest.php`
- Create: `/tmp/opencode/verify_images.sh` (verification helper, not committed)

**Interfaces:**
- Consumes: existing `CatalogInventory::CATALOG` structure (category => `[name, imageIdx]`); the migration `2026_08_19_170001_coherent_catalog` already ran.
- Produces: `CatalogInventory::IMAGES` as `array<string, string>` (product name → `https://images.unsplash.com/<id>?auto=format&fit=crop&w=800&q=80`); `CatalogInventory::imageFor(string $name): ?string`.

- [ ] **Step 1: Write the failing tests**

Add to `CatalogCoherenceTest.php`:

```php
public function test_every_product_has_an_image(): void
{
    foreach (Product::with('images')->get() as $product) {
        $this->assertNotEmpty($product->images, "No image for: {$product->name}");
    }
}

public function test_no_image_is_shared_across_categories(): void
{
    $byUrl = Product::with(['category', 'images'])->get()
        ->flatMap(fn ($p) => $p->images->map(fn ($img) => [$p->category->name, $img->url]))
        ->groupBy(fn ($row) => $row[1]);

    foreach ($byUrl as $url => $rows) {
        $this->assertSame(1, $rows->pluck(0)->unique()->count(), "Image shared across categories: {$url}");
    }
}

public function test_images_point_to_verified_unsplash_urls(): void
{
    foreach (Product::with('images')->get() as $product) {
        foreach ($product->images as $img) {
            $this->assertStringStartsWith('https://images.unsplash.com/', $img->url, "Non-Unsplash image: {$product->name}");
        }
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test Modules/CatalogDelivery/tests/Feature/CatalogCoherenceTest.php`
Expected: FAIL — products still share the 18-photo pool across categories; `#71/#73-76` have no images.

- [ ] **Step 3: Source and verify the curated photo set**

For each of the 105 product names, find an Unsplash photo ID that plausibly depicts it. Method:
1. Browse `https://unsplash.com/s/photos/<keyword>` for each product type (agent-browser) and collect candidate `photo-<id>` values from image URLs.
2. Build the map in `CatalogInventory.php` as `IMAGES = ['Aether Pro Laptop' => 'photo-...', ...]` (full `https://images.unsplash.com/...?...` URLs — see Produces).
3. Same photo MAY be reused only within the same category and only when no distinct photo exists for a product (e.g. several book titles may share a generic book photo). Cross-category reuse is forbidden (test enforces it).
4. Verify EVERY URL:

```bash
cat > /tmp/opencode/verify_images.sh << 'EOF'
#!/usr/bin/env bash
while IFS= read -r u; do
  code=$(curl -sfL -o /dev/null -w "%{http_code}" --max-time 15 "$u") || code=FAIL
  [ "$code" != "200" ] && echo "$code  $u"
done < /tmp/opencode/urls.txt
echo DONE
EOF
chmod +x /tmp/opencode/verify_images.sh
php -r '$s=file_get_contents("Modules/CatalogDelivery/database/seeders/CatalogInventory.php"); preg_match_all("#https://images\.unsplash\.com/[^\"\x27]+#", $s, $m); file_put_contents("/tmp/opencode/urls.txt", implode("\n", array_unique($m[0])));'
/tmp/opencode/verify_images.sh
```

Expected: `DONE` with zero lines above it. Re-source any URL that fails.

- [ ] **Step 4: Rewrite `CatalogInventory::IMAGES` and helper**

In `Modules/CatalogDelivery/database/seeders/CatalogInventory.php`, replace the `IMAGES` const with:

```php
public const IMAGES = [
    // 'Product Name' => 'https://images.unsplash.com/photo-<verified-id>?auto=format&fit=crop&w=800&q=80',
    // ...one entry per product name in CATALOG, all URLs curl-verified 200 (see Global Constraints).
];

public static function imageFor(string $name): ?string
{
    return self::IMAGES[$name] ?? null;
}
```

The `CATALOG` arrays keep their shape (`[name, imageIdx]`) but the index is now ignored by the seeder/migration — the map is keyed by exact product name (matches `CATALOG` + the renames from the coherent-catalog migration).

- [ ] **Step 5: Update `ProductSeeder`**

Replace the image creation block in `ProductSeeder.php`:

```php
    ProductImage::create([
        'product_id' => $product->id,
        'url' => CatalogInventory::imageFor($name)
            ?? "https://picsum.photos/seed/" . \Illuminate\Support\Str::slug($name) . "/800/600",
    ]);
```

- [ ] **Step 6: Create the live-DB migration**

`Modules/CatalogDelivery/database/migrations/2026_08_20_180001_curated_product_images.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\CatalogDelivery\Database\Seeders\CatalogInventory;

return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('products')->exists()) {
            return;
        }

        DB::table('products')->orderBy('id')->get()->each(function ($product) {
            $url = CatalogInventory::imageFor($product->name)
                ?? "https://picsum.photos/seed/" . \Illuminate\Support\Str::slug($product->name) . "/800/600";

            $existing = DB::table('product_images')->where('product_id', $product->id)->first();
            if ($existing) {
                DB::table('product_images')->where('id', $existing->id)->update(['url' => $url]);
            } else {
                DB::table('product_images')->insert([
                    'product_id' => $product->id,
                    'url' => $url,
                    'position' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });
    }

    public function down(): void
    {
    }
};
```

(Confirm the `product_images` column names match `2026_04_18_*` schema: `product_id`, `url`, `position`, timestamps — check the migration before running.)

- [ ] **Step 7: Run migrate + full test suite**

Run: `php artisan migrate && php artisan test Modules/CatalogDelivery/tests/Feature/CatalogCoherenceTest.php`
Expected: all PASS.
Run: `php artisan test`
Expected: 116+ tests, 0 failures (existing 113 + 3 new).

- [ ] **Step 8: Commit**

```bash
git add Modules/CatalogDelivery/database/seeders/CatalogInventory.php Modules/CatalogDelivery/database/seeders/ProductSeeder.php Modules/CatalogDelivery/database/migrations/2026_08_20_180001_curated_product_images.php Modules/CatalogDelivery/tests/Feature/CatalogCoherenceTest.php
git commit -m "feat(catalog): curated per-product images (verified Unsplash map + migration)"
```

---

### Task 2: Collection = full browsable catalog (spec §C)

**Files:**
- Modify: `Modules/CatalogDelivery/app/Services/CatalogQueryService.php`
- Modify: `Modules/CatalogDelivery/app/Http/Controllers/ViewController.php:24-27`
- Modify: `Modules/CatalogDelivery/resources/views/collection.blade.php` (full rewrite)
- Modify: `Modules/CatalogDelivery/resources/views/home.blade.php:21,34` (add section ids)
- Modify: `resources/views/components/app-layout.blade.php:156-157` (footer anchors)
- Modify: `Modules/CatalogDelivery/tests/Feature/CollectionPageTest.php`

**Interfaces:**
- Produces: `CatalogQueryService::collection(): array` → `['categories' => \Illuminate\Database\Eloquent\Collection<Category>]` where each `Category` has `products` loaded (eager `images`, `partners`, newest first).

- [ ] **Step 1: Write the failing tests**

In `CollectionPageTest.php`, replace `test_collection_page_renders_sections` and add:

```php
public function test_collection_page_lists_every_category_as_a_section(): void
{
    $this->get('/collection')
        ->assertOk()
        ->assertSee('id="electronics"', false)
        ->assertSee('id="beauty-wellness"', false)
        ->assertSee('Beauty &amp; Wellness', false);
}

public function test_collection_shows_all_products(): void
{
    Product::factory()->count(12)->create();

    $response = $this->get('/collection');
    $response->assertOk();
    foreach (Product::pluck('name')->all() as $name) {
        $this->assertStringContainsString(e($name), $response->getContent());
    }
}

public function test_footer_links_point_to_homepage_sections(): void
{
    $this->get('/')
        ->assertSee('href="' . route('home') . '#new-arrivals"', false)
        ->assertSee('href="' . route('home') . '#editor-choice"', false);
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php artisan test Modules/CatalogDelivery/tests/Feature/CollectionPageTest.php`
Expected: FAIL (`/collection` has no category sections; footer still points at `collection#...`).

- [ ] **Step 3: Add `collection()` to the query service**

In `CatalogQueryService.php` add:

```php
public function collection(): array
{
    $categories = Category::with(['products' => fn ($q) => $q->with(['images', 'partners'])->latest()])
        ->orderBy('name')
        ->get();

    return compact('categories');
}
```

- [ ] **Step 4: Point the controller at it**

`ViewController::collection()` becomes:

```php
public function collection(CatalogQueryService $catalog)
{
    return view('catalogdelivery::collection', $catalog->collection());
}
```

- [ ] **Step 5: Rewrite the collection view**

`Modules/CatalogDelivery/resources/views/collection.blade.php`:

```blade
@section('title', 'The Collection | SmartShop')

<x-app-layout>

<section class="hero-luxury">
    <img src="https://picsum.photos/id/1027/2000/1200" class="hero-image-bg" alt="">
    <div class="hero-overlay">
        <span class="home-eyebrow">The LUWI Collection</span>
        <h1>The Curated<br>Collection.</h1>
        <p class="home-hero-sub">
            Every piece, every artisan. Browse the full marketplace catalog by category.
        </p>
        <div class="home-hero-actions">
            <a href="{{ route('shop') }}" class="btn btn-primary home-btn-solid">Filter Everything</a>
            <a href="#electronics" class="btn btn-ghost home-btn-outline">Start Browsing</a>
        </div>
    </div>
</section>

<section class="luxury-section home-section-spaced">
    <div class="collection-jump">
        @foreach($categories as $category)
            <a href="#{{ \Illuminate\Support\Str::slug($category->name) }}" class="btn btn-ghost collection-jump-link">{{ $category->name }}</a>
        @endforeach
    </div>
</section>

@foreach($categories as $category)
    <section id="{{ \Illuminate\Support\Str::slug($category->name) }}" class="luxury-section home-section-spaced">
        <div class="home-section-head">
            <span class="home-eyebrow-sm">{{ $category->products->count() }} pieces</span>
            <h2 class="home-section-title">{{ $category->name }}.</h2>
        </div>

        <div class="collection-grid">
            @foreach($category->products as $product)
                @include('catalogdelivery::components.product-card', ['product' => $product])
            @endforeach
        </div>
    </section>
@endforeach

</x-app-layout>
```

- [ ] **Step 6: Home section ids + footer anchors**

`home.blade.php:21` → `<section id="editor-choice" class="luxury-section home-section-spaced">` and `:34` → `<section id="new-arrivals" class="luxury-section">`.

`app-layout.blade.php:156-157`:

```blade
<li><a href="{{ route('home') }}#new-arrivals">New Arrivals</a></li>
<li><a href="{{ route('home') }}#editor-choice">Featured</a></li>
```

- [ ] **Step 7: Add jump-nav styles to `app.scss`**

Append to `Modules/CatalogDelivery/resources/assets/scss/app.scss` (after the `.shop-filter-btn` block):

```scss
.collection-jump {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    justify-content: center;
}

.collection-jump-link {
    border-radius: 99px;
    padding: 0.75rem 1.5rem;
}
```

- [ ] **Step 8: Run the tests**

Run: `php artisan test Modules/CatalogDelivery/tests/Feature/CollectionPageTest.php && php artisan test`
Expected: all PASS, full suite green.

- [ ] **Step 9: Commit**

```bash
git add Modules/CatalogDelivery/app/Services/CatalogQueryService.php Modules/CatalogDelivery/app/Http/Controllers/ViewController.php Modules/CatalogDelivery/resources/views/collection.blade.php Modules/CatalogDelivery/resources/views/home.blade.php Modules/CatalogDelivery/resources/assets/scss/app.scss resources/views/components/app-layout.blade.php Modules/CatalogDelivery/tests/Feature/CollectionPageTest.php
git commit -m "feat(catalog): /collection is now the full browsable catalog with category sections"
```

---

### Task 3: Mobile filter drawer fix (spec §D)

**Files:**
- Modify: `Modules/CatalogDelivery/resources/assets/scss/app.scss:162-204,235-242`

**Interfaces:** none (pure CSS).

- [ ] **Step 1: Apply the CSS fix**

In `app.scss`, `.filter-drawer` gains `height: 100dvh` (after `height: 100vh`), `overflow-y: auto`:

```scss
.filter-drawer {
    position: fixed;
    top: 0;
    right: 0;
    width: 400px;
    height: 100vh;
    height: 100dvh;
    overflow-y: auto;
    background: var(--nav-bg);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border-left: 1px solid var(--border);
    z-index: 2000;
    padding: 3rem;
    transform: translateX(100%);
    transition: transform 0.5s cubic-bezier(0.16, 1, 0.3, 1);
    display: flex;
    flex-direction: column;
    gap: 2rem;
}
```

Replace the 768px media query line `.filter-drawer { width: 100%; }` with:

```scss
.filter-drawer {
    width: 100%;
    padding: 1.5rem;
    gap: 1.25rem;
    padding-bottom: calc(1.5rem + env(safe-area-inset-bottom));
}
.filter-form { gap: 1.25rem; }
```

- [ ] **Step 2: Build assets + verify in a phone viewport**

Run: `npm run build`
Then with agent-browser at a 390×844 viewport: open `http://127.0.0.1:8001/shop`, tap the Filter button, scroll the drawer to the bottom, confirm the **Apply Filter** button is visible and tappable, tap it, confirm the page navigates with the filter applied.

- [ ] **Step 3: Run the suite + commit**

Run: `php artisan test` — green.
```bash
git add Modules/CatalogDelivery/resources/assets/scss/app.scss
git commit -m "fix(catalog): filter drawer scrolls on mobile — Apply Filter reachable (100dvh + overflow)"
```
---

### Task 4: E1 — middleware enforces active status

**Files:**
- Modify: `Modules/IdentityAccess/app/Http/Middleware/AdminMiddleware.php:18-22`
- Modify: `Modules/IdentityAccess/app/Http/Middleware/PartnerMiddleware.php:18-23`
- Create: `Modules/IdentityAccess/tests/Feature/StatusGateMiddlewareTest.php`

**Interfaces:** none (behavior change).

- [ ] **Step 1: Write the failing test**

`Modules/IdentityAccess/tests/Feature/StatusGateMiddlewareTest.php`:

```php
<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Models\User;
use Tests\TestCase;

class StatusGateMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $status): User
    {
        return User::create([
            'name' => $role . ' ' . $status,
            'email' => $role . '-' . $status . '@test.com',
            'password' => bcrypt('password'),
            'role' => $role,
            'status' => $status,
        ]);
    }

    public function test_pending_admin_is_denied_admin_routes(): void
    {
        $this->actingAs($this->makeUser('admin', 'pending'))
            ->get('/admin/dashboard')
            ->assertRedirect();
    }

    public function test_suspended_admin_is_denied_admin_routes(): void
    {
        $this->actingAs($this->makeUser('admin', 'suspended'))
            ->get('/admin/dashboard')
            ->assertRedirect();
    }

    public function test_active_admin_reaches_admin_routes(): void
    {
        $this->actingAs($this->makeUser('admin', 'active'))
            ->get('/admin/dashboard')
            ->assertOk();
    }

    public function test_pending_partner_is_denied_partner_routes(): void
    {
        $this->actingAs($this->makeUser('partner', 'pending'))
            ->get('/partner/dashboard')
            ->assertRedirect();
    }
}
```

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/StatusGateMiddlewareTest.php`
Expected: FAIL — pending/suspended admins currently pass.

- [ ] **Step 3: Fix both middleware**

`AdminMiddleware::handle` body:

```php
if (auth()->user() && auth()->user()->role === 'admin' && auth()->user()->status === 'active') {
    return $next($request);
}
return redirect()->route('home')->with('error', 'Access denied');
```

`PartnerMiddleware::handle` body (same pattern, `role === 'partner'`):

```php
if (auth()->user() && auth()->user()->role === 'partner' && auth()->user()->status === 'active') {
    return $next($request);
}
return redirect()->route('home')->with('error', 'Access denied');
```

- [ ] **Step 4: Run tests + commit**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/StatusGateMiddlewareTest.php && php artisan test`
Expected: all PASS.
```bash
git add Modules/IdentityAccess/app/Http/Middleware/AdminMiddleware.php Modules/IdentityAccess/app/Http/Middleware/PartnerMiddleware.php Modules/IdentityAccess/tests/Feature/StatusGateMiddlewareTest.php
git commit -m "fix(identity): admin/partner middleware requires active status — closes role-escalation hole"
```

---

### Task 5: E2 — Google login status check + challenge alignment

**Files:**
- Modify: `Modules/IdentityAccess/app/Http/Controllers/GoogleAuthController.php:56-79`
- Create: `Modules/IdentityAccess/tests/Feature/GoogleAuthStatusTest.php`

**Interfaces:** none (behavior change).

- [ ] **Step 1: Write the failing tests**

`Modules/IdentityAccess/tests/Feature/GoogleAuthStatusTest.php`:

```php
<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Modules\IdentityAccess\Models\User;
use Tests\TestCase;

class GoogleAuthStatusTest extends TestCase
{
    use RefreshDatabase;

    private function mockGoogle(string $email, ?string $googleId = 'google-1'): void
    {
        $user = (new SocialiteUser)->map([
            'id' => $googleId,
            'name' => 'Google User',
            'email' => $email,
            'avatar' => null,
        ]);
        Socialite::shouldReceive('driver->user')->andReturn($user);
    }

    public function test_suspended_user_with_linked_google_is_blocked(): void
    {
        $user = User::create([
            'name' => 'Suspended',
            'email' => 'suspended@test.com',
            'password' => null,
            'role' => 'user',
            'status' => 'suspended',
            'google_id' => 'google-1',
            'email_verified_at' => now(),
        ]);
        $this->mockGoogle($user->email);

        $this->get('/auth/google/callback')
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_active_admin_without_2fa_is_forced_through_challenge(): void
    {
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => null,
            'role' => 'admin',
            'status' => 'active',
            'google_id' => 'google-1',
            'email_verified_at' => now(),
            'two_factor_type' => null,
        ]);
        $this->mockGoogle($user->email);

        $this->get('/auth/google/callback')
            ->assertRedirect(route('2fa.challenge'));
        $this->assertGuest();
    }
}
```

Note: if the installed socialite version has no `SocialiteUser::map()`, build the mock with `setRaw()` instead. Run the suite to confirm the mock works before the fix.

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/GoogleAuthStatusTest.php`
Expected: FAIL — suspended user logs in; admin without 2FA skips the challenge.

- [ ] **Step 3: Fix `handleCallback`**

After the `if ($existing) {...} else {...}` block (line 56), before `Log::info('auth.google_linked', ...)`:

```php
if ($user->status !== 'active') {
    Log::warning('auth.google_status_blocked', ['user' => $user->id, 'status' => $user->status]);
    return redirect()->route('login')->withErrors(['email' => 'Your account is currently ' . $user->status . '. Please contact support.']);
}
```

Replace the gate at line 60 (`if ($user->twoFactorEnabled()) {...}`) with:

```php
if ($user->twoFactorEnabled() || $user->isAdmin() || $user->isPartner()) {
    session([
        '2fa.pending' => $user->id,
        '2fa.attempts' => 0,
        '2fa.pending_method' => $user->twoFactorMethod(),
    ]);
    if ($user->twoFactorMethod() === 'email') {
        OtpService::send($user);
    }
    return redirect()->route('2fa.challenge');
}
```

And remove the dead `2fa.required` session lines:

```php
Auth::login($user);
$request->session()->regenerate();

return redirect()->intended('/');
```

- [ ] **Step 4: Run tests + commit**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/GoogleAuthStatusTest.php && php artisan test`
Expected: all PASS.
```bash
git add Modules/IdentityAccess/app/Http/Controllers/GoogleAuthController.php Modules/IdentityAccess/tests/Feature/GoogleAuthStatusTest.php
git commit -m "fix(identity): google login enforces active status and challenges admins/partners"
```

---

### Task 6: E3 — FK-safe deletes

**Files:**
- Modify: `Modules/CatalogDelivery/app/Http/Controllers/ProductController.php:106-110`
- Modify: `Modules/CatalogDelivery/app/Http/Controllers/CategoryController.php:49-53`
- Modify: `Modules/CatalogDelivery/app/Http/Controllers/PartnerInventoryController.php:40-61` (bulkAction) and `:165-174` (destroy)
- Modify: `Modules/IdentityAccess/app/Http/Controllers/AdminUserController.php:103-112` (destroy)
- Create: `Modules/CatalogDelivery/tests/Feature/FkSafeDeleteTest.php`
- Create: `Modules/IdentityAccess/tests/Feature/AdminUserDeleteTest.php`

**Interfaces:** none (behavior change).

- [ ] **Step 1: Write the failing tests**

`Modules/CatalogDelivery/tests/Feature/FkSafeDeleteTest.php`:

```php
<?php

namespace Modules\CatalogDelivery\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CatalogDelivery\Models\Category;
use Modules\CatalogDelivery\Models\Product;
use Modules\MarketplacePipeline\Models\Cart;
use Modules\MarketplacePipeline\Models\CartItem;
use Modules\IdentityAccess\Models\User;
use Tests\TestCase;

class FkSafeDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_product_in_a_cart_returns_friendly_error_not_500(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $cart = Cart::factory()->create(['user_id' => $user->id]);
        CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id]);

        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']))
            ->delete("/admin/products/{$product->id}")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_deleting_a_category_with_products_returns_friendly_error_not_500(): void
    {
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']))
            ->delete("/admin/categories/{$category->id}")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }
}
```

`Modules/IdentityAccess/tests/Feature/AdminUserDeleteTest.php`:

```php
<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Models\User;
use Tests\TestCase;

class AdminUserDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_purging_a_user_with_orders_returns_friendly_error_not_500(): void
    {
        $target = User::factory()->create();
        \Modules\MarketplacePipeline\Models\Order::factory()->create(['user_id' => $target->id]);

        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']))
            ->delete("/admin/users/{$target->id}")
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }
}
```

(Check the actual factories: `Cart`, `CartItem`, `Order` factories must exist in `Modules/*/database/factories/` — if a factory is missing, create the model with `::create([...])` using the real columns from the migration instead.)

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test Modules/CatalogDelivery/tests/Feature/FkSafeDeleteTest.php Modules/IdentityAccess/tests/Feature/AdminUserDeleteTest.php`
Expected: FAIL with `QueryException` (500).

- [ ] **Step 3: Wrap deletes in try/catch**

`ProductController::destroy`:

```php
public function destroy($id)
{
    try {
        $product = Product::findOrFail($id);
        $product->delete();
        return redirect()->route('admin.products.index')->with('success', 'Product Removed successfully');
    } catch (\Illuminate\Database\QueryException $e) {
        return redirect()->route('admin.products.index')
            ->with('error', 'Cannot delete this product: it is referenced by existing orders, carts, or reviews.');
    }
}
```

`CategoryController::destroy`:

```php
public function destroy($id)
{
    try {
        $category = Category::findOrFail($id);
        $category->delete();
        return redirect()->back()->with('success', 'Category deleted');
    } catch (\Illuminate\Database\QueryException $e) {
        return redirect()->back()
            ->with('error', 'Cannot delete this category: it still contains products.');
    }
}
```

`PartnerInventoryController::destroy` (existing signature — wrap its delete, same QueryException pattern, message: 'Cannot delete this product: it is referenced by existing orders, carts, or reviews.').

`PartnerInventoryController::bulkAction` delete branch — wrap the whole branch in try/catch (`\Illuminate\Database\QueryException`), on failure: `return redirect()->back()->with('error', 'Some products could not be removed because they are referenced by orders, carts, or reviews.');`

`AdminUserController::destroy` — replace `User::destroy($id);` with:

```php
try {
    $user = User::findOrFail($id);
    $user->delete();
} catch (\Illuminate\Database\QueryException $e) {
    return back()->with('error', 'Cannot purge this member: they have orders or partner records. Suspend the account instead.');
}
$this->telemetry->log('admin.users.destroy', ['user_id' => $id]);
return redirect()->back()->with('success', 'Member purged from registry.');
```

Note: the global toast in `app-layout.blade.php` does not render `session('error')` — confirm whether `error` flashes are visible; if not, ALSO add a small error render to the toast block:

```blade
@if(session('error'))
    showToast("{{ session('error') }}", 'error');
@endif
```

- [ ] **Step 4: Run tests + commit**

Run: `php artisan test Modules/CatalogDelivery/tests/Feature/FkSafeDeleteTest.php Modules/IdentityAccess/tests/Feature/AdminUserDeleteTest.php && php artisan test`
Expected: all PASS.
```bash
git add Modules/CatalogDelivery/app/Http/Controllers/ProductController.php Modules/CatalogDelivery/app/Http/Controllers/CategoryController.php Modules/CatalogDelivery/app/Http/Controllers/PartnerInventoryController.php Modules/IdentityAccess/app/Http/Controllers/AdminUserController.php Modules/CatalogDelivery/tests/Feature/FkSafeDeleteTest.php Modules/IdentityAccess/tests/Feature/AdminUserDeleteTest.php resources/views/components/app-layout.blade.php
git commit -m "fix: FK-safe deletes — friendly errors instead of 500s, error flashes visible"
```

---

### Task 7: E4 — admin orders pagination + empty state

**Files:**
- Modify: `Modules/MarketplacePipeline/resources/views/admin/orders/index.blade.php`

**Interfaces:** consumes `$orders` paginator (15/page) already passed by `AdminOrderController::index`.

- [ ] **Step 1: Add pagination + empty state to the blade**

In `Modules/MarketplacePipeline/resources/views/admin/orders/index.blade.php`:

Wrap the table in `@if($orders->isNotEmpty()) ... @else` empty-state block (mirror `admin/reviews/index.blade.php` empty state pattern), and after the table add:

```blade
<div class="pc-pagination">
    {{ $orders->links() }}
</div>
```

- [ ] **Step 2: Verify + commit**

Run: `php artisan test` — green.
```bash
git add Modules/MarketplacePipeline/resources/views/admin/orders/index.blade.php
git commit -m "fix(marketplace): admin orders list renders pagination and empty state"
```

---

### Task 8: E5 — cart remove IDOR

**Files:**
- Modify: `Modules/MarketplacePipeline/app/Http/Controllers/CartController.php:85-91`
- Modify: `Modules/MarketplacePipeline/tests/Feature/CartControllerTest.php` (create if missing)

**Interfaces:** none (behavior change).

- [ ] **Step 1: Write the failing test**

In `Modules/MarketplacePipeline/tests/Feature/` create `CartOwnershipTest.php`:

```php
<?php

namespace Modules\MarketplacePipeline\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\CatalogDelivery\Models\Product;
use Modules\IdentityAccess\Models\User;
use Modules\MarketplacePipeline\Models\Cart;
use Modules\MarketplacePipeline\Models\CartItem;
use Tests\TestCase;

class CartOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_remove_another_users_cart_item(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $product = Product::factory()->create();
        $cart = Cart::factory()->create(['user_id' => $owner->id]);
        $item = CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id]);

        $this->actingAs($attacker)
            ->delete("/cart/remove/{$item->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
    }
}
```

(Use the real route URL: check `Modules/MarketplacePipeline/routes/web.php` for the remove route path — likely `/cart/remove/{id}` — and the model factories; if factories are missing, `Model::create([...])` with real columns.)

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test Modules/MarketplacePipeline/tests/Feature/CartOwnershipTest.php`
Expected: FAIL — the item is deleted (200).

- [ ] **Step 3: Fix `remove`**

```php
public function remove($id)
{
    $item = CartItem::whereHas('cart', fn ($q) => $q->where('user_id', auth()->id()))
        ->find($id);

    if (! $item) {
        abort(404);
    }

    $item->delete();

    return back()->with('status', 'Item removed');
}
```

- [ ] **Step 4: Run tests + commit**

Run: `php artisan test Modules/MarketplacePipeline/tests/Feature/CartOwnershipTest.php && php artisan test`
Expected: all PASS.
```bash
git add Modules/MarketplacePipeline/app/Http/Controllers/CartController.php Modules/MarketplacePipeline/tests/Feature/CartOwnershipTest.php
git commit -m "fix(marketplace): cart item removal is scoped to the owner (IDOR)"
```

---

### Task 9: E6 — challenge resend works for un-enrolled admins/partners

**Files:**
- Modify: `Modules/IdentityAccess/app/Http/Controllers/TwoFactorController.php:69-86`
- Create: `Modules/IdentityAccess/tests/Feature/ChallengeResendTest.php`

**Interfaces:** none (behavior change).

- [ ] **Step 1: Write the failing test**

`Modules/IdentityAccess/tests/Feature/ChallengeResendTest.php`:

```php
<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\IdentityAccess\Models\User;
use Modules\IdentityAccess\Services\OtpService;
use Tests\TestCase;

class ChallengeResendTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_without_2fa_can_resend_challenge_code(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'status' => 'active', 'two_factor_type' => null]);
        session(['2fa.pending' => $user->id, '2fa.attempts' => 0, '2fa.pending_method' => 'email']);
        Cache::forget('2fa:resend:' . $user->id);

        $this->post('/2fa/challenge/resend')
            ->assertSessionHas('status')
            ->assertRedirect();
    }
}
```

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/ChallengeResendTest.php`
Expected: FAIL — currently redirects to login.

- [ ] **Step 3: Fix `resend`**

Replace the guard:

```php
if (! $user || (! $user->twoFactorEnabled() && ! $user->isAdmin() && ! $user->isPartner())) {
    session()->forget('2fa.pending');
    return redirect()->route('login');
}
```

- [ ] **Step 4: Run tests + commit**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/ChallengeResendTest.php && php artisan test`
Expected: all PASS.
```bash
git add Modules/IdentityAccess/app/Http/Controllers/TwoFactorController.php Modules/IdentityAccess/tests/Feature/ChallengeResendTest.php
git commit -m "fix(identity): challenge resend works for admins/partners without 2FA enrolled"
```

---

### Task 10: E7 — "send code" actions on settings/security forms

**Files:**
- Modify: `Modules/IdentityAccess/routes/web.php:36-50` (auth group — add 3 POST routes)
- Modify: `Modules/IdentityAccess/app/Http/Controllers/UserController.php` (add `sendEmailCode`, `sendPasswordCode`)
- Modify: `Modules/IdentityAccess/app/Http/Controllers/TwoFactorController.php` (add `sendDisableCode`)
- Modify: `Modules/IdentityAccess/resources/views/users/settings.blade.php:24-30`
- Modify: `Modules/IdentityAccess/resources/views/users/security.blade.php:67-73`
- Modify: `Modules/IdentityAccess/resources/views/partials/twofa-card.blade.php:14-20`
- Create: `Modules/IdentityAccess/tests/Feature/SendCodeButtonTest.php`

**Interfaces:**
- Produces: routes `profile.send-email-code`, `profile.send-password-code`, `profile.settings.twofa.send-disable-code` (POST, auth).

- [ ] **Step 1: Write the failing tests**

`Modules/IdentityAccess/tests/Feature/SendCodeButtonTest.php`:

```php
<?php

namespace Modules\IdentityAccess\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Modules\IdentityAccess\Models\User;
use Tests\TestCase;

class SendCodeButtonTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_email_code_issues_otp(): void
    {
        $user = User::factory()->create();
        Cache::forget('otp:send:' . $user->id);

        $this->actingAs($user)
            ->post('/profile/send-email-code')
            ->assertSessionHas('status');
    }

    public function test_send_password_code_issues_otp(): void
    {
        $user = User::factory()->create();
        Cache::forget('otp:send:' . $user->id);

        $this->actingAs($user)
            ->post('/profile/send-password-code')
            ->assertSessionHas('status');
    }

    public function test_send_disable_code_issues_otp(): void
    {
        $user = User::factory()->create(['two_factor_type' => 'email', 'two_factor_confirmed_at' => now()]);
        Cache::forget('otp:send:' . $user->id);

        $this->actingAs($user)
            ->post('/profile/settings/twofa/send-disable-code')
            ->assertSessionHas('status');
    }
}
```

(Check `OtpService::send` for any cache keys/rate limits that would break the tests — `2fa:resend:` is used by the challenge resend; mirror whatever guard exists. If `OtpService::send` has no guard, drop the `Cache::forget` lines.)

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/SendCodeButtonTest.php`
Expected: FAIL — routes don't exist (404).

- [ ] **Step 3: Add the three routes**

In `Modules/IdentityAccess/routes/web.php`, inside the `Route::middleware(['auth'])` group after the twofa routes:

```php
Route::post('/profile/send-email-code', [UserController::class, 'sendEmailCode'])->middleware('throttle:2fa-resend')->name('profile.send-email-code');
Route::post('/profile/send-password-code', [UserController::class, 'sendPasswordCode'])->middleware('throttle:2fa-resend')->name('profile.send-password-code');
Route::post('/profile/settings/twofa/send-disable-code', [\Modules\IdentityAccess\Http\Controllers\TwoFactorController::class, 'sendDisableCode'])->middleware('throttle:2fa-resend')->name('profile.settings.twofa.send-disable-code');
```

- [ ] **Step 4: Add controller methods**

In `UserController`:

```php
public function sendEmailCode()
{
    \Modules\IdentityAccess\Services\OtpService::send(Auth::user());
    return back()->with('status', 'A verification code was sent to your email. It expires in 10 minutes.');
}

public function sendPasswordCode()
{
    \Modules\IdentityAccess\Services\OtpService::send(Auth::user());
    return back()->with('status', 'A verification code was sent to your email. It expires in 10 minutes.');
}
```

In `TwoFactorController`:

```php
public function sendDisableCode()
{
    OtpService::send($this->userOrFail());
    return back()->with('status', 'A verification code was sent to your email. It expires in 10 minutes.');
}
```

(Use `Auth::user()` pattern already used elsewhere in TwoFactorController for simplicity — `$user = Auth::user();`.)

- [ ] **Step 5: Add "Send code" buttons to the blades**

`settings.blade.php` — next to the Verification Code input (lines 24-29), add:

```blade
<button type="button" class="btn btn-ghost" onclick="document.getElementById('send-email-code').submit()">Send Code</button>
<form id="send-email-code" action="{{ route('profile.send-email-code') }}" method="POST" class="form-inline">
    @csrf
</form>
```

`security.blade.php` — next to the password form's code input (lines 67-72):

```blade
<button type="button" class="btn btn-ghost" onclick="document.getElementById('send-password-code').submit()">Send Code</button>
<form id="send-password-code" action="{{ route('profile.send-password-code') }}" method="POST" class="form-inline">
    @csrf
</form>
```

`twofa-card.blade.php` — inside the disable form next to the code input (lines 14-19):

```blade
<button type="button" class="btn btn-ghost" onclick="document.getElementById('send-disable-code').submit()">Send Code</button>
<form id="send-disable-code" action="{{ route('profile.settings.twofa.send-disable-code') }}" method="POST" class="form-inline">
    @csrf
</form>
```

(Match the existing form-group layout/classes; buttons go inside the `.form-group` below the code input, following the existing `form-hint` styling.)

- [ ] **Step 6: Run tests + commit**

Run: `php artisan test Modules/IdentityAccess/tests/Feature/SendCodeButtonTest.php && php artisan test`
Expected: all PASS.
```bash
git add Modules/IdentityAccess/routes/web.php Modules/IdentityAccess/app/Http/Controllers/UserController.php Modules/IdentityAccess/app/Http/Controllers/TwoFactorController.php Modules/IdentityAccess/resources/views/users/settings.blade.php Modules/IdentityAccess/resources/views/users/security.blade.php Modules/IdentityAccess/resources/views/partials/twofa-card.blade.php Modules/IdentityAccess/tests/Feature/SendCodeButtonTest.php
git commit -m "feat(identity): send-code buttons for email change, password change, 2FA disable"
```

---

### Task 11: E8 — partner onboarding promotes the user's role

**Files:**
- Modify: `Modules/PartnerHub/app/Http/Controllers/PartnerController.php:35-46`
- Create: `Modules/PartnerHub/tests/Feature/PartnerRolePromotionTest.php`

**Interfaces:** none (behavior change).

- [ ] **Step 1: Write the failing test**

`Modules/PartnerHub/tests/Feature/PartnerRolePromotionTest.php`:

```php
<?php

namespace Modules\PartnerHub\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\IdentityAccess\Models\User;
use Tests\TestCase;

class PartnerRolePromotionTest extends TestCase
{
    use RefreshDatabase;

    public function test_establishing_a_partner_promotes_the_user_role(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $member = User::factory()->create(['role' => 'user', 'status' => 'active']);

        $this->actingAs($admin)
            ->post('/admin/partners', [
                'name' => 'Test Artisan',
                'description' => null,
                'contact_info' => 'contact@test.com',
                'website' => null,
                'user_id' => $member->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $member->id, 'role' => 'partner', 'status' => 'active']);
    }

    public function test_establishing_a_partner_with_an_admin_user_is_rejected(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $this->actingAs($admin)
            ->post('/admin/partners', [
                'name' => 'Admin Artisan',
                'description' => null,
                'contact_info' => null,
                'website' => null,
                'user_id' => $admin->id,
            ])
            ->assertSessionHasErrors('user_id');

        $this->assertDatabaseMissing('partners', ['name' => 'Admin Artisan']);
    }
}
```

(Verify the create form posts to `/admin/partners` — check `Modules/PartnerHub/routes/web.php`; adjust the URL if needed. Check `StorePartnerRequest` — add the `not_in` rule below, which the second test relies on.)

- [ ] **Step 2: Run to verify failure**

Run: `php artisan test Modules/PartnerHub/tests/Feature/PartnerRolePromotionTest.php`
Expected: FAIL — role stays `user`.

- [ ] **Step 3: Add validation rule + promotion logic**

In `StorePartnerRequest::rules()`, change `user_id` to:

```php
'user_id' => 'required|exists:users,id|not_in:' . implode(',', User::where('role', 'admin')->pluck('id')->all()),
```

(Or simpler and explicit: keep the rule and handle it in the controller.) In `PartnerController::store`, after `Partner::create($data);`:

```php
$user = User::findOrFail($data['user_id']);
$user->forceFill(['role' => 'partner', 'status' => 'active'])->save();
```

- [ ] **Step 4: Run tests + commit**

Run: `php artisan test Modules/PartnerHub/tests/Feature/PartnerRolePromotionTest.php && php artisan test`
Expected: all PASS.
```bash
git add Modules/PartnerHub/app/Http/Controllers/PartnerController.php Modules/PartnerHub/app/Http/Requests/StorePartnerRequest.php Modules/PartnerHub/tests/Feature/PartnerRolePromotionTest.php
git commit -m "fix(partner): establishing a partner promotes the user role — no more orphaned portals"
```

---

### Task 12: Docs, deploy, live verification

**Files:**
- Modify: `docs/AUDIT-2026-08-20-full-app-sweep.md` (mark E1–E8 + catalog/collection/filter as fixed)
- Modify: `PROJECT_REPORT.txt` (add §18)
- Modify: `docs/PROJECT_ARCHITECTURE.md` (test count + route list additions)

- [ ] **Step 1: Update docs**

Audit doc: annotate each HIGH/MEDIUM item fixed in this plan with `✅ FIXED (commit <sha>)`.
Report §18: summarize images curation, collection page, filter fix, E1–E8 with commit range and test totals.
Architecture: update test count (113 + new tests) and note the new routes (`profile.send-email-code`, `profile.send-password-code`, `profile.settings.twofa.send-disable-code`).

- [ ] **Step 2: Full suite + local live check**

Run: `php artisan test` — all green.
Local: `php artisan serve --port=8001` running; curl `/collection`, `/shop` (drawer CSS), `/admin/dashboard` (login as mafuletil@gmail.com via password+OTP from tinker `OtpService::issue`), verify footer anchors.

- [ ] **Step 3: Commit docs + deploy**

```bash
git add docs/AUDIT-2026-08-20-full-app-sweep.md PROJECT_REPORT.txt docs/PROJECT_ARCHITECTURE.md
git commit -m "docs: audit status, report §18, architecture — catalog images, collection, sweep fixes"
git push
```

Deploy: `ssh root@104.248.163.215 "cd /var/www/smartshop && git pull -q && php artisan migrate --force && php artisan route:clear -q && php artisan config:clear -q && php artisan queue:restart"`

- [ ] **Step 4: Live-verify on prod**

- `/collection` 200 with all 7 category sections + anchors.
- `/shop` on a phone viewport: drawer scrolls, Apply Filter reachable.
- Product pages: images match product names (spot-check one product per category).
- Admin login (mafuletil@gmail.com): password + email OTP → dashboard. Pending user cannot reach `/admin/*`.
- Admin orders list: pagination links present.
- Google login blocked for suspended accounts (skip if no Google test account).
