# Modular Monolith Migration Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Migrate the SmartShop e-commerce monolith from role-organized code to a modular monolith (`nwidart/laravel-modules` v13, 5 modules + Core) following the conventions proven in the Atlas-Learning reference project, without breaking any existing route name, feature, or the live deployment.

**Architecture:** Root-level `Modules/<PascalCase>/` directories, each owning its app code (Controllers/Models/Services/Providers/Events/Listeners/Mail/Policies), `database/{migrations,factories,seeders}`, `resources/views` + `resources/assets/{js,scss}`, `routes/{web,api}.php`, `tests/`, `module.json`, `composer.json`, and `vite.config.js`. Modules auto-discover via `module.json` providers + `modules_statuses.json`; views resolve via lowercase view namespace aliases (`identityaccess::`, `catalogdelivery::`, etc.). Core (`app/`, `resources/views/components|layouts`, `config/`, `bootstrap/`) keeps only shared infrastructure: layout Blade components (the CSS layer), CurrencyService + `@money`, middleware wiring, generic controllers. Hard rules: **blades contain zero SQL and zero inline CSS** (queries → per-module `Services/`, styles → per-module `resources/assets/scss/` + core design system), **route names never change**.

**Tech Stack:** PHP 8.3+, Laravel 13.x, nwidart/laravel-modules ^13, wikimedia/composer-merge-plugin ^2.1, Vite 8 (single root build aggregating module assets), Tailwind 4, PHPUnit 12 (per-module test suites), MySQL (single DB).

## Global Constraints

- **Route names are immutable.** Every task that moves a route MUST keep the exact `->name()` (e.g., `admin.orders.index`, `partner.inventory.store`). Blade `route()` calls will not change.
- **View namespace rule:** module views are referenced as `<alias>::<path>` where alias = lowercase module name from `module.json` (`identityaccess`, `catalogdelivery`, `marketplacepipeline`, `partnerhub`, `telemetrypipeline`). Core views (`partials.pagination`, `partials.admin-nav`, `partials.partner-nav`, `layouts.*`) keep bare names.
- **No SQL in blades, no inline CSS in blades.** Any `@php`/`{{ }}` block containing `::where`, `DB::`, `->get()`, `Model::` queries moves into the module's `Services/`. Any `<style>` block moves into the module's `resources/assets/scss/app.scss` or core `resources/css/app.css`.
- **Single database.** All module migrations live in `Modules/<M>/database/migrations/` and run with the global `php artisan migrate` (nwidart auto-discovers them). Never split the DB.
- **Module namespaces:** `Modules\IdentityAccess\` → `Modules/IdentityAccess/app/`, `Modules\CatalogDelivery\` → `Modules/CatalogDelivery/app/`, `Modules\MarketplacePipeline\` → `Modules/MarketplacePipeline/app/`, `Modules\PartnerHub\` → `Modules/PartnerHub/app/`, `Modules\TelemetryPipeline\` → `Modules/TelemetryPipeline/app/`. Test namespaces `Modules\<M>\Tests\{Feature,Unit}` → `Modules/<M>/tests/{Feature,Unit}`.
- **Core-only items that stay put:** `App\Services\CurrencyService`, `@money` directive (`AppServiceProvider`), `App\Http\Middleware\CurrencyMiddleware`, `config/currency.php`, `config/paypal.php` (+ new `config/shop.php`), `resources/views/partials/*`, base `Controller`, `database/factories/UserFactory.php`, `routes/console.php`, root `bootstrap/app.php` (aliases updated to point into modules).
- **PHP 8.3+, PSR-12, no new composer dependencies** beyond nwidart/laravel-modules + wikimedia/composer-merge-plugin.
- **Every task ends green:** `php artisan test` passes; app boots (`php artisan route:list` succeeds).
- **Commit per task** with the message format shown in the task.

---

## Phase 0 — Pre-flight: foundation packages + blocker fixes

### Task 0.1: Install nwidart/laravel-modules + composer-merge-plugin

**Files:**
- Modify: `composer.json`
- Create: `config/modules.php` (published), `modules_statuses.json` (auto)

**Interfaces:**
- Produces: `php artisan module:make` working; `config/modules.php` with `'namespace' => 'Modules'`; `modules_statuses.json` at project root.

- [ ] **Step 1: Require packages**

Run:
```bash
composer require nwidart/laravel-modules:^13.0 wikimedia/composer-merge-plugin:^2.1
```

- [ ] **Step 2: Allow the merge-plugin**

Add to `composer.json` under `config.allow-plugins`:
```json
"wikimedia/composer-merge-plugin": true
```

- [ ] **Step 3: Publish nwidart config**

Run:
```bash
php artisan vendor:publish --provider="Nwidart\Modules\LaravelModulesServiceProvider" --tag=config
```
If the provider name is rejected, run `php artisan vendor:publish` and select the `config` option for nwidart/modules manually.

- [ ] **Step 4: Verify**

```bash
php artisan module:list
```
Expected: `No modules yet.` or an empty table — command must not error.

- [ ] **Step 5: Commit**

```bash
git add composer.json composer.lock config/modules.php modules_statuses.json
git commit -m "build: install nwidart/laravel-modules + composer-merge-plugin"
```

### Task 0.2: Add config/shop.php + commission constants

**Files:**
- Create: `config/shop.php`

**Interfaces:**
- Produces: `config('shop.commission_rate')` = 0.10, `config('shop.currency_default')` = 'USD' — consumed by Task 0.5 (PayoutService) and Task 6.2.

- [ ] **Step 1: Create the config**

Create `config/shop.php`:
```php
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Platform business rules
    |--------------------------------------------------------------------------
    */
    'commission_rate' => env('SHOP_COMMISSION_RATE', 0.10),
    'currency_default' => 'USD',
];
```

- [ ] **Step 2: Verify**

```bash
php artisan tinker --execute="var_dump(config('shop.commission_rate'));"
```
Expected: `float(0.1)`.

- [ ] **Step 3: Commit**

```bash
git add config/shop.php
git commit -m "feat(config): platform business rules config (commission, currency default)"
```

### Task 0.3: Fix the broken WishlistController

**Files:**
- Modify: `app/Http/Controllers/WishlistController.php` (full rewrite)

**Interfaces:**
- Produces: `WishlistController@index` returns `view('wishlist', ['items' => $wishlistItems])` where `$wishlistItems` is an Eloquent collection of `Wishlist` models with `product` loaded; `WishlistController@toggle(Request)` returns `{status:'success', action:'added'|'removed', message:string}` JSON. Consumed by: existing `layouts/app.blade.php` `toggleWishlist()` fetch and `resources/views/wishlist.blade.php` (both already wired).

- [ ] **Step 1: Write the failing feature test**

Create `tests/Feature/WishlistTest.php`:
```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Wishlist;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WishlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_toggle_adds_and_removes_a_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);

        $this->actingAs($user)
            ->post('/wishlist/toggle', ['product_id' => $product->id])
            ->assertOk()
            ->assertJson(['action' => 'added']);

        $this->assertDatabaseHas('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($user)
            ->post('/wishlist/toggle', ['product_id' => $product->id])
            ->assertOk()
            ->assertJson(['action' => 'removed']);

        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_archive_page_lists_wishlist_products(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);
        Wishlist::create(['user_id' => $user->id, 'product_id' => $product->id]);

        $this->actingAs($user)
            ->get('/archive')
            ->assertOk()
            ->assertSee($product->name);
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=WishlistTest`
Expected: FAIL (500 error / exception from `Auth::user()->wishlist()`).

- [ ] **Step 3: Rewrite the controller**

Replace the entire contents of `app/Http/Controllers/WishlistController.php`:
```php
<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WishlistController extends Controller
{
    /**
     * Display the user's saved collection.
     */
    public function index()
    {
        $items = Wishlist::with('product')
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('wishlist', compact('items'));
    }

    /**
     * Toggle a product in the wishlist (AJAX).
     */
    public function toggle(Request $request)
    {
        $productId = $request->integer('product_id');
        $user = Auth::user();

        Product::findOrFail($productId);

        $existing = Wishlist::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($existing) {
            $existing->delete();

            return response()->json([
                'status' => 'success',
                'action' => 'removed',
                'message' => 'Removed from Archive',
            ]);
        }

        Wishlist::create([
            'user_id' => $user->id,
            'product_id' => $productId,
        ]);

        return response()->json([
            'status' => 'success',
            'action' => 'added',
            'message' => 'Saved to Archive',
        ]);
    }
}
```

- [ ] **Step 4: Add the missing User relation**

Add to `app/Models/User.php` (inside the class):
```php
    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }
```

- [ ] **Step 5: Add factories for tests**

The test above uses `Product::factory()` — **the project only has `UserFactory`**. Create `database/factories/ProductFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'price' => fake()->randomFloat(2, 20, 500),
            'description' => fake()->paragraph(),
            'stock' => fake()->numberBetween(1, 50),
            'image' => null,
            'category_id' => Category::factory(),
        ];
    }
}
```
Create `database/factories/CategoryFactory.php`:
```php
<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return ['name' => fake()->unique()->words(2, true)];
    }
}
```
Verify `Product`/`Category` models have `HasFactory` (`rg "HasFactory" app/Models/Product.php app/Models/Category.php`); add `use Illuminate\Database\Eloquent\Factories\HasFactory;` + `use HasFactory;` if missing.

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test --filter=WishlistTest`
Expected: PASS (2 tests).

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/WishlistController.php app/Models/User.php database/factories/ProductFactory.php database/factories/CategoryFactory.php tests/Feature/WishlistTest.php
git commit -m "fix(wishlist): implement toggle + archive against wishlists table"
```

### Task 0.4: Wire customer review submission

**Files:**
- Modify: `routes/web.php` (add member route), `resources/views/product.blade.php` (add form), `app/Http/Controllers/ReviewController.php` (store sets status pending)
- Create: `tests/Feature/ReviewSubmissionTest.php`

**Interfaces:**
- Produces: route `reviews.store` = `POST /reviews` (auth middleware) → `ReviewController@store`. New reviews created with `status = 'pending'` so the existing admin moderation (approve/reject) is meaningful. Consumed by product detail page form; `ViewController@product` already filters to approved reviews.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/ReviewSubmissionTest.php`:
```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_submit_a_review_for_moderation(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);

        $this->actingAs($user)
            ->post('/reviews', [
                'product_id' => $product->id,
                'rating' => 5,
                'comment' => 'Exceptional piece.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'rating' => 5,
            'status' => 'pending',
        ]);
    }

    public function test_guest_cannot_submit_a_review(): void
    {
        $product = Product::factory()->create(['stock' => 5]);

        $this->post('/reviews', [
            'product_id' => $product->id,
            'rating' => 5,
        ])->assertRedirect('/login');
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=ReviewSubmissionTest`
Expected: FAIL (404 — route does not exist).

- [ ] **Step 3: Add the route**

In `routes/web.php`, inside the existing `Route::middleware(['auth'])->group(...)` block (after the Wishlist routes), add:
```php
    /* Reviews */
    Route::post('/reviews', [\App\Http\Controllers\ReviewController::class, 'store'])->name('reviews.store');
```

- [ ] **Step 4: Make store() create pending reviews**

In `app/Http/Controllers/ReviewController.php`, replace the `Review::create([...])` block in `store()` with:
```php
        Review::create([
            'user_id' => auth()->id(),
            'product_id' => $request->product_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'status' => 'pending',
        ]);
```

- [ ] **Step 5: Add the form to the product page**

In `resources/views/product.blade.php`, above the review list (the `@foreach($reviews ...)` block — `ViewController@product` passes `$reviews`), add:
```blade
@auth
    <div class="review-form-panel">
        <h3>Share your perspective</h3>
        <form method="POST" action="{{ route('reviews.store') }}" class="review-form">
            @csrf
            <input type="hidden" name="product_id" value="{{ $product->id }}">
            <label for="rating">Rating</label>
            <select name="rating" id="rating" required>
                <option value="5">5 — Exceptional</option>
                <option value="4">4 — Excellent</option>
                <option value="3">3 — Good</option>
                <option value="2">2 — Fair</option>
                <option value="1">1 — Poor</option>
            </select>
            <label for="comment">Comment</label>
            <textarea name="comment" id="comment" rows="4" placeholder="What makes this piece special?"></textarea>
            <button type="submit" class="btn-primary">Submit Review</button>
        </form>
    </div>
@else
    <p><a href="{{ route('login') }}">Sign in</a> to share your perspective.</p>
@endauth
```
Use the page's existing button/form class names from the `layouts/app.blade.php` design system.

- [ ] **Step 6: Run tests to verify they pass**

Run: `php artisan test --filter=ReviewSubmissionTest`
Expected: PASS (2 tests). Also run `php artisan test` — full suite must stay green.

- [ ] **Step 7: Commit**

```bash
git add routes/web.php app/Http/Controllers/ReviewController.php resources/views/product.blade.php tests/Feature/ReviewSubmissionTest.php
git commit -m "feat(reviews): customer review submission with pending moderation"
```

### Task 0.5: Fix payout revenue split (financial leak)

**Files:**
- Modify: `app/Http/Controllers/AdminOrderController.php` (`complete()`)
- Create: `tests/Feature/PayoutSplitTest.php`

**Interfaces:**
- Produces: `complete($id)` behaviour — for each order item, the line total is split equally among that product's partners; each partner's payout = their aggregated share × (1 − `config('shop.commission_rate')`), via `Payout::updateOrCreate(['order_id','partner_id'], ['amount'=>..., 'status'=>'pending'])`. Consumed by: admin "Mark Shipped" form (already wired), and later moved verbatim into `PayoutService` (Task 5.3).

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/PayoutSplitTest.php`:
```php
<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Partner;
use App\Models\Payout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayoutSplitTest extends TestCase
{
    use RefreshDatabase;

    public function test_multi_partner_product_splits_payout_equally(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $buyer = User::factory()->create(['status' => 'active']);
        $p1 = Partner::create(['name' => 'Atelier A', 'user_id' => User::factory()->create(['status' => 'active'])->id]);
        $p2 = Partner::create(['name' => 'Atelier B', 'user_id' => User::factory()->create(['status' => 'active'])->id]);

        $product = Product::factory()->create(['price' => 100, 'stock' => 5]);
        $product->partners()->attach([$p1->id, $p2->id]);

        $order = Order::create(['user_id' => $buyer->id, 'total_price' => 100, 'status' => 'paid']);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 1, 'price' => 100]);

        $this->actingAs($admin)->post("/admin/orders/{$order->id}/complete")->assertRedirect();

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame(2, Payout::where('order_id', $order->id)->count());

        foreach (Payout::where('order_id', $order->id)->get() as $payout) {
            $this->assertEqualsWithDelta(45.0, (float) $payout->amount, 0.001);
        }
    }
}
```

- [ ] **Step 2: Run test to verify it fails**

Run: `php artisan test --filter=PayoutSplitTest`
Expected: FAIL — both payouts equal 90.0 (each partner receives the full line value).

- [ ] **Step 3: Fix the payout computation**

In `app/Http/Controllers/AdminOrderController.php`, replace the payout block inside `complete()` (the `$partnerItems = []` foreach loop and the payout creation loop) with:
```php
                // Calculate payouts for each partner involved in this order.
                // A product's line value is split equally among its partners.
                $partnerItems = [];
                foreach ($order->items as $item) {
                    $partners = $item->product->partners;
                    if ($partners->isEmpty()) {
                        continue;
                    }
                    $lineValue = $item->price * $item->quantity;
                    $share = $lineValue / $partners->count();
                    foreach ($partners as $partner) {
                        $partnerItems[$partner->id] = ($partnerItems[$partner->id] ?? 0) + $share;
                    }
                }

                foreach ($partnerItems as $partnerId => $grossAmount) {
                    // Platform takes commission_rate (default 10%) commission
                    $netAmount = $grossAmount * (1 - config('shop.commission_rate'));

                    \App\Models\Payout::updateOrCreate(
                        ['order_id' => $order->id, 'partner_id' => $partnerId],
                        ['amount' => $netAmount, 'status' => 'pending']
                    );
                }
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=PayoutSplitTest`
Expected: PASS. Then full suite: `php artisan test` — green.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/AdminOrderController.php tests/Feature/PayoutSplitTest.php
git commit -m "fix(payouts): split line value equally among product partners (leak fix)"
```

### Task 0.6: Fix admin product show 404

**Files:**
- Modify: `routes/web.php` (remove the `show` route + name entry)

**Interfaces:**
- Produces: `admin.products.show` route removed (ProductController has no `show()` method — the legacy `resources/views/products/show.blade.php` is dead and will be deleted in Task 7.1).

- [ ] **Step 1: Remove the route**

In `routes/web.php`, the `Route::resource('products', ...)` block currently includes `'show' => 'products.show'` in its `->names([...])` array. Remove the `'show' => 'products.show',` line.

- [ ] **Step 2: Verify**

```bash
php artisan route:list --name=products
```
Expected: `show` route absent; `index/create/store/edit/update/destroy` present.

- [ ] **Step 3: Commit**

```bash
git add routes/web.php
git commit -m "fix(routes): remove dead admin.products.show route"
```

### Task 0.7: Baseline test suite green

- [ ] **Step 1: Run the full suite**

Run: `php artisan test`
Expected: ALL PASS (ExampleTest + WishlistTest + ReviewSubmissionTest + PayoutSplitTest + Unit test).

- [ ] **Step 2: Commit any stragglers**

```bash
git status --short
git add -A && git commit -m "chore: pre-refactor baseline green"
```

---

## Phase 1 — Core scaffolding: layout components, Vite aggregation, module skeletons

### Task 1.1: Extract layout Blade components (the CSS layer)

**Files:**
- Create: `app/View/Components/AppLayout.php`, `resources/views/components/app-layout.blade.php`
- Modify: `resources/views/layouts/app.blade.php` (becomes the component body with `{{ $slot }}`), `resources/css/app.css` (absorb the layout's `<style>` block)

**Interfaces:**
- Produces: `<x-app-layout>` component rendering the existing chrome (nav, currency switcher, user dropdown, footer, toasts, `toggleWishlist()` JS, theme toggle). Slot = page content. Existing views keep working.

- [ ] **Step 1: Create the component class**

Create `app/View/Components/AppLayout.php`:
```php
<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class AppLayout extends Component
{
    public function render(): View|Closure|string
    {
        return view('layouts.app');
    }
}
```

- [ ] **Step 2: Convert the layout to slot-based**

In `resources/views/layouts/app.blade.php`, locate the main content region — the block containing page content (a `<main>...</main>` region or `@yield('content')`). Replace that inner content block with:
```blade
<main class="app-main">
    {{ $slot }}
</main>
```
If the file uses `@yield('content')`, replace it with `{{ $slot }}`. The layout must have no `@section/@yield` requirements on children (verify it doesn't; it currently renders children inside the content region).

- [ ] **Step 3: Move the layout's `<style>` block to the core stylesheet**

Move the contents of the large inline `<style>` block in the layout verbatim into `resources/css/app.css` (append below the `@import 'tailwindcss';`/`@source` lines and font declarations). Delete the `<style>...</style>` block from the layout. Keep inline `style="..."` attributes only if dynamic (JS-driven).

- [ ] **Step 4: Smoke test**

```bash
php artisan serve --port=8000 &
curl -s http://127.0.0.1:8000/ | head -20
kill %1
```
Expected: home page renders with nav intact.

- [ ] **Step 5: Commit**

```bash
git add app/View/Components/AppLayout.php resources/views/layouts/app.blade.php resources/css/app.css
git commit -m "refactor(layout): x-app-layout component + design system moved to core css"
```

### Task 1.2: Create the 5 module skeletons

**Files:**
- Create: `Modules/{IdentityAccess,CatalogDelivery,MarketplacePipeline,PartnerHub,TelemetryPipeline}/` skeletons (via nwidart generator), `modules_statuses.json` (auto)

**Interfaces:**
- Produces: five empty modules discoverable by nwidart; `modules_statuses.json` lists all five as `true`. Their `module.json` aliases: `identityaccess`, `catalogdelivery`, `marketplacepipeline`, `partnerhub`, `telemetrypipeline`.

- [ ] **Step 1: Generate the modules**

Run:
```bash
php artisan module:make IdentityAccess
php artisan module:make CatalogDelivery
php artisan module:make MarketplacePipeline
php artisan module:make PartnerHub
php artisan module:make TelemetryPipeline
```

- [ ] **Step 2: Verify discovery**

Run:
```bash
php artisan module:list
```
Expected: 5 modules, all enabled. `modules_statuses.json` contains all five keys with `true`.

- [ ] **Step 3: Verify skeleton layout**

Run:
```bash
find Modules/IdentityAccess -maxdepth 2 | sort
```
Expected to include: `app/`, `app/Providers/IdentityAccessServiceProvider.php`, `config/config.php`, `database/migrations/`, `database/seeders/`, `resources/views/`, `routes/api.php`, `routes/web.php`, `tests/Feature/`, `tests/Unit/`, `module.json`, `composer.json`, `vite.config.js`.

- [ ] **Step 4: Verify module.json aliases**

For each module, confirm `Modules/<M>/module.json` has `"alias": "<lowercasename>"` exactly: `identityaccess`, `catalogdelivery`, `marketplacepipeline`, `partnerhub`, `telemetrypipeline`.

- [ ] **Step 5: Commit**

```bash
git add Modules/ modules_statuses.json
git commit -m "feat(modules): scaffold five SmartShop modules"
```

### Task 1.3: Wire composer autoload + merge-plugin + module composer.json

**Files:**
- Modify: `composer.json` (root), `Modules/<M>/composer.json` (5 files)

**Interfaces:**
- Produces: PSR-4 autoload `Modules\<M>\` → `Modules/<M>/app/`; module factories/seeders/tests namespaces; merge-plugin `include: ["Modules/*/composer.json"]`.

- [ ] **Step 1: Add module PSR-4 entries to root composer.json**

In `composer.json` `autoload.psr-4`, add (keeping existing entries):
```json
"Modules\\IdentityAccess\\": "Modules/IdentityAccess/app/",
"Modules\\CatalogDelivery\\": "Modules/CatalogDelivery/app/",
"Modules\\MarketplacePipeline\\": "Modules/MarketplacePipeline/app/",
"Modules\\PartnerHub\\": "Modules/PartnerHub/app/",
"Modules\\TelemetryPipeline\\": "Modules/TelemetryPipeline/app/"
```

- [ ] **Step 2: Add merge-plugin extra to root composer.json**

Add to `composer.json` `extra` (keep existing `extra.laravel`):
```json
"extra": {
    "laravel": {
        "dont-discover": []
    },
    "merge-plugin": {
        "include": ["Modules/*/composer.json"]
    }
}
```

- [ ] **Step 3: Add module test namespaces to autoload-dev**

In `autoload-dev.psr-4`, add:
```json
"Modules\\IdentityAccess\\Tests\\": "Modules/IdentityAccess/tests/",
"Modules\\CatalogDelivery\\Tests\\": "Modules/CatalogDelivery/tests/",
"Modules\\MarketplacePipeline\\Tests\\": "Modules/MarketplacePipeline/tests/",
"Modules\\PartnerHub\\Tests\\": "Modules/PartnerHub/tests/",
"Modules\\TelemetryPipeline\\Tests\\": "Modules/TelemetryPipeline/tests/"
```

- [ ] **Step 4: Verify each module composer.json**

Each `Modules/<M>/composer.json` must contain:
```json
{
    "name": "smartshop/<lowercasename>",
    "autoload": {
        "psr-4": {
            "Modules\\<ModuleName>\\": "app/",
            "Modules\\<ModuleName>\\Database\\Factories\\": "database/factories/",
            "Modules\\<ModuleName>\\Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Modules\\<ModuleName>\\Tests\\": "tests/"
        }
    },
    "extra": {
        "laravel": {
            "providers": [],
            "aliases": {}
        }
    }
}
```
Adjust the generated files to match.

- [ ] **Step 5: Dump autoload + verify**

```bash
composer dump-autoload
php artisan tinker --execute="var_dump(class_exists('Modules\\IdentityAccess\\Providers\\IdentityAccessServiceProvider'));"
```
Expected: `bool(true)`.

- [ ] **Step 6: Commit**

```bash
git add composer.json Modules/*/composer.json composer.lock
git commit -m "build: module autoload + composer merge-plugin wiring"
```

### Task 1.4: Per-module Vite assets + root loader aggregation

**Files:**
- Create: `vite-module-loader.js`, `Modules/<M>/vite.config.js` (5), `Modules/<M>/resources/assets/{js,scss}/.gitkeep`

**Interfaces:**
- Produces: each module's `vite.config.js` exports `paths` (project-root-relative asset paths); root `vite-module-loader.js` collects `paths` from modules whose `modules_statuses.json` flag is `true`; root `vite.config.js` spreads them into the single `laravel()` input. One root `npm run build` emits one manifest containing core + module assets (deviation from Atlas's per-module outDir — same aggregation spirit, avoids broken relative outDir paths; documented in §15.4 of PROJECT_ARCHITECTURE.md).

- [ ] **Step 1: Create the loader**

Create `vite-module-loader.js`:
```javascript
import fs from 'fs/promises';
import path from 'path';
import { pathToFileURL } from 'url';

export async function collectModuleAssetsPaths(paths, modulesPath) {
    modulesPath = path.join(__dirname, modulesPath);

    const moduleStatusesPath = path.join(__dirname, 'modules_statuses.json');

    try {
        const moduleStatusesContent = await fs.readFile(moduleStatusesPath, 'utf-8');
        const moduleStatuses = JSON.parse(moduleStatusesContent);

        const moduleDirectories = await fs.readdir(modulesPath);

        for (const moduleDir of moduleDirectories) {
            if (moduleDir === '.DS_Store') {
                continue;
            }

            if (moduleStatuses[moduleDir] === true) {
                const viteConfigPath = path.join(modulesPath, moduleDir, 'vite.config.js');

                try {
                    await fs.access(viteConfigPath);
                    const moduleConfigURL = pathToFileURL(viteConfigPath);
                    const moduleConfig = await import(moduleConfigURL.href);

                    if (moduleConfig.paths && Array.isArray(moduleConfig.paths)) {
                        paths.push(...moduleConfig.paths);
                    }
                } catch (error) {
                    // vite.config.js does not exist, skip this module
                }
            }
        }
    } catch (error) {
        // modules_statuses.json does not exist, skip module assets
    }

    return paths;
}
```

- [ ] **Step 2: Create the module Vite configs**

Create `Modules/<M>/vite.config.js` for each of the five modules (same content, different module dir), e.g. for IdentityAccess:
```javascript
export default {
    paths: [
        'Modules/IdentityAccess/resources/assets/js/app.js',
        'Modules/IdentityAccess/resources/assets/scss/app.scss',
    ],
};
```

- [ ] **Step 3: Create module asset dirs**

Run:
```bash
for m in IdentityAccess CatalogDelivery MarketplacePipeline PartnerHub TelemetryPipeline; do
  mkdir -p "Modules/$m/resources/assets/js" "Modules/$m/resources/assets/scss"
  touch "Modules/$m/resources/assets/js/.gitkeep" "Modules/$m/resources/assets/scss/.gitkeep"
done
```

- [ ] **Step 4: Update root vite.config.js**

Replace the contents of `vite.config.js`:
```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { collectModuleAssetsPaths } from './vite-module-loader';

async function getModulePaths() {
    const paths = [];
    await collectModuleAssetsPaths(paths, 'Modules');
    return paths;
}

export default defineConfig(async () => {
    const modulePaths = await getModulePaths();

    return {
        plugins: [
            laravel({
                input: [
                    'resources/css/app.css',
                    'resources/js/app.js',
                    ...modulePaths,
                ],
                refresh: true,
            }),
            tailwindcss(),
        ],
        server: {
            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
```

- [ ] **Step 5: Verify build**

```bash
npm run build
```
Expected: build succeeds; `public/build/manifest.json` contains entries for `resources/css/app.css`, `resources/js/app.js`, and all five module asset pairs.

- [ ] **Step 6: Commit**

```bash
git add vite.config.js vite-module-loader.js Modules/*/vite.config.js Modules/*/resources/assets
git commit -m "build(vite): per-module asset aggregation via vite-module-loader"
```

### Task 1.5: Per-module test suites in phpunit.xml

**Files:**
- Modify: `phpunit.xml`

**Interfaces:**
- Produces: `php artisan test` discovers `Modules/*/tests/{Feature,Unit}`.

- [ ] **Step 1: Extend testsuites**

In `phpunit.xml`, inside the `Unit` and `Feature` testsuites, add module directories:
```xml
<testsuite name="Unit">
    <directory>tests/Unit</directory>
    <directory>Modules/*/tests/Unit</directory>
</testsuite>
<testsuite name="Feature">
    <directory>tests/Feature</directory>
    <directory>Modules/*/tests/Feature</directory>
</testsuite>
```

- [ ] **Step 2: Verify**

Run: `php artisan test`
Expected: full suite still passes; no test discovery errors.

- [ ] **Step 3: Commit**

```bash
git add phpunit.xml
git commit -m "test: per-module phpunit suites"
```

### Task 1.6: Verify boot with all modules enabled

- [ ] **Step 1: Route list + boot check**

Run:
```bash
php artisan route:list > /tmp/routes_before.txt
php artisan test
php artisan module:list
```
Expected: all route names from §7 of PROJECT_ARCHITECTURE.md present; tests green; 5 modules enabled.

- [ ] **Step 2: Commit any stragglers**

```bash
git status --short
git add -A && git commit -m "chore: phase 1 scaffolding complete"
```

---

## Phase 2 — IdentityAccess module

### Task 2.1: Move User + Wishlist models into IdentityAccess

**Files:**
- Move: `app/Models/User.php`, `app/Models/Wishlist.php` → `Modules/IdentityAccess/app/Models/`

**Interfaces:**
- Produces: `Modules\IdentityAccess\Models\User` (with `wishlists()` relation from Task 0.3), `Modules\IdentityAccess\Models\Wishlist`. Consumed by every later module.

- [ ] **Step 1: Move the files**

```bash
mkdir -p Modules/IdentityAccess/app/Models
git mv app/Models/User.php Modules/IdentityAccess/app/Models/User.php
git mv app/Models/Wishlist.php Modules/IdentityAccess/app/Models/Wishlist.php
```

- [ ] **Step 2: Fix namespaces**

In both moved files change the namespace line to `namespace Modules\IdentityAccess\Models;`. In `User.php`, the `wishlists()` relation uses `Wishlist::class` — resolves in-namespace; remove any `use App\Models\Wishlist;` import if present.

- [ ] **Step 3: Update all references project-wide**

```bash
rg -l "App\\\\Models\\\\User" app routes resources tests database | xargs -r sed -i 's|App\\Models\\User|Modules\\IdentityAccess\\Models\\User|g'
rg -l "App\\\\Models\\\\Wishlist" app routes resources tests database | xargs -r sed -i 's|App\\Models\\Wishlist|Modules\\IdentityAccess\\Models\\Wishlist|g'
```
Check for fully-qualified usages too:
```bash
rg -l "\\\\App\\\\Models\\\\User" app routes resources tests database | xargs -r sed -i 's|\\App\\Models\\User|\\Modules\\IdentityAccess\\Models\\User|g'
rg -l "\\\\App\\\\Models\\\\Wishlist" app routes resources tests database | xargs -r sed -i 's|\\App\\Models\\Wishlist|\\Modules\\IdentityAccess\\Models\\Wishlist|g'
```

- [ ] **Step 4: Verify**

```bash
composer dump-autoload
php artisan test --filter=WishlistTest
```
Expected: PASS. `rg "App\\\\Models\\\\User" app routes resources tests database` returns nothing.

- [ ] **Step 5: Commit**

```bash
git add -A Modules/IdentityAccess app routes resources tests database
git commit -m "refactor(identityaccess): move User + Wishlist models into module"
```

### Task 2.2: Move identity controllers + middleware into IdentityAccess

**Files:**
- Move: `app/Http/Controllers/{AuthController,UserController,AdminUserController,WishlistController}.php` → `Modules/IdentityAccess/app/Http/Controllers/`
- Move: `app/Http/Middleware/{AdminMiddleware,PartnerMiddleware}.php` → `Modules/IdentityAccess/app/Http/Middleware/`
- Move: `app/Mail/{WelcomeMember,UserStatusUpdated}.php` → `Modules/IdentityAccess/app/Mail/`
- Move: `resources/views/emails/members/welcome.blade.php`, `resources/views/emails/user_status_updated.blade.php` → `Modules/IdentityAccess/resources/views/emails/...`
- Modify: `bootstrap/app.php` (alias targets)

**Interfaces:**
- Produces: `Modules\IdentityAccess\Http\Controllers\{AuthController,UserController,AdminUserController,WishlistController}`, `Modules\IdentityAccess\Http\Middleware\{AdminMiddleware,PartnerMiddleware}`, `Modules\IdentityAccess\Mail\{WelcomeMember,UserStatusUpdated}`. Route names unchanged. Middleware aliases `admin`/`partner` point into the module.

- [ ] **Step 1: Move the files**

```bash
git mv app/Http/Controllers/AuthController.php Modules/IdentityAccess/app/Http/Controllers/AuthController.php
git mv app/Http/Controllers/UserController.php Modules/IdentityAccess/app/Http/Controllers/UserController.php
git mv app/Http/Controllers/AdminUserController.php Modules/IdentityAccess/app/Http/Controllers/AdminUserController.php
git mv app/Http/Controllers/WishlistController.php Modules/IdentityAccess/app/Http/Controllers/WishlistController.php
git mv app/Http/Middleware/AdminMiddleware.php Modules/IdentityAccess/app/Http/Middleware/AdminMiddleware.php
git mv app/Http/Middleware/PartnerMiddleware.php Modules/IdentityAccess/app/Http/Middleware/PartnerMiddleware.php
git mv app/Mail/WelcomeMember.php Modules/IdentityAccess/app/Mail/WelcomeMember.php
git mv app/Mail/UserStatusUpdated.php Modules/IdentityAccess/app/Mail/UserStatusUpdated.php
mkdir -p Modules/IdentityAccess/resources/views/emails/members
git mv resources/views/emails/members/welcome.blade.php Modules/IdentityAccess/resources/views/emails/members/welcome.blade.php
git mv resources/views/emails/user_status_updated.blade.php Modules/IdentityAccess/resources/views/emails/user_status_updated.blade.php
```

- [ ] **Step 2: Fix namespaces + imports in the moved files**

For each moved file:
- Controllers: `namespace App\Http\Controllers;` → `namespace Modules\IdentityAccess\Http\Controllers;`; `use App\Http\Controllers\Controller;` stays (base controller is Core); `use App\Models\User;` → `use Modules\IdentityAccess\Models\User;`; `use App\Models\Wishlist;` → `use Modules\IdentityAccess\Models\Wishlist;`.
- Middleware: namespace → `Modules\IdentityAccess\Http\Middleware`.
- Mailables: namespace → `Modules\IdentityAccess\Mail`; view strings: `WelcomeMember` → `identityaccess::emails.members.welcome`, `UserStatusUpdated` → `identityaccess::emails.user_status_updated`.

- [ ] **Step 3: Update bootstrap/app.php aliases**

In `bootstrap/app.php`, change the alias block to:
```php
    $middleware->alias([
        'admin' => \Modules\IdentityAccess\Http\Middleware\AdminMiddleware::class,
        'partner' => \Modules\IdentityAccess\Http\Middleware\PartnerMiddleware::class,
    ]);
```

- [ ] **Step 4: Update remaining references**

```bash
rg -l "App\\\\Http\\\\Controllers\\\\AuthController|App\\\\Http\\\\Controllers\\\\UserController|App\\\\Http\\\\Controllers\\\\AdminUserController|App\\\\Http\\\\Controllers\\\\WishlistController|App\\\\Http\\\\Middleware\\\\AdminMiddleware|App\\\\Http\\\\Middleware\\\\PartnerMiddleware|App\\\\Mail\\\\WelcomeMember|App\\\\Mail\\\\UserStatusUpdated" app routes resources tests database | xargs -r sed -i -E 's|App\\(Http\\(Controllers\|Http\\(Middleware\|Mail)\\(AuthController\|UserController\|AdminUserController\|WishlistController\|AdminMiddleware\|PartnerMiddleware\|WelcomeMember\|UserStatusUpdated)|Modules\\IdentityAccess\\\1\\\2|g'
```
Manually verify with `rg` after the sed that no `App\Http\Controllers\AuthController` etc. remain.

- [ ] **Step 5: Verify**

```bash
composer dump-autoload
php artisan test --filter=WishlistTest
```
Expected: tests pass. (`route:list` may show stale class targets until Task 2.3 — that's expected and harmless.)

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "refactor(identityaccess): move auth/user/member controllers, middleware, mailers"
```

### Task 2.3: Move identity routes into the module

**Files:**
- Create: `Modules/IdentityAccess/routes/web.php`, `Modules/IdentityAccess/routes/api.php`, `Modules/IdentityAccess/app/Providers/RouteServiceProvider.php`
- Modify: `routes/web.php` (prune), `routes/api.php` (prune), `Modules/IdentityAccess/module.json`, `Modules/IdentityAccess/app/Providers/IdentityAccessServiceProvider.php`

**Interfaces:**
- Produces: module web.php registers (names identical): `login`, `signup` (guest); `POST /createaccount`, `POST /accessaccount`, `logout`; `profile`, `profile.update`, `profile.wishlist`, `wishlist.toggle` (auth); `admin.dashboard`, `admin.users.*` + `admin.users.approve` (auth+admin). Module api.php registers `/api/login`, `/api/register`, `/api/user`. Module `RouteServiceProvider` loads them under `web` / `api` middleware groups.

- [ ] **Step 1: Create the module RouteServiceProvider**

Create `Modules/IdentityAccess/app/Providers/RouteServiceProvider.php`:
```php
<?php

namespace Modules\IdentityAccess\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'IdentityAccess';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
    }

    protected function mapApiRoutes(): void
    {
        Route::middleware('api')->prefix('api')->name('api.')->group(module_path($this->name, '/routes/api.php'));
    }
}
```

- [ ] **Step 2: Register providers in module.json + ModuleServiceProvider**

Edit `Modules/IdentityAccess/module.json` so `providers` contains:
```json
"providers": [
    "Modules\\IdentityAccess\\Providers\\IdentityAccessServiceProvider"
]
```
Edit `Modules/IdentityAccess/app/Providers/IdentityAccessServiceProvider.php` so the class has:
```php
    protected array $providers = [
        RouteServiceProvider::class,
    ];
```
(Keep the generated `$name`/`$nameLower`.)

- [ ] **Step 3: Create the module web routes**

Create `Modules/IdentityAccess/routes/web.php`:
```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\IdentityAccess\Http\Controllers\AdminUserController;
use Modules\IdentityAccess\Http\Controllers\AuthController;
use Modules\IdentityAccess\Http\Controllers\UserController;
use Modules\IdentityAccess\Http\Controllers\WishlistController;

Route::middleware('guest')->group(function () {
    Route::get('/login', fn() => view('identityaccess::auth.login'))->name('login');
    Route::get('/signup', fn() => view('identityaccess::auth.signup'))->name('signup');
});

Route::post('/createaccount', [AuthController::class, 'register']);
Route::post('/accessaccount', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [UserController::class, 'show'])->name('profile');
    Route::put('/profile/update', [UserController::class, 'updateProfile'])->name('profile.update');

    Route::get('/archive', [WishlistController::class, 'index'])->name('profile.wishlist');
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
});

Route::middleware(['auth', 'admin'])->prefix('admin')->as('admin.')->group(function () {
    Route::get('/dashboard', [\Modules\IdentityAccess\Http\Controllers\AdminDashboardController::class, 'index'])->name('dashboard');
    Route::resource('users', AdminUserController::class)->except(['create', 'store', 'show'])->names([
        'index' => 'users.index',
        'edit' => 'users.edit',
        'update' => 'users.update',
        'destroy' => 'users.destroy',
    ]);
    Route::post('/users/{id}/approve', [AdminUserController::class, 'approve'])->name('users.approve');
});
```
**Note:** the admin dashboard controller moves in Task 2.5. Complete Tasks 2.3–2.5 in one working session before running the app to avoid a broken intermediate state.

- [ ] **Step 4: Create the module API routes**

Create `Modules/IdentityAccess/routes/api.php`:
```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\IdentityAccess\Http\Controllers\AuthController;

Route::post('/login', [AuthController::class, 'apiLogin']);
Route::post('/register', [AuthController::class, 'apiRegister']);
Route::get('/user', function (Illuminate\Http\Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
```
Remove the old `/api/login`, `/api/register`, `/api/user` from `routes/api.php` (keep the catalog closure — it moves to CatalogDelivery in Task 3.5).

- [ ] **Step 5: Prune routes/web.php**

Remove from `routes/web.php`: the guest group (`login`/`signup`), the three auth POST routes, `profile` + `profile.update`, the wishlist routes (`/archive`, `/wishlist/toggle`), the admin `dashboard` route, the admin `users` resource + `users.approve`. Keep: public routes, cart/orders/paypal (Phase 5), admin products/categories/partners/reviews/payouts (Phases 3–4), partner group (Phases 3–4).

- [ ] **Step 6: Verify**

```bash
composer dump-autoload
php artisan route:list | grep -E "login|signup|profile|archive|wishlist|admin/users|admin.dashboard"
php artisan test --filter=WishlistTest
```
Expected: routes present with unchanged names; WishlistTest passes.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "refactor(identityaccess): module routes (web + api) for auth, profile, wishlist, members"
```

### Task 2.4: Move identity views into the module

**Files:**
- Move: `resources/views/auth/{login,signup}.blade.php` → `Modules/IdentityAccess/resources/views/auth/`
- Move: `resources/views/users/{show,index,edit}.blade.php` → `Modules/IdentityAccess/resources/views/users/`
- Move: `resources/views/wishlist.blade.php` → `Modules/IdentityAccess/resources/views/`
- Move: `resources/views/admin/users/index.blade.php` → `Modules/IdentityAccess/resources/views/admin/users/`

**Interfaces:**
- Produces: module views reachable as `identityaccess::auth.login`, `identityaccess::users.show`, `identityaccess::wishlist`, `identityaccess::admin.users.index`, etc. Controllers updated to those view names.

- [ ] **Step 1: Move the views**

```bash
mkdir -p Modules/IdentityAccess/resources/views/auth
mkdir -p Modules/IdentityAccess/resources/views/users
mkdir -p Modules/IdentityAccess/resources/views/admin/users
git mv resources/views/auth/login.blade.php Modules/IdentityAccess/resources/views/auth/login.blade.php
git mv resources/views/auth/signup.blade.php Modules/IdentityAccess/resources/views/auth/signup.blade.php
git mv resources/views/users/show.blade.php Modules/IdentityAccess/resources/views/users/show.blade.php
git mv resources/views/users/index.blade.php Modules/IdentityAccess/resources/views/users/index.blade.php
git mv resources/views/users/edit.blade.php Modules/IdentityAccess/resources/views/users/edit.blade.php
git mv resources/views/wishlist.blade.php Modules/IdentityAccess/resources/views/wishlist.blade.php
git mv resources/views/admin/users/index.blade.php Modules/IdentityAccess/resources/views/admin/users/index.blade.php
```

- [ ] **Step 2: Update controller view references**

- `UserController@show`: `view('users.show', ...)` → `view('identityaccess::users.show', ...)`.
- `WishlistController@index`: `view('wishlist', ...)` → `view('identityaccess::wishlist', ...)`.
- `AdminUserController@index`: `view('admin.users.index', ...)` → `view('identityaccess::admin.users.index', ...)`.
- (Auth views referenced by module route closures — already `identityaccess::auth.*` from Task 2.3.)

- [ ] **Step 3: Verify**

```bash
php artisan test
php artisan serve --port=8000 &
curl -s http://127.0.0.1:8000/login | grep -c "Member"
kill %1
```
Expected: login page renders; no view-not-found errors.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "refactor(identityaccess): move auth/profile/wishlist/member views into module"
```

### Task 2.5: Move admin dashboard + GovernanceService into IdentityAccess

**Files:**
- Move: `app/Http/Controllers/AdminDashboardController.php` → `Modules/IdentityAccess/app/Http/Controllers/`
- Move: `resources/views/admin/dashboard.blade.php` → `Modules/IdentityAccess/resources/views/admin/dashboard.blade.php`
- Create: `Modules/IdentityAccess/app/Services/GovernanceService.php`

**Interfaces:**
- Produces: `GovernanceService::getDashboardMetrics(): array` returning the admin dashboard dataset; `AdminDashboardController@index` becomes a thin wrapper. Dashboard view `identityaccess::admin.dashboard`. (Order/Product/Review model imports finalize in Task 5.1 Step 3.)

- [ ] **Step 1: Move controller + view**

```bash
git mv app/Http/Controllers/AdminDashboardController.php Modules/IdentityAccess/app/Http/Controllers/AdminDashboardController.php
git mv resources/views/admin/dashboard.blade.php Modules/IdentityAccess/resources/views/admin/dashboard.blade.php
```
Fix the controller namespace → `Modules\IdentityAccess\Http\Controllers`. Keep model imports pointing at the CURRENT paths (`App\Models\Order|Product|Review`) — finalized in Task 5.1 Step 3.

- [ ] **Step 2: Create GovernanceService**

Create `Modules/IdentityAccess/app/Services/GovernanceService.php` — move the query logic verbatim from `AdminDashboardController@index` (stats: revenue, active orders, catalog size, members, low stock <5, pending reviews, pending users, 5 recent orders) into:
```php
<?php

namespace Modules\IdentityAccess\Services;

class GovernanceService
{
    public function getDashboardMetrics(): array
    {
        // [move the exact queries from AdminDashboardController@index here,
        //  returning the same keys the controller previously compact()'d]
    }
}
```

- [ ] **Step 3: Thin the controller**

Replace the body of `AdminDashboardController@index` with:
```php
    public function index(\Modules\IdentityAccess\Services\GovernanceService $governance)
    {
        $metrics = $governance->getDashboardMetrics();

        return view('identityaccess::admin.dashboard', $metrics);
    }
```
Keep the SAME variable names the view uses.

- [ ] **Step 4: Verify**

```bash
php artisan test
php artisan route:list --name=admin.dashboard
```
Expected: green; route present.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "refactor(identityaccess): admin dashboard + GovernanceService"
```

### Task 2.6: IdentityAccess phase verification

- [ ] **Step 1: Full verification**

Run:
```bash
php artisan test
php artisan route:list | wc -l
rg -n "App\\\\Models\\\\User|App\\\\Models\\\\Wishlist|App\\\\Http\\\\Controllers\\\\AuthController|App\\\\Http\\\\Controllers\\\\AdminUserController|App\\\\Http\\\\Controllers\\\\WishlistController" app routes resources tests database Modules | grep -v Modules || true
```
Expected: tests green; zero stale references outside `Modules/`.

- [ ] **Step 2: Commit**

```bash
git add -A && git commit -m "chore: phase 2 identityaccess complete"
```

---

## Phase 3 — CatalogDelivery module

### Task 3.1: Move catalog models + factories

**Files:**
- Move: `app/Models/{Product,Category,ProductImage,ProductVariant,Review}.php` → `Modules/CatalogDelivery/app/Models/`
- Move: `database/factories/{ProductFactory,CategoryFactory}.php` → `Modules/CatalogDelivery/database/factories/`

**Interfaces:**
- Produces: `Modules\CatalogDelivery\Models\{Product,Category,ProductImage,ProductVariant,Review}`; factories `Modules\CatalogDelivery\Database\Factories\{ProductFactory,CategoryFactory}`.

- [ ] **Step 1: Move models + factories**

```bash
mkdir -p Modules/CatalogDelivery/app/Models
git mv app/Models/Product.php Modules/CatalogDelivery/app/Models/Product.php
git mv app/Models/Category.php Modules/CatalogDelivery/app/Models/Category.php
git mv app/Models/ProductImage.php Modules/CatalogDelivery/app/Models/ProductImage.php
git mv app/Models/ProductVariant.php Modules/CatalogDelivery/app/Models/ProductVariant.php
git mv app/Models/Review.php Modules/CatalogDelivery/app/Models/Review.php
mkdir -p Modules/CatalogDelivery/database/factories
git mv database/factories/ProductFactory.php Modules/CatalogDelivery/database/factories/ProductFactory.php
git mv database/factories/CategoryFactory.php Modules/CatalogDelivery/database/factories/CategoryFactory.php
```

- [ ] **Step 2: Fix namespaces + imports**

Each moved model → `namespace Modules\CatalogDelivery\Models;`; internal imports (`use App\Models\...` → `Modules\CatalogDelivery\Models\...`; `use App\Models\User;` → `Modules\IdentityAccess\Models\User;`). The two factories → `namespace Modules\CatalogDelivery\Database\Factories;`, model imports → `Modules\CatalogDelivery\Models\Product` / `Category`.

- [ ] **Step 3: Update all references**

```bash
rg -l "App\\\\Models\\\\Product|App\\\\Models\\\\Category|App\\\\Models\\\\ProductImage|App\\\\Models\\\\ProductVariant|App\\\\Models\\\\Review|Database\\\\Factories\\\\ProductFactory|Database\\\\Factories\\\\CategoryFactory" app routes resources tests database | xargs -r sed -i -E 's|App\\Models\\(Product\|Category\|ProductImage\|ProductVariant\|Review)|Modules\\CatalogDelivery\\Models\\\1|g; s|Database\\Factories\\(ProductFactory\|CategoryFactory)|Modules\\CatalogDelivery\\Database\\Factories\\\1|g'
rg -l "\\\\App\\\\Models\\\\Product|\\\\App\\\\Models\\\\Review" app routes resources tests database | xargs -r sed -i -E 's|\\App\\Models\\(Product\|Review)|\\Modules\\CatalogDelivery\\Models\\\1|g'
```
Also fix seeders' imports (`database/seeders/{ProductSeeder,CategorySeeder,ReviewSeeder,OrderSeeder,DatabaseSeeder}.php` — `use Modules\CatalogDelivery\Models\...;` replacing `App\Models\...`).

- [ ] **Step 4: Verify**

```bash
composer dump-autoload
php artisan test --filter="WishlistTest|ReviewSubmissionTest"
```
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "refactor(catalogdelivery): move product/category/media/review models + factories"
```

### Task 3.2: Move catalog controllers + requests + policies

**Files:**
- Move: `app/Http/Controllers/{ProductController,CategoryController,PartnerInventoryController,ReviewController,ViewController}.php` → `Modules/CatalogDelivery/app/Http/Controllers/`
- Move: `app/Http/Requests/{StoreProductRequest,UpdateProductRequest}.php` → `Modules/CatalogDelivery/app/Http/Requests/`
- Move: `app/Policies/{ProductPolicy,ReviewPolicy}.php` → `Modules/CatalogDelivery/app/Policies/`

**Interfaces:**
- Produces: `Modules\CatalogDelivery\Http\Controllers\{ProductController,CategoryController,PartnerInventoryController,ReviewController,ViewController}`; requests `Modules\CatalogDelivery\Http\Requests\{StoreProductRequest,UpdateProductRequest}`; policies `Modules\CatalogDelivery\Policies\{ProductPolicy,ReviewPolicy}`. Note: `ViewController@partnerProfile` + the `artisan-profile` route move to PartnerHub in Tasks 4.2–4.3 — leave them in place until then.

- [ ] **Step 1: Move the files**

```bash
mkdir -p Modules/CatalogDelivery/app/Http/Controllers
mkdir -p Modules/CatalogDelivery/app/Http/Requests
mkdir -p Modules/CatalogDelivery/app/Policies
git mv app/Http/Controllers/ProductController.php Modules/CatalogDelivery/app/Http/Controllers/ProductController.php
git mv app/Http/Controllers/CategoryController.php Modules/CatalogDelivery/app/Http/Controllers/CategoryController.php
git mv app/Http/Controllers/PartnerInventoryController.php Modules/CatalogDelivery/app/Http/Controllers/PartnerInventoryController.php
git mv app/Http/Controllers/ReviewController.php Modules/CatalogDelivery/app/Http/Controllers/ReviewController.php
git mv app/Http/Controllers/ViewController.php Modules/CatalogDelivery/app/Http/Controllers/ViewController.php
git mv app/Http/Requests/StoreProductRequest.php Modules/CatalogDelivery/app/Http/Requests/StoreProductRequest.php
git mv app/Http/Requests/UpdateProductRequest.php Modules/CatalogDelivery/app/Http/Requests/UpdateProductRequest.php
git mv app/Policies/ProductPolicy.php Modules/CatalogDelivery/app/Policies/ProductPolicy.php
git mv app/Policies/ReviewPolicy.php Modules/CatalogDelivery/app/Policies/ReviewPolicy.php
```

- [ ] **Step 2: Fix namespaces + imports**

- Controllers: `namespace Modules\CatalogDelivery\Http\Controllers;`; `use App\Http\Controllers\Controller;` stays; model imports → `Modules\CatalogDelivery\Models\*`, `Modules\IdentityAccess\Models\User` where used; `PartnerInventoryController`'s `getPartner()` helper uses `App\Models\Partner` → keep temporarily, fix to `Modules\PartnerHub\Models\Partner` in Task 4.1 Step 3.
- Requests: `namespace Modules\CatalogDelivery\Http\Requests;`.
- Policies: `namespace Modules\CatalogDelivery\Policies;`.
- View strings: `ReviewController` → `catalogdelivery::admin.reviews.index` (leave dead `reviews.create`/`reviews.edit` — deleted in Task 7.1); `ProductController`/`CategoryController` views → `catalogdelivery::admin.products.*`, `catalogdelivery::admin.categories.*`; `PartnerInventoryController` → `catalogdelivery::partner.inventory.*`; `ViewController` → `catalogdelivery::home|shop|product|about|contact` (leave `partner_profile` bare until Task 4.2).

- [ ] **Step 3: Update remaining references**

```bash
rg -l "App\\\\Http\\\\Controllers\\\\ProductController|App\\\\Http\\\\Controllers\\\\CategoryController|App\\\\Http\\\\Controllers\\\\PartnerInventoryController|App\\\\Http\\\\Controllers\\\\ReviewController|App\\\\Http\\\\Controllers\\\\ViewController|App\\\\Http\\\\Requests\\\\StoreProductRequest|App\\\\Http\\\\Requests\\\\UpdateProductRequest|App\\\\Policies\\\\ProductPolicy|App\\\\Policies\\\\ReviewPolicy" app routes resources tests database | xargs -r sed -i -E 's|App\\(Http\\(Controllers\|Http\\(Requests\|Policies)\\(ProductController\|CategoryController\|PartnerInventoryController\|ReviewController\|ViewController\|StoreProductRequest\|UpdateProductRequest\|ProductPolicy\|ReviewPolicy)|Modules\\CatalogDelivery\\\1\\\2|g'
```

- [ ] **Step 4: Verify**

```bash
composer dump-autoload
php artisan test
```
Expected: green (routes still target old class paths until Task 3.5 — run the full suite AFTER Task 3.5 in the same session).

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "refactor(catalogdelivery): move catalog/media/review controllers, requests, policies"
```

### Task 3.3: Extract CatalogQueryService (no SQL in blades)

**Files:**
- Create: `Modules/CatalogDelivery/app/Services/CatalogQueryService.php`
- Modify: `Modules/CatalogDelivery/app/Http/Controllers/ViewController.php`, `resources/views/product.blade.php` (related-products query)

**Interfaces:**
- Produces: `CatalogQueryService::home(): array{featuredProducts, latestProducts}`, `CatalogQueryService::shop(Request): \Illuminate\Pagination\LengthAwarePaginator`, `CatalogQueryService::product(int $id): array{product, reviews, relatedProducts}`, `CatalogQueryService::related(Product $product, int $limit = 4)`.

- [ ] **Step 1: Create the service**

Create `Modules/CatalogDelivery/app/Services/CatalogQueryService.php` with the query logic moved verbatim from `ViewController@home`, `@shop`, `@product` (home: latest 8 + featured 6 in-stock; shop: search/category/price filters + sort + paginate 12; product: approved reviews + related):
```php
<?php

namespace Modules\CatalogDelivery\Services;

use Modules\CatalogDelivery\Models\Product;

class CatalogQueryService
{
    public function home(): array { /* move home queries here */ }

    public function shop(\Illuminate\Http\Request $request): \Illuminate\Pagination\LengthAwarePaginator { /* move shop query here */ }

    public function product(int $id): array
    {
        $product = Product::with(['images', 'category'])->findOrFail($id);
        $reviews = $product->reviews()->where('status', 'approved')->latest()->get();
        $relatedProducts = $this->related($product);

        return compact('product', 'reviews', 'relatedProducts');
    }

    public function related(Product $product, int $limit = 4): \Illuminate\Database\Eloquent\Collection
    {
        return Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->inRandomOrder()
            ->limit($limit)
            ->get();
    }
}
```
Match the exact eager loads/variables the original controller used (read `ViewController.php` first).

- [ ] **Step 2: Thin ViewController**

`ViewController` methods become:
```php
    public function home(CatalogQueryService $catalog)
    {
        return view('catalogdelivery::home', $catalog->home());
    }

    public function shop(Request $request, CatalogQueryService $catalog)
    {
        return view('catalogdelivery::shop', ['products' => $catalog->shop($request)]);
    }

    public function product($id, CatalogQueryService $catalog)
    {
        return view('catalogdelivery::product', $catalog->product($id));
    }
```
Keep `about()`/`contact()` as-is.

- [ ] **Step 3: Remove the inline query from product.blade.php**

In `resources/views/product.blade.php`, find the related-products `@php`/`Product::where(...)` block and delete it — the controller now passes `$relatedProducts` (verify the view iterates `$relatedProducts`).

- [ ] **Step 4: Verify**

```bash
php artisan test --filter=ReviewSubmissionTest
```
Full app checks pass after Task 3.5 wiring.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "refactor(catalogdelivery): CatalogQueryService; remove SQL from blades"
```

### Task 3.4: Move catalog views into the module

**Files:**
- Move (into `Modules/CatalogDelivery/resources/views/`): `home.blade.php`, `shop.blade.php`, `product.blade.php`, `about.blade.php`, `contact.blade.php`, `components/product-card.blade.php`, `products/{index,create,edit}.blade.php`, `categories/{index,create,edit}.blade.php`, `admin/reviews/index.blade.php`, `partner/inventory/{index,create,edit}.blade.php`

**Interfaces:**
- Produces: module views `catalogdelivery::home|shop|product|about|contact|components.product-card|admin.products.*|admin.categories.*|admin.reviews.index|partner.inventory.*`. `<x-product-card>` usages change to `<x-catalogdelivery::product-card>` everywhere.

- [ ] **Step 1: Move the views**

```bash
mkdir -p Modules/CatalogDelivery/resources/views/products
mkdir -p Modules/CatalogDelivery/resources/views/categories
mkdir -p Modules/CatalogDelivery/resources/views/admin/reviews
mkdir -p Modules/CatalogDelivery/resources/views/partner/inventory
mkdir -p Modules/CatalogDelivery/resources/views/components
git mv resources/views/home.blade.php Modules/CatalogDelivery/resources/views/home.blade.php
git mv resources/views/shop.blade.php Modules/CatalogDelivery/resources/views/shop.blade.php
git mv resources/views/product.blade.php Modules/CatalogDelivery/resources/views/product.blade.php
git mv resources/views/about.blade.php Modules/CatalogDelivery/resources/views/about.blade.php
git mv resources/views/contact.blade.php Modules/CatalogDelivery/resources/views/contact.blade.php
git mv resources/views/components/product-card.blade.php Modules/CatalogDelivery/resources/views/components/product-card.blade.php
git mv resources/views/products/index.blade.php Modules/CatalogDelivery/resources/views/products/index.blade.php
git mv resources/views/products/create.blade.php Modules/CatalogDelivery/resources/views/products/create.blade.php
git mv resources/views/products/edit.blade.php Modules/CatalogDelivery/resources/views/products/edit.blade.php
git mv resources/views/categories/index.blade.php Modules/CatalogDelivery/resources/views/categories/index.blade.php
git mv resources/views/categories/create.blade.php Modules/CatalogDelivery/resources/views/categories/create.blade.php
git mv resources/views/categories/edit.blade.php Modules/CatalogDelivery/resources/views/categories/edit.blade.php
git mv resources/views/admin/reviews/index.blade.php Modules/CatalogDelivery/resources/views/admin/reviews/index.blade.php
git mv resources/views/partner/inventory/index.blade.php Modules/CatalogDelivery/resources/views/partner/inventory/index.blade.php
git mv resources/views/partner/inventory/create.blade.php Modules/CatalogDelivery/resources/views/partner/inventory/create.blade.php
git mv resources/views/partner/inventory/edit.blade.php Modules/CatalogDelivery/resources/views/partner/inventory/edit.blade.php
```

- [ ] **Step 2: Update component usages**

```bash
rg -l "<x-product-card" app routes resources Modules | xargs -r sed -i 's|<x-product-card|<x-catalogdelivery::product-card|g'
```
This touches `home`, `shop`, `product`, `wishlist` (IdentityAccess), `partner_profile` (PartnerHub later) views.

- [ ] **Step 3: Move inline CSS to module assets**

For each moved view containing a `<style>` block, extract the block into `Modules/CatalogDelivery/resources/assets/scss/app.scss` (create it; scope each section with a comment) and remove it from the blade. Static `style="..."` attributes → scss classes; keep only dynamic ones. Same procedure in Task 5.4 (MarketplacePipeline) and Phase 6 audit for stragglers.

- [ ] **Step 4: Verify**

```bash
php artisan test
```
(App wiring completes in Task 3.5 — run checks after it.)

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "refactor(catalogdelivery): move catalog/admin-media/partner-inventory views; css to assets"
```

### Task 3.5: Move catalog routes into the module

**Files:**
- Create: `Modules/CatalogDelivery/routes/web.php`, `Modules/CatalogDelivery/routes/api.php`, `Modules/CatalogDelivery/app/Providers/RouteServiceProvider.php`
- Modify: `routes/web.php` (prune), `routes/api.php` (remove catalog closure), `Modules/CatalogDelivery/module.json` + `CatalogDeliveryServiceProvider::$providers`

**Interfaces:**
- Produces: module routes — public: `home`, `shop`, `product.show`, `about`, `contact`; admin: `admin.products.*` (incl. `reorder-images`, `delete-image`), `admin.categories.*`, `admin.reviews.*`; partner: `partner.inventory.*` (incl. `bulk-action`, `reorder-images`, `delete-image`); api: `/api/catalog` closure (updated to `Modules\CatalogDelivery\Models\Product`).

- [ ] **Step 1: Create the RouteServiceProvider** (Task 2.3 Step 1 pattern, name `CatalogDelivery`), register it in `CatalogDeliveryServiceProvider::$providers`, keep `module.json` providers intact.

- [ ] **Step 2: Create the module web routes**

`Modules/CatalogDelivery/routes/web.php` — move from `routes/web.php` verbatim: public routes (`home`, `shop`, `product.show`, `about`, `contact` — controllers imported as `Modules\CatalogDelivery\Http\Controllers\ViewController`), the admin `products` resource (WITHOUT a `show` name entry — Task 0.6) + `reorder-images` + `delete-image` (`ProductController`), the admin `categories` resource (`CategoryController`), the admin `reviews` group (`ReviewController`), and the partner `inventory` group (`PartnerInventoryController`, prefix `partner`, name `partner.`). Names stay byte-identical.

- [ ] **Step 3: Create the module api routes**

`Modules/CatalogDelivery/routes/api.php`:
```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\CatalogDelivery\Models\Product;

Route::get('/catalog', function () {
    return Product::with(['category', 'images'])->paginate(15);
});
```

- [ ] **Step 4: Prune root routes**

Remove from `routes/web.php` all blocks moved above. Remove the catalog closure from `routes/api.php` (leave the file with a comment — `bootstrap/app.php` requires it).

- [ ] **Step 5: Verify**

```bash
composer dump-autoload
php artisan route:list | grep -E "admin.products|admin.categories|admin.reviews|partner.inventory|home|shop|product.show"
php artisan test
```
Expected: names unchanged; full suite green.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "refactor(catalogdelivery): module routes for storefront, admin catalog, partner inventory"
```

### Task 3.6: CatalogDelivery phase verification

- [ ] **Step 1: Verify + smoke test**

```bash
php artisan test
php artisan route:list | wc -l
php artisan serve --port=8000 &
curl -s http://127.0.0.1:8000/shop | grep -c "product"
curl -s http://127.0.0.1:8000/product/1 | grep -c "review"
kill %1
```
Expected: shop + product pages render (200), reviews section present.

- [ ] **Step 2: Commit**

```bash
git add -A && git commit -m "chore: phase 3 catalogdelivery complete"
```## Phase 4 — PartnerHub module

### Task 4.1: Move Partner + PartnerProduct models

**Files:**
- Move: `app/Models/{Partner,PartnerProduct}.php` → `Modules/PartnerHub/app/Models/`

**Interfaces:**
- Produces: `Modules\PartnerHub\Models\{Partner,PartnerProduct}`. `Partner::orders()` (custom `belongsToMany` through `order_items` + `partner_products`), `payouts()`, `products()` relations unchanged. Consumed by: `PartnerInventoryController::getPartner()` in CatalogDelivery (Task 3.2 Step 2 left it on `App\Models\Partner`), `PartnerDashboardController` (Task 4.2), MarketplacePipeline controllers (Task 5.2).

- [ ] **Step 1: Move the models**

```bash
mkdir -p Modules/PartnerHub/app/Models
git mv app/Models/Partner.php Modules/PartnerHub/app/Models/Partner.php
git mv app/Models/PartnerProduct.php Modules/PartnerHub/app/Models/PartnerProduct.php
```

- [ ] **Step 2: Fix namespaces + imports**

Both models → `namespace Modules\PartnerHub\Models;`. Internal imports: `use App\Models\User;` → `Modules\IdentityAccess\Models\User;`; `use App\Models\Product;` → `Modules\CatalogDelivery\Models\Product;`. References to `App\Models\{Order,OrderItem,Payout}` inside `Partner` (the `orders()` custom relation) stay as-is until Task 5.1 Step 3 fixes them.

- [ ] **Step 3: Update all references**

```bash
rg -l "App\\\\Models\\\\Partner|App\\\\Models\\\\PartnerProduct" app routes resources tests database Modules | xargs -r sed -i -E 's|App\\Models\\(Partner\|PartnerProduct)|Modules\\PartnerHub\\Models\\\1|g'
```

- [ ] **Step 4: Verify**

```bash
composer dump-autoload
php artisan test
```
Expected: PASS (includes `PayoutSplitTest` from Task 0.5 — it imports `App\Models\Partner`; the sed above rewrites it).

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "refactor(partnerhub): move partner models"
```

### Task 4.2: Move partner controllers + requests; add PartnerProfileController (self-service)

**Files:**
- Move: `app/Http/Controllers/{PartnerController,PartnerDashboardController}.php` → `Modules/PartnerHub/app/Http/Controllers/`
- Move: `app/Http/Requests/{StorePartnerRequest,UpdatePartnerRequest}.php` → `Modules/PartnerHub/app/Http/Requests/`
- Create: `Modules/PartnerHub/app/Http/Controllers/PartnerProfileController.php`
- Modify: `Modules/CatalogDelivery/app/Http/Controllers/ViewController.php` (remove `partnerProfile()`)

**Interfaces:**
- Produces: `Modules\PartnerHub\Http\Controllers\{PartnerController,PartnerDashboardController,PartnerProfileController}`; requests `Modules\PartnerHub\Http\Requests\{StorePartnerRequest,UpdatePartnerRequest}`. `PartnerProfileController::show($id)` — public artisan page (body verbatim from `ViewController@partnerProfile`, view `partnerhub::partner_profile`); `edit()` — auth+partner, view `partnerhub::partner.profile.edit`; `update(Request)` — validates + updates the partner, redirects to `partner.profile.edit` with status. Consumed by Task 4.3 routes. Note: `PartnerDashboardController`'s query logic moves to `AnalyticsService` in Task 6.2 — leave it in the controller until then.

- [ ] **Step 1: Move the files**

```bash
mkdir -p Modules/PartnerHub/app/Http/Controllers
mkdir -p Modules/PartnerHub/app/Http/Requests
git mv app/Http/Controllers/PartnerController.php Modules/PartnerHub/app/Http/Controllers/PartnerController.php
git mv app/Http/Controllers/PartnerDashboardController.php Modules/PartnerHub/app/Http/Controllers/PartnerDashboardController.php
git mv app/Http/Requests/StorePartnerRequest.php Modules/PartnerHub/app/Http/Requests/StorePartnerRequest.php
git mv app/Http/Requests/UpdatePartnerRequest.php Modules/PartnerHub/app/Http/Requests/UpdatePartnerRequest.php
```

- [ ] **Step 2: Fix namespaces + imports + view strings**

- `PartnerController` → `namespace Modules\PartnerHub\Http\Controllers;`; `use App\Models\Partner;` → `Modules\PartnerHub\Models\Partner;`; `use App\Models\Product;` → `Modules\CatalogDelivery\Models\Product;`; `use App\Models\User;` → `Modules\IdentityAccess\Models\User;`; requests → `Modules\PartnerHub\Http\Requests\{StorePartnerRequest,UpdatePartnerRequest}`; views → `partnerhub::admin.partners.*` (match each `view('admin.partners.…')` → `view('partnerhub::admin.partners.…')`).
- `PartnerDashboardController` → `namespace Modules\PartnerHub\Http\Controllers;`; `use App\Models\Partner;` → `Modules\PartnerHub\Models\Partner;`; view → `partnerhub::partner.dashboard`. Keep `use App\Models\Order;`/`\App\Models\OrderItem` until Task 5.1 Step 3.
- Requests → `namespace Modules\PartnerHub\Http\Requests;` (imports of `App\Models\Partner` → `Modules\PartnerHub\Models\Partner`).

- [ ] **Step 3: Create PartnerProfileController**

Read `ViewController.php` first and copy the body of `partnerProfile()` verbatim into `show()` (it loads the partner with its products):

```php
<?php

namespace Modules\PartnerHub\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\PartnerHub\Models\Partner;
use Illuminate\Http\Request;

class PartnerProfileController extends Controller
{
    public function show($id)
    {
        $partner = Partner::with('products')->findOrFail($id);

        return view('partnerhub::partner_profile', compact('partner'));
    }

    public function edit()
    {
        $partner = Partner::where('user_id', auth()->id())->firstOrFail();

        return view('partnerhub::partner.profile.edit', compact('partner'));
    }

    public function update(Request $request)
    {
        $partner = Partner::where('user_id', auth()->id())->firstOrFail();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'website' => ['nullable', 'url', 'max:255'],
            'contact_info' => ['nullable', 'string', 'max:255'],
        ]);

        $partner->update($validated);

        return redirect()->route('partner.profile.edit')->with('status', 'Profile updated successfully');
    }
}
```
Match the exact eager loads/variables of the original `partnerProfile()` (read it first).

- [ ] **Step 4: Remove `partnerProfile()` from ViewController**

Delete the `partnerProfile()` method and its now-unused `Partner` import from `Modules/CatalogDelivery/app/Http/Controllers/ViewController.php`.

- [ ] **Step 5: Update remaining references**

```bash
rg -l "App\\\\Http\\\\Controllers\\\\PartnerController|App\\\\Http\\\\Controllers\\\\PartnerDashboardController|App\\\\Http\\\\Requests\\\\StorePartnerRequest|App\\\\Http\\\\Requests\\\\UpdatePartnerRequest" app routes resources tests database Modules | xargs -r sed -i -E 's|App\\(Http\\(Controllers\|Http\\(Requests)\\(PartnerController\|PartnerDashboardController\|StorePartnerRequest\|UpdatePartnerRequest)|Modules\\PartnerHub\\\1\\\2|g'
```

- [ ] **Step 6: Verify**

```bash
composer dump-autoload
php artisan test
```
Note: routes still target old class paths until Task 4.3 — run the full suite AFTER Task 4.3 in the same session.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "refactor(partnerhub): move partner controllers, requests; add self-service profile"
```

### Task 4.3: Move partner routes into the module

**Files:**
- Create: `Modules/PartnerHub/routes/web.php`, `Modules/PartnerHub/app/Providers/RouteServiceProvider.php`
- Modify: `routes/web.php` (prune), `Modules/PartnerHub/app/Providers/PartnerHubServiceProvider.php` (`$providers`), `Modules/PartnerHub/module.json`

**Interfaces:**
- Produces: module routes — admin: `admin.partners.*` (`index/create/store/show/edit/update/destroy/add_product/remove_product`); public: `partner.profile` (GET `/artisan-profile/{id}`); partner: `partner.dashboard`, plus NEW `partner.profile.edit` + `partner.profile.update` (self-service). All existing names stay byte-identical.

- [ ] **Step 1: Create the RouteServiceProvider** (Task 2.3 Step 1 pattern, name `PartnerHub`), register it in `PartnerHubServiceProvider::$providers`, keep `module.json` providers intact.

- [ ] **Step 2: Create the module web routes**

`Modules/PartnerHub/routes/web.php`:
```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\PartnerHub\Http\Controllers\PartnerController;
use Modules\PartnerHub\Http\Controllers\PartnerDashboardController;
use Modules\PartnerHub\Http\Controllers\PartnerProfileController;

Route::get('/artisan-profile/{id}', [PartnerProfileController::class, 'show'])->name('partner.profile');

Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function () {
    Route::resource('partners', PartnerController::class);
    Route::post('partners/{id}/add-product', [PartnerController::class, 'addProduct'])->name('partners.add_product');
    Route::delete('partners/{id}/remove-product/{productId}', [PartnerController::class, 'removeProduct'])->name('partners.remove_product');
});

Route::prefix('partner')->middleware(['auth', 'partner'])->name('partner.')->group(function () {
    Route::get('/dashboard', [PartnerDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile/edit', [PartnerProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/update', [PartnerProfileController::class, 'update'])->name('profile.update');
});
```
Verify the original `partners` resource in `routes/web.php` matches `Route::resource('partners', PartnerController::class)` with no `except` — copy the original verbatim if it differs.

- [ ] **Step 3: Prune root routes**

Remove from `routes/web.php`: the `artisan-profile` route, the admin `partners` resource + `add_product`/`remove_product` routes, and the `partner.dashboard` route (leave the partner `orders`/`payouts` groups in place — they move in Task 5.6).

- [ ] **Step 4: Verify**

```bash
composer dump-autoload
php artisan route:list | grep -E "admin.partners|partner.profile|partner.dashboard"
php artisan test
```
Expected: names unchanged; full suite green.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "refactor(partnerhub): module routes for partner registry, artisan profile, dashboard"
```

### Task 4.4: Move partner views into the module

**Files:**
- Move (into `Modules/PartnerHub/resources/views/`): `admin/partners/{index,create,edit,show}.blade.php`, `partner/dashboard.blade.php`, `partner_profile.blade.php`
- Create: `Modules/PartnerHub/resources/views/partner/profile/edit.blade.php`
- Modify: `resources/views/partials/partner-nav.blade.php` (add Profile link)

**Interfaces:**
- Produces: module views `partnerhub::admin.partners.*`, `partnerhub::partner.dashboard`, `partnerhub::partner_profile`, `partnerhub::partner.profile.edit` (all referenced by controllers from Task 4.2).

- [ ] **Step 1: Move the views**

```bash
mkdir -p Modules/PartnerHub/resources/views/admin/partners
mkdir -p Modules/PartnerHub/resources/views/partner/profile
git mv resources/views/admin/partners/index.blade.php Modules/PartnerHub/resources/views/admin/partners/index.blade.php
git mv resources/views/admin/partners/create.blade.php Modules/PartnerHub/resources/views/admin/partners/create.blade.php
git mv resources/views/admin/partners/edit.blade.php Modules/PartnerHub/resources/views/admin/partners/edit.blade.php
git mv resources/views/admin/partners/show.blade.php Modules/PartnerHub/resources/views/admin/partners/show.blade.php
git mv resources/views/partner/dashboard.blade.php Modules/PartnerHub/resources/views/partner/dashboard.blade.php
git mv resources/views/partner_profile.blade.php Modules/PartnerHub/resources/views/partner_profile.blade.php
```

- [ ] **Step 2: Move inline CSS/JS to module assets**

For each moved view containing a `<style>` block, extract it into `Modules/PartnerHub/resources/assets/scss/app.scss` (create it; scope sections with comments) and remove it from the blade. Move the Chart.js init `<script>` in `partner/dashboard.blade.php` into `Modules/PartnerHub/resources/assets/js/dashboard.js` (the Task 1.4 Vite pipeline compiles both). Keep only dynamic `style="..."` attributes.

- [ ] **Step 3: Create the self-service profile edit view**

Create `Modules/PartnerHub/resources/views/partner/profile/edit.blade.php`. Mirror the form markup conventions (input/button classes) of `partner/inventory/create.blade.php` — do not introduce new inline styles:
```blade
<x-app-layout>
    <div class="max-w-3xl mx-auto py-10 px-4">
        <h1 class="text-2xl mb-6">Edit Public Profile</h1>

        @if (session('status'))
            <div class="mb-4 text-green-600">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="mb-4 text-red-600">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('partner.profile.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label for="name">Business name</label>
                <input id="name" name="name" value="{{ old('name', $partner->name) }}" required>
            </div>
            <div>
                <label for="description">Bio</label>
                <textarea id="description" name="description" rows="4">{{ old('description', $partner->description) }}</textarea>
            </div>
            <div>
                <label for="website">Website</label>
                <input id="website" name="website" value="{{ old('website', $partner->website) }}">
            </div>
            <div>
                <label for="contact_info">Contact info</label>
                <input id="contact_info" name="contact_info" value="{{ old('contact_info', $partner->contact_info) }}">
            </div>

            <button type="submit">Save changes</button>
        </form>
    </div>
</x-app-layout>
```
Apply the actual input/button classes from the existing partner forms when copying.

- [ ] **Step 4: Add Profile link to partner nav**

In `resources/views/partials/partner-nav.blade.php`, add a nav item linking to `route('partner.profile.edit')` (mirror the existing nav item markup, e.g. the "Earnings" item).

- [ ] **Step 5: Verify**

```bash
php artisan test
```
Expected: green (wiring completed in Task 4.3).

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "refactor(partnerhub): move partner/admin/artisan views; self-service profile view"
```

### Task 4.5: PartnerHub phase verification

- [ ] **Step 1: Verify + smoke test**

```bash
php artisan test
php artisan route:list | grep -cE "admin.partners|partner.profile|partner.dashboard"
php artisan serve --port=8000 &
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/artisan-profile/1
kill %1
```
Expected: route count 18 (14 admin partners + 2 artisan profile + dashboard + profile.edit); artisan profile renders 200 (or 404 if no partner id 1 exists — then hit an existing id).

- [ ] **Step 2: Commit**

```bash
git add -A && git commit -m "chore: phase 4 partnerhub complete"
```

## Phase 5 — MarketplacePipeline module

### Task 5.1: Move commerce models

**Files:**
- Move: `app/Models/{Cart,CartItem,Order,OrderItem,Payment,Payout}.php` → `Modules/MarketplacePipeline/app/Models/`

**Interfaces:**
- Produces: `Modules\MarketplacePipeline\Models\{Cart,CartItem,Order,OrderItem,Payment,Payout}`. Cross-module imports that must resolve: `Order.user` → `Modules\IdentityAccess\Models\User`; `Order/Payout/OrderItem` relations to products → `Modules\CatalogDelivery\Models\Product`; `Payout.partner` → `Modules\PartnerHub\Models\Partner`. Consumed by: IdentityAccess `GovernanceService` (Task 2.5), PartnerHub `PartnerDashboardController` (Task 4.2), all commerce controllers (Task 5.2).

- [ ] **Step 1: Move the models**

```bash
mkdir -p Modules/MarketplacePipeline/app/Models
git mv app/Models/Cart.php Modules/MarketplacePipeline/app/Models/Cart.php
git mv app/Models/CartItem.php Modules/MarketplacePipeline/app/Models/CartItem.php
git mv app/Models/Order.php Modules/MarketplacePipeline/app/Models/Order.php
git mv app/Models/OrderItem.php Modules/MarketplacePipeline/app/Models/OrderItem.php
git mv app/Models/Payment.php Modules/MarketplacePipeline/app/Models/Payment.php
git mv app/Models/Payout.php Modules/MarketplacePipeline/app/Models/Payout.php
```

- [ ] **Step 2: Fix namespaces + imports**

Each moved model → `namespace Modules\MarketplacePipeline\Models;`. Internal imports: `use App\Models\User;` → `Modules\IdentityAccess\Models\User;`; `use App\Models\Product;` → `Modules\CatalogDelivery\Models\Product;`; `use App\Models\Partner;` → `Modules\PartnerHub\Models\Partner;` (in `Payout`).

- [ ] **Step 3: Update all references** (include `Modules/` — GovernanceService, Partner models/controllers reference these)

```bash
rg -l "App\\\\Models\\\\CartItem|App\\\\Models\\\\Cart|App\\\\Models\\\\OrderItem|App\\\\Models\\\\Order|App\\\\Models\\\\Payment|App\\\\Models\\\\Payout" app routes resources tests database Modules | xargs -r sed -i -E 's|App\\Models\\(CartItem\|Cart\|OrderItem\|Order\|Payment\|Payout)|Modules\\MarketplacePipeline\\Models\\\1|g'
```
Note: `CartItem` and `OrderItem` MUST precede `Cart`/`Order` in the alternation (longest first) so `App\Models\OrderItem` is not half-rewritten.

- [ ] **Step 4: Verify**

```bash
composer dump-autoload
php artisan test --filter="PayoutSplitTest|WishlistTest|ReviewSubmissionTest"
```
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "refactor(marketplacepipeline): move cart/order/payment/payout models"
```

### Task 5.2: Move commerce controllers

**Files:**
- Move: `app/Http/Controllers/{CartController,OrderController,PaymentController,AdminOrderController,AdminPayoutController,PartnerOrderController,PartnerPayoutController}.php` → `Modules/MarketplacePipeline/app/Http/Controllers/`

**Interfaces:**
- Produces: `Modules\MarketplacePipeline\Http\Controllers\{CartController,OrderController,PaymentController,AdminOrderController,AdminPayoutController,PartnerOrderController,PartnerPayoutController}`. `AdminOrderController@complete` still contains the Task 0.5 split logic — extracted into `PayoutService` in Task 5.3. `OrderController@store/cancel` transaction bodies — extracted into `CheckoutService` in Task 5.4.

- [ ] **Step 1: Move the files**

```bash
mkdir -p Modules/MarketplacePipeline/app/Http/Controllers
git mv app/Http/Controllers/CartController.php Modules/MarketplacePipeline/app/Http/Controllers/CartController.php
git mv app/Http/Controllers/OrderController.php Modules/MarketplacePipeline/app/Http/Controllers/OrderController.php
git mv app/Http/Controllers/PaymentController.php Modules/MarketplacePipeline/app/Http/Controllers/PaymentController.php
git mv app/Http/Controllers/AdminOrderController.php Modules/MarketplacePipeline/app/Http/Controllers/AdminOrderController.php
git mv app/Http/Controllers/AdminPayoutController.php Modules/MarketplacePipeline/app/Http/Controllers/AdminPayoutController.php
git mv app/Http/Controllers/PartnerOrderController.php Modules/MarketplacePipeline/app/Http/Controllers/PartnerOrderController.php
git mv app/Http/Controllers/PartnerPayoutController.php Modules/MarketplacePipeline/app/Http/Controllers/PartnerPayoutController.php
```

- [ ] **Step 2: Fix namespaces + imports + view strings**

- Controllers → `namespace Modules\MarketplacePipeline\Http\Controllers;`; `use App\Http\Controllers\Controller;` stays; model imports → `Modules\MarketplacePipeline\Models\*`, `Modules\IdentityAccess\Models\User` where used, `Modules\CatalogDelivery\Models\Product` where used; `use App\Mail\OrderConfirmed;` etc. stay until Task 5.5 (mailables move there).
- View strings → `marketplacepipeline::` prefix: `cart.index`, `orders.index`, `admin.orders.{index,show}`, `admin.payouts.index`, `partner.orders.{index,show}`, `partner.payouts.index`.
- `PaymentController` keeps its `srmklive` PayPal imports unchanged.

- [ ] **Step 3: Update remaining references**

```bash
rg -l "App\\\\Http\\\\Controllers\\\\(CartController|OrderController|PaymentController|AdminOrderController|AdminPayoutController|PartnerOrderController|PartnerPayoutController)" app routes resources tests database Modules | xargs -r sed -i -E 's|App\\Http\\Controllers\\(CartController\|OrderController\|PaymentController\|AdminOrderController\|AdminPayoutController\|PartnerOrderController\|PartnerPayoutController)|Modules\\MarketplacePipeline\\Http\\Controllers\\\1|g'
```

- [ ] **Step 4: Verify**

```bash
composer dump-autoload
```
Note: routes still target old class paths until Task 5.6 — run the full suite AFTER Task 5.6 in the same session.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "refactor(marketplacepipeline): move cart/order/payment/payout controllers"
```

### Task 5.3: Extract PayoutService

**Files:**
- Create: `Modules/MarketplacePipeline/app/Services/PayoutService.php`
- Modify: `Modules/MarketplacePipeline/app/Http/Controllers/AdminOrderController.php`
- Move: `tests/Feature/PayoutSplitTest.php` → `Modules/MarketplacePipeline/tests/Feature/`

**Interfaces:**
- Produces: `PayoutService::settle(Order $order): void` — per-item line value split equally among the product's partners, net of `config('shop.commission_rate')`, via `Payout::updateOrCreate(['order_id','partner_id'], [...])`. Exact split logic moved verbatim from Task 0.5 Step 3. Test namespace becomes `Modules\MarketplacePipeline\Tests\Feature`.

- [ ] **Step 1: Create the service** (split logic verbatim from Task 0.5 Step 3)

```php
<?php

namespace Modules\MarketplacePipeline\Services;

use Modules\MarketplacePipeline\Models\Order;
use Modules\MarketplacePipeline\Models\Payout;

class PayoutService
{
    public function settle(Order $order): void
    {
        $order->load('items.product.partners');

        $partnerItems = [];
        foreach ($order->items as $item) {
            $partners = $item->product->partners;
            if ($partners->isEmpty()) {
                continue;
            }
            $lineValue = $item->price * $item->quantity;
            $share = $lineValue / $partners->count();
            foreach ($partners as $partner) {
                $partnerItems[$partner->id] = ($partnerItems[$partner->id] ?? 0) + $share;
            }
        }

        foreach ($partnerItems as $partnerId => $grossAmount) {
            $netAmount = $grossAmount * (1 - config('shop.commission_rate'));

            Payout::updateOrCreate(
                ['order_id' => $order->id, 'partner_id' => $partnerId],
                ['amount' => $netAmount, 'status' => 'pending']
            );
        }
    }
}
```

- [ ] **Step 2: Thin AdminOrderController@complete**

```php
    public function complete($id, PayoutService $payouts)
    {
        $order = Order::with('items.product.partners')->findOrFail($id);

        if ($order->status === 'paid') {
            \DB::transaction(function () use ($order, $payouts) {
                $order->update(['status' => 'completed']);
                $payouts->settle($order);
            });

            return back()->with('status', 'Order marked as completed and payouts generated.');
        }

        return back()->withErrors('Only paid orders can be marked as completed.');
    }
```
Remove the inline split block; import `Modules\MarketplacePipeline\Services\PayoutService`.

- [ ] **Step 3: Move the payout test into the module suite**

```bash
mkdir -p Modules/MarketplacePipeline/tests/Feature
git mv tests/Feature/PayoutSplitTest.php Modules/MarketplacePipeline/tests/Feature/PayoutSplitTest.php
```
Rewrite header: `namespace Modules\MarketplacePipeline\Tests\Feature;` and `use Modules\MarketplacePipeline\Tests\TestCase;` (base class generated by Task 1.2 skeleton); model imports → `Modules\IdentityAccess\Models\User`, `Modules\CatalogDelivery\Models\Product`, `Modules\MarketplacePipeline\Models\{Order,OrderItem,Payout}`, `Modules\PartnerHub\Models\Partner`.

- [ ] **Step 4: Verify**

```bash
composer dump-autoload
php artisan test --filter=PayoutSplitTest
```
Expected: PASS (both payouts 45.0 for a 100-line two-partner product).

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "refactor(marketplacepipeline): extract PayoutService; move payout tests to module"
```

### Task 5.4: Extract CheckoutService

**Files:**
- Create: `Modules/MarketplacePipeline/app/Services/CheckoutService.php`
- Modify: `Modules/MarketplacePipeline/app/Http/Controllers/OrderController.php`

**Interfaces:**
- Produces: `CheckoutService::checkout(User $user): Order` (throws `\RuntimeException('Cart is empty')` for empty carts; runs the store transaction — `lockForUpdate` product rows, stock validation, order + order_items creation, stock decrement, cart clear — and returns the order; the mail is NOT sent by the service); `CheckoutService::cancel(User $user, int $orderId): void` (throws `\RuntimeException` on 'Order already cancelled' / 'Only pending orders can be cancelled'; restores stock, marks cancelled, voids pending payments). Controllers keep the try/catch + redirect/back behavior.

- [ ] **Step 1: Create the service** — transaction bodies copied verbatim from `OrderController@store` (lines 30-163) and `@cancel` (lines 171-259), with `auth()->id()` → `$user->id`:

```php
<?php

namespace Modules\MarketplacePipeline\Services;

use Modules\IdentityAccess\Models\User;
use Modules\MarketplacePipeline\Models\Order;
use Modules\MarketplacePipeline\Models\OrderItem;
use Modules\MarketplacePipeline\Models\Cart;
use Modules\MarketplacePipeline\Models\Payment;
use Modules\CatalogDelivery\Models\Product;
use Illuminate\Support\Facades\DB;

class CheckoutService
{
    public function checkout(User $user): Order
    {
        $cart = Cart::with('items')->where('user_id', $user->id)->first();

        if (!$cart || $cart->items->isEmpty()) {
            throw new \RuntimeException('Cart is empty');
        }

        return DB::transaction(function () use ($cart, $user) {
            $total = 0;
            $products = [];

            foreach ($cart->items as $item) {
                $product = Product::where('id', $item->product_id)->lockForUpdate()->first();

                if (!$product) {
                    throw new \Exception('Product not found');
                }

                $products[$item->product_id] = $product;

                if ($product->stock < $item->quantity) {
                    throw new \Exception('Insufficient stock for ' . $product->name);
                }

                $total += $product->price * $item->quantity;
            }

            $order = Order::create([
                'user_id' => $user->id,
                'total_price' => $total,
                'status' => 'pending',
            ]);

            foreach ($cart->items as $item) {
                $product = $products[$item->product_id];

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $product->price,
                ]);

                $product->decrement('stock', $item->quantity);
            }

            $cart->items()->delete();

            return $order;
        });
    }

    public function cancel(User $user, int $orderId): void
    {
        DB::transaction(function () use ($user, $orderId) {
            $order = Order::where('user_id', $user->id)
                ->lockForUpdate()
                ->with('items.product')
                ->findOrFail($orderId);

            if ($order->status === 'cancelled') {
                throw new \RuntimeException('Order already cancelled');
            }

            if ($order->status !== 'pending') {
                throw new \RuntimeException('Only pending orders can be cancelled');
            }

            foreach ($order->items as $item) {
                $product = Product::where('id', $item->product_id)->lockForUpdate()->first();

                if (!$product) {
                    continue;
                }

                $product->increment('stock', $item->quantity);
            }

            $order->update(['status' => 'cancelled']);

            Payment::where('order_id', $order->id)
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);
        });
    }
}
```
Match the original controller's exact behavior (comments/order of operations) — diff against `git show` of the moved file if in doubt.

- [ ] **Step 2: Thin OrderController**

```php
    public function store(Request $request, CheckoutService $checkout)
    {
        try {
            $order = $checkout->checkout(auth()->user());

            $order->load('items.product');
            Mail::to(auth()->user())->send(new OrderConfirmed($order));

            return redirect()->route('orders.index')->with('status', 'Order placed successfully');
        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    public function cancel($id, CheckoutService $checkout)
    {
        try {
            $checkout->cancel(auth()->user(), (int) $id);

            $order = Order::with('items.product')->findOrFail($id);
            Mail::to(auth()->user())->send(new OrderCancelled($order));

            return redirect()->route('orders.index')->with('status', 'Order cancelled successfully');
        } catch (\Exception $e) {
            return back()->withErrors($e->getMessage());
        }
    }
```
The original sent `OrderCancelled` with the order loaded — since the service methods now return `void`, `cancel` reloads the order after the service call before mailing.

- [ ] **Step 3: Verify**

```bash
composer dump-autoload
php -l Modules/MarketplacePipeline/app/Services/CheckoutService.php
php artisan test
```
Full suite green after Task 5.6 wiring.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "refactor(marketplacepipeline): extract CheckoutService; thin OrderController"
```

### Task 5.5: Move commerce views + mailables

**Files:**
- Move (into `Modules/MarketplacePipeline/resources/views/`): `cart/index.blade.php`, `orders/index.blade.php`, `admin/orders/{index,show}.blade.php`, `admin/payouts/index.blade.php`, `partner/orders/{index,show}.blade.php`, `partner/payouts/index.blade.php`, `emails/orders/{confirmed,cancelled}.blade.php`, `emails/payments/success.blade.php`
- Move: `app/Mail/{OrderConfirmed,OrderCancelled,PaymentSuccess}.php` → `Modules/MarketplacePipeline/app/Mail/`

**Interfaces:**
- Produces: views `marketplacepipeline::cart.index`, `marketplacepipeline::orders.index`, `marketplacepipeline::admin.orders.{index,show}`, `marketplacepipeline::admin.payouts.index`, `marketplacepipeline::partner.orders.{index,show}`, `marketplacepipeline::partner.payouts.index`, `marketplacepipeline::emails.orders.{confirmed,cancelled}`, `marketplacepipeline::emails.payments.success`; mailables `Modules\MarketplacePipeline\Mail\{OrderConfirmed,OrderCancelled,PaymentSuccess}` (all `ShouldQueue`).

- [ ] **Step 1: Move the files**

```bash
mkdir -p Modules/MarketplacePipeline/resources/views/admin/orders
mkdir -p Modules/MarketplacePipeline/resources/views/admin/payouts
mkdir -p Modules/MarketplacePipeline/resources/views/partner/orders
mkdir -p Modules/MarketplacePipeline/resources/views/partner/payouts
mkdir -p Modules/MarketplacePipeline/resources/views/emails/orders
mkdir -p Modules/MarketplacePipeline/resources/views/emails/payments
mkdir -p Modules/MarketplacePipeline/app/Mail
git mv resources/views/cart/index.blade.php Modules/MarketplacePipeline/resources/views/cart/index.blade.php
git mv resources/views/orders/index.blade.php Modules/MarketplacePipeline/resources/views/orders/index.blade.php
git mv resources/views/admin/orders/index.blade.php Modules/MarketplacePipeline/resources/views/admin/orders/index.blade.php
git mv resources/views/admin/orders/show.blade.php Modules/MarketplacePipeline/resources/views/admin/orders/show.blade.php
git mv resources/views/admin/payouts/index.blade.php Modules/MarketplacePipeline/resources/views/admin/payouts/index.blade.php
git mv resources/views/partner/orders/index.blade.php Modules/MarketplacePipeline/resources/views/partner/orders/index.blade.php
git mv resources/views/partner/orders/show.blade.php Modules/MarketplacePipeline/resources/views/partner/orders/show.blade.php
git mv resources/views/partner/payouts/index.blade.php Modules/MarketplacePipeline/resources/views/partner/payouts/index.blade.php
git mv resources/views/emails/orders/confirmed.blade.php Modules/MarketplacePipeline/resources/views/emails/orders/confirmed.blade.php
git mv resources/views/emails/orders/cancelled.blade.php Modules/MarketplacePipeline/resources/views/emails/orders/cancelled.blade.php
git mv resources/views/emails/payments/success.blade.php Modules/MarketplacePipeline/resources/views/emails/payments/success.blade.php
git mv app/Mail/OrderConfirmed.php Modules/MarketplacePipeline/app/Mail/OrderConfirmed.php
git mv app/Mail/OrderCancelled.php Modules/MarketplacePipeline/app/Mail/OrderCancelled.php
git mv app/Mail/PaymentSuccess.php Modules/MarketplacePipeline/app/Mail/PaymentSuccess.php
```
(If `Modules/MarketplacePipeline/resources/views/cart` does not exist, `mkdir -p` it first.)

- [ ] **Step 2: Fix namespaces + imports + view strings**

- Mailables → `namespace Modules\MarketplacePipeline\Mail;`; their view strings → `marketplacepipeline::emails.orders.confirmed` / `marketplacepipeline::emails.orders.cancelled` / `marketplacepipeline::emails.payments.success`.
- Update mailable imports across modules: `rg -l "App\\\\Mail\\\\(OrderConfirmed|OrderCancelled|PaymentSuccess)" app routes resources tests database Modules | xargs -r sed -i -E 's|App\\Mail\\(OrderConfirmed\|OrderCancelled\|PaymentSuccess)|Modules\\MarketplacePipeline\\Mail\\\1|g'`
- Fix the stray trailing `tml>` typo in `Modules/MarketplacePipeline/resources/views/emails/orders/confirmed.blade.php` (§11).

- [ ] **Step 3: Move inline CSS to module assets**

For each moved view containing a `<style>` block, extract it into `Modules/MarketplacePipeline/resources/assets/scss/app.scss` (create it; scope sections with comments). Keep only dynamic `style="..."` attributes.

- [ ] **Step 4: Verify**

```bash
composer dump-autoload
php artisan test
```
(Routes wired in Task 5.6 — run checks after it.)

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "refactor(marketplacepipeline): move commerce/mail views and mailables"
```

### Task 5.6: Move commerce routes into the module

**Files:**
- Create: `Modules/MarketplacePipeline/routes/web.php`, `Modules/MarketplacePipeline/app/Providers/RouteServiceProvider.php`
- Modify: `routes/web.php` (prune to comment-only), `Modules/MarketplacePipeline/module.json` + `MarketplacePipelineServiceProvider::$providers`

**Interfaces:**
- Produces: module routes — member: `cart.index/add/remove`, `orders.index/store/cancel`, `paypal.store/capture/cancel`; admin: `admin.orders.index/show/complete`, `admin.payouts.index/process`; partner: `partner.orders.index/show`, `partner.payouts.index`. Names byte-identical.

- [ ] **Step 1: Create the RouteServiceProvider** (Task 2.3 Step 1 pattern, name `MarketplacePipeline`), register it in `MarketplacePipelineServiceProvider::$providers`, keep `module.json` providers intact.

- [ ] **Step 2: Create the module web routes**

`Modules/MarketplacePipeline/routes/web.php`:
```php
<?php

use Illuminate\Support\Facades\Route;
use Modules\MarketplacePipeline\Http\Controllers\CartController;
use Modules\MarketplacePipeline\Http\Controllers\OrderController;
use Modules\MarketplacePipeline\Http\Controllers\PaymentController;
use Modules\MarketplacePipeline\Http\Controllers\AdminOrderController;
use Modules\MarketplacePipeline\Http\Controllers\AdminPayoutController;
use Modules\MarketplacePipeline\Http\Controllers\PartnerOrderController;
use Modules\MarketplacePipeline\Http\Controllers\PartnerPayoutController;

Route::middleware('auth')->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::delete('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/store', [OrderController::class, 'store'])->name('orders.store');
    Route::patch('/orders/{id}/cancel', [OrderController::class, 'cancel'])->name('orders.cancel');

    Route::post('/paypal/store', [PaymentController::class, 'store'])->name('paypal.store');
    Route::get('/paypal/capture', [PaymentController::class, 'capture'])->name('paypal.capture');
    Route::get('/paypal/cancel', function () {
        return redirect()->route('cart.index')->withErrors('Payment cancelled');
    })->name('paypal.cancel');
});

Route::prefix('admin')->middleware(['auth', 'admin'])->name('admin.')->group(function () {
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{id}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{id}/complete', [AdminOrderController::class, 'complete'])->name('orders.complete');

    Route::get('/payouts', [AdminPayoutController::class, 'index'])->name('payouts.index');
    Route::post('/payouts/{id}/process', [AdminPayoutController::class, 'process'])->name('payouts.process');
});

Route::prefix('partner')->middleware(['auth', 'partner'])->name('partner.')->group(function () {
    Route::get('/orders', [PartnerOrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order}', [PartnerOrderController::class, 'show'])->name('orders.show');

    Route::get('/payouts', [PartnerPayoutController::class, 'index'])->name('payouts.index');
});
```
Copy the original `paypal.cancel` closure body verbatim from `routes/web.php` if it differs.

- [ ] **Step 3: Prune root routes**

`routes/web.php` becomes comment-only (it was emptied across Tasks 2.3, 3.5, 4.3, 5.6; `bootstrap/app.php` still requires the file):
```php
<?php

// All web routes now live in module route files:
//   Modules/IdentityAccess/routes/web.php
//   Modules/CatalogDelivery/routes/web.php
//   Modules/PartnerHub/routes/web.php
//   Modules/MarketplacePipeline/routes/web.php
//   Modules/TelemetryPipeline/routes/web.php
```

- [ ] **Step 4: Verify**

```bash
composer dump-autoload
php artisan route:list | grep -cE "cart\.|orders\.|paypal\.|admin.orders|admin.payouts|partner.orders|partner.payouts"
php artisan test
```
Expected: 20 route names; full suite green (this is the first full-suite run since Task 5.2 — commerce routes now resolve to module controllers).

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "refactor(marketplacepipeline): module routes for cart, orders, paypal, admin/partner orders+payouts"
```

### Task 5.7: Checkout tests + phase verification

**Files:**
- Create: `Modules/MarketplacePipeline/tests/Feature/CheckoutFlowTest.php`

**Interfaces:**
- Consumes: `CheckoutService` via `POST /orders/store` (Task 5.4), `Product::factory()` (Task 0.3), `Cart`/`CartItem` models (Task 5.1).

- [ ] **Step 1: Write the failing test**

```php
<?php

namespace Modules\MarketplacePipeline\Tests\Feature;

use Modules\IdentityAccess\Models\User;
use Modules\CatalogDelivery\Models\Product;
use Modules\MarketplacePipeline\Models\Cart;
use Modules\MarketplacePipeline\Models\CartItem;
use Modules\MarketplacePipeline\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\MarketplacePipeline\Tests\TestCase;

class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_checkout_creates_order_decrements_stock_and_clears_cart(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $product = Product::factory()->create(['price' => 50, 'stock' => 3]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 2]);

        $this->actingAs($user)->post('/orders/store')->assertRedirect(route('orders.index'));

        $this->assertSame(1, Order::count());
        $this->assertSame(100.0, (float) Order::first()->total_price);
        $this->assertSame(1, $product->fresh()->stock);
        $this->assertSame(0, CartItem::count());
    }

    public function test_checkout_rejects_insufficient_stock(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $product = Product::factory()->create(['price' => 10, 'stock' => 1]);

        $cart = Cart::create(['user_id' => $user->id]);
        CartItem::create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 2]);

        $response = $this->actingAs($user)->post('/orders/store');

        $response->assertSessionHasErrors();
        $this->assertSame(0, Order::count());
        $this->assertSame(1, $product->fresh()->stock);
    }
}
```
(If `Cart::create` requires a `Cart` factory instead, create the row via `Cart::create([...])` as written — the model uses `$fillable` per §5.)

- [ ] **Step 2: Run test to verify behavior**

Run: `php artisan test --filter=CheckoutFlowTest`
Expected: PASS (checkout transaction + stock guard verified; `lockForUpdate` prevents overselling under concurrency — preserved verbatim from the original controller).

- [ ] **Step 3: Phase verification + smoke test**

```bash
php artisan test
php artisan route:list | wc -l
php artisan serve --port=8000 &
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/shop
kill %1
```
Expected: full suite green; route count stable; shop renders 200.

- [ ] **Step 4: Commit**

```bash
git add -A && git commit -m "chore: phase 5 marketplacepipeline complete"
```## Phase 6 — TelemetryPipeline module

### Task 6.1: audit_logs + email_logs migrations + models

**Files:**
- Create: `Modules/TelemetryPipeline/database/migrations/2026_08_16_000001_create_audit_logs_table.php`, `Modules/TelemetryPipeline/database/migrations/2026_08_16_000002_create_email_logs_table.php`
- Create: `Modules/TelemetryPipeline/app/Models/{AuditLog,EmailLog}.php`

**Interfaces:**
- Produces: tables `audit_logs` + `email_logs` (owned by TelemetryPipeline per §15.5 ownership map); models `Modules\TelemetryPipeline\Models\{AuditLog,EmailLog}` — `AuditLog` fillable `actor_id/action/metadata/ip` with `$casts = ['metadata' => 'array']`; `EmailLog` fillable `recipient/subject/status`. Consumed by `TelemetryService` (Task 6.3). First module-owned migrations — nwidart auto-discovers them with the global `php artisan migrate`.

- [ ] **Step 1: Create the migrations**

```bash
mkdir -p Modules/TelemetryPipeline/database/migrations
```

`Modules/TelemetryPipeline/database/migrations/2026_08_16_000001_create_audit_logs_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100);
            $table->json('metadata')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
```

`Modules/TelemetryPipeline/database/migrations/2026_08_16_000002_create_email_logs_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_logs', function (Blueprint $table) {
            $table->id();
            $table->string('recipient', 255);
            $table->string('subject', 255)->nullable();
            $table->string('status', 20)->default('sent');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_logs');
    }
};
```

- [ ] **Step 2: Create the models**

`Modules/TelemetryPipeline/app/Models/AuditLog.php`:
```php
<?php

namespace Modules\TelemetryPipeline\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = ['actor_id', 'action', 'metadata', 'ip'];

    protected $casts = [
        'metadata' => 'array',
    ];
}
```

`Modules/TelemetryPipeline/app/Models/EmailLog.php`:
```php
<?php

namespace Modules\TelemetryPipeline\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = ['recipient', 'subject', 'status'];
}
```

- [ ] **Step 3: Run the migrations**

```bash
php artisan migrate
php artisan migrate:status | grep -E "audit_logs|email_logs"
```
Expected: both tables present.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "feat(telemetrypipeline): audit_logs + email_logs tables and models"
```

### Task 6.2: AnalyticsService (partner dashboard queries)

**Files:**
- Create: `Modules/TelemetryPipeline/app/Services/AnalyticsService.php`
- Modify: `Modules/PartnerHub/app/Http/Controllers/PartnerDashboardController.php`

**Interfaces:**
- Produces: `AnalyticsService::partnerDashboard(Partner $partner): array{inventoryCount, totalRevenue, itemsSold, pendingPayout, recentOrders, chartData}` — queries moved verbatim from `PartnerDashboardController@index`. Cross-module read-only access to `Modules\PartnerHub\Models\Partner`, `Modules\MarketplacePipeline\Models\{Order,OrderItem}`, `Modules\CatalogDelivery\Models\Product` (via `product.partners` relations) — the established Atlas pattern (TelemetryPipeline reads everything; writes stay with owners). Admin dashboard metrics remain in IdentityAccess `GovernanceService` (Task 2.5).

- [ ] **Step 1: Create the service**

```php
<?php

namespace Modules\TelemetryPipeline\Services;

use Modules\PartnerHub\Models\Partner;
use Modules\MarketplacePipeline\Models\Order;
use Modules\MarketplacePipeline\Models\OrderItem;
use Carbon\Carbon;

class AnalyticsService
{
    public function partnerDashboard(Partner $partner): array
    {
        $inventoryCount = $partner->products()->count();

        $allOrderItems = OrderItem::whereHas('product.partners', function ($q) use ($partner) {
            $q->where('partners.id', $partner->id);
        })->get();

        $totalRevenue = $allOrderItems->sum(fn ($item) => $item->price * $item->quantity);
        $itemsSold = $allOrderItems->sum('quantity');

        $pendingPayout = $partner->payouts()->where('status', 'pending')->sum('amount');

        $recentOrders = Order::whereHas('items.product.partners', function ($q) use ($partner) {
            $q->where('partners.id', $partner->id);
        })->latest()->take(5)->get();

        $salesData = OrderItem::whereHas('product.partners', function ($q) use ($partner) {
                $q->where('partners.id', $partner->id);
            })
            ->whereHas('order', function ($q) {
                $q->where('status', 'completed')
                  ->where('created_at', '>=', now()->subDays(30));
            })
            ->selectRaw('DATE(created_at) as date, SUM(price * quantity) as total')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $chartData = [
            'labels' => $salesData->pluck('date')->map(fn ($d) => Carbon::parse($d)->format('M d')),
            'values' => $salesData->pluck('total'),
        ];

        return compact('inventoryCount', 'totalRevenue', 'itemsSold', 'pendingPayout', 'recentOrders', 'chartData');
    }
}
```
Match the original controller's exact eager loads/variables (diff against the moved file if in doubt).

- [ ] **Step 2: Thin PartnerDashboardController**

```php
    public function index(AnalyticsService $analytics)
    {
        $partner = Partner::where('user_id', auth()->id())->firstOrFail();

        return view('partnerhub::partner.dashboard', ['partner' => $partner] + $analytics->partnerDashboard($partner));
    }
```
Import `Modules\TelemetryPipeline\Services\AnalyticsService`; remove the moved query block and the now-unused `Order`/`OrderItem`/`Carbon` imports.

- [ ] **Step 3: Verify**

```bash
composer dump-autoload
php -l Modules/TelemetryPipeline/app/Services/AnalyticsService.php
php artisan test
```
Expected: green.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "feat(telemetrypipeline): AnalyticsService for partner dashboard; thin controller"
```

### Task 6.3: TelemetryService + email logging + low-stock alerts

**Files:**
- Create: `Modules/TelemetryPipeline/app/Services/TelemetryService.php`, `Modules/TelemetryPipeline/app/Providers/EventServiceProvider.php`, `Modules/TelemetryPipeline/app/Services/LowStockAlertService.php`, `Modules/TelemetryPipeline/app/Mail/LowStockAlert.php`, `Modules/TelemetryPipeline/resources/views/emails/low-stock.blade.php`
- Modify: `Modules/IdentityAccess/app/Http/Controllers/AdminUserController.php` (`approve`, `update`, `destroy`), `Modules/MarketplacePipeline/app/Http/Controllers/AdminOrderController.php` (`complete`), `Modules/MarketplacePipeline/app/Http/Controllers/AdminPayoutController.php` (`process`), `Modules/CatalogDelivery/app/Http/Controllers/{ProductController,PartnerInventoryController}.php` (`store`, `update`), `Modules/TelemetryPipeline/module.json` (register `EventServiceProvider`)

**Interfaces:**
- Produces: `TelemetryService::log(string $action, array $metadata = []): AuditLog` (actor = `auth()->id()`, ip = `request()->ip()`); `EventServiceProvider` listens on `Illuminate\Mail\Events\MessageSending` → writes `EmailLog`; `LowStockAlertService::check(Product $product): void` → queues `LowStockAlert` mail to the product's partner users + admins when `stock < config('shop.low_stock_threshold', 5)`. Consumers call these one-liners from their controllers (cross-module service calls — Atlas pattern).

- [ ] **Step 1: Create TelemetryService**

```php
<?php

namespace Modules\TelemetryPipeline\Services;

use Modules\TelemetryPipeline\Models\AuditLog;

class TelemetryService
{
    public function log(string $action, array $metadata = []): AuditLog
    {
        return AuditLog::create([
            'actor_id' => auth()->id(),
            'action' => $action,
            'metadata' => $metadata,
            'ip' => request()->ip(),
        ]);
    }
}
```

- [ ] **Step 2: Create the email-log EventServiceProvider**

```php
<?php

namespace Modules\TelemetryPipeline\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Modules\TelemetryPipeline\Models\EmailLog;

class EventServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(MessageSending::class, function (MessageSending $event) {
            $to = collect($event->message->getTo())->keys()->first();

            EmailLog::create([
                'recipient' => $to,
                'subject' => $event->message->getSubject(),
                'status' => 'sent',
            ]);
        });
    }
}
```
Register it in `Modules/TelemetryPipeline/module.json` under `"providers"`.

- [ ] **Step 3: Create LowStockAlertService + mailable + view**

```php
<?php

namespace Modules\TelemetryPipeline\Services;

use Modules\CatalogDelivery\Models\Product;
use Modules\IdentityAccess\Models\User;
use Modules\TelemetryPipeline\Mail\LowStockAlert;
use Illuminate\Support\Facades\Mail;

class LowStockAlertService
{
    public function check(Product $product): void
    {
        if ($product->stock >= (int) config('shop.low_stock_threshold', 5)) {
            return;
        }

        $recipients = $product->partners()
            ->with('user')
            ->get()
            ->pluck('user')
            ->filter()
            ->merge([User::where('role', 'admin')->first()])
            ->filter(fn ($user) => $user && $user->status === 'active');

        foreach ($recipients->unique('id') as $user) {
            Mail::to($user)->queue(new LowStockAlert($product));
        }
    }
}
```

`Modules/TelemetryPipeline/app/Mail/LowStockAlert.php`:
```php
<?php

namespace Modules\TelemetryPipeline\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Modules\CatalogDelivery\Models\Product;

class LowStockAlert extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Product $product)
    {
    }

    public function build(): self
    {
        return $this->subject('Low stock: ' . $this->product->name)
            ->view('telemetrypipeline::emails.low-stock');
    }
}
```

`Modules/TelemetryPipeline/resources/views/emails/low-stock.blade.php` (no inline CSS, no SQL):
```blade
<h1>Low stock alert</h1>
<p>{{ $product->name }} has only {{ $product->stock }} unit(s) left.</p>
<p><a href="{{ route('partner.inventory.edit', $product) }}">Restock it here</a></p>
```
(Match the markup conventions of the existing email templates.)

- [ ] **Step 4: Wire the hooks**

In `AdminUserController` (IdentityAccess) add `use Modules\TelemetryPipeline\Services\TelemetryService;` + constructor-inject or resolve, then:
- in `approve()`: `$telemetry->log('admin.users.approve', ['user_id' => $id]);`
- in `update()`: `$telemetry->log('admin.users.update', ['user_id' => $id]);`
- in `destroy()`: `$telemetry->log('admin.users.destroy', ['user_id' => $id]);`

In `AdminOrderController@complete` (MarketplacePipeline): `(new TelemetryService)->log('admin.orders.complete', ['order_id' => $order->id]);`
In `AdminPayoutController@process`: `(new TelemetryService)->log('admin.payouts.process', ['payout_id' => $id]);`
In `ProductController@store/update` and `PartnerInventoryController@store/update` (CatalogDelivery): after the product is saved/updated, add `(new LowStockAlertService)->check($product);` with the matching import.

- [ ] **Step 5: Write the telemetry test**

Create `Modules/TelemetryPipeline/tests/Feature/TelemetryTest.php`:
```php
<?php

namespace Modules\TelemetryPipeline\Tests\Feature;

use Modules\IdentityAccess\Models\User;
use Modules\CatalogDelivery\Models\Product;
use Modules\PartnerHub\Models\Partner;
use Modules\TelemetryPipeline\Models\AuditLog;
use Modules\TelemetryPipeline\Models\EmailLog;
use Modules\TelemetryPipeline\Mail\LowStockAlert;
use Modules\TelemetryPipeline\Services\LowStockAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Modules\TelemetryPipeline\Tests\TestCase;

class TelemetryTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_written_on_user_approve(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $user = User::factory()->create(['role' => 'partner', 'status' => 'pending']);

        $this->actingAs($admin)->post("/admin/users/{$user->id}/approve");

        $this->assertSame(1, AuditLog::where('action', 'admin.users.approve')->count());
    }

    public function test_email_log_written_on_mail_send(): void
    {
        $user = User::factory()->create(['status' => 'active']);

        Mail::raw('test body', fn ($message) => $message->to($user->email)->subject('Test'));

        $this->assertSame(1, EmailLog::where('recipient', $user->email)->count());
    }

    public function test_low_stock_alert_queued(): void
    {
        Mail::fake();

        $user = User::factory()->create(['role' => 'partner', 'status' => 'active']);
        $partner = Partner::create(['name' => 'Atelier', 'user_id' => $user->id]);
        $product = Product::factory()->create(['stock' => 2]);
        $product->partners()->attach($partner->id);

        (new LowStockAlertService)->check($product);

        Mail::assertQueued(LowStockAlert::class);
    }
}
```

- [ ] **Step 6: Verify**

```bash
composer dump-autoload
php artisan test --filter=TelemetryTest
php artisan test
```
Expected: PASS; full suite green.

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "feat(telemetrypipeline): audit + email logging, low-stock alerts"
```

### Task 6.4: Rate limiting + health route

**Files:**
- Modify: `Modules/TelemetryPipeline/app/Providers/RouteServiceProvider.php` (`boot()`: `RateLimiter::for`), `Modules/TelemetryPipeline/routes/web.php` (health route), `Modules/IdentityAccess/routes/web.php` (`throttle:auth` on POST `/createaccount` + `/accessaccount`), `Modules/MarketplacePipeline/routes/web.php` (`throttle:checkout` on `orders.store` + `paypal.store`)

**Interfaces:**
- Produces: named limiters `auth` (5 req/min per IP) + `checkout` (3 req/min per IP) — fixes the §13 "no rate limiting on login/register/checkout" gap; new public route `GET /health` → `{"status":"ok"}` (used by CI in Task 7.4).

- [ ] **Step 1: Register the limiters + health route**

In `Modules/TelemetryPipeline/app/Providers/RouteServiceProvider.php::boot()` add:
```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

public function boot(): void
{
    parent::boot();

    RateLimiter::for('auth', fn (Request $request) => Limit::perMinute(5)->by($request->ip()));
    RateLimiter::for('checkout', fn (Request $request) => Limit::perMinute(3)->by($request->ip()));
}
```

In `Modules/TelemetryPipeline/routes/web.php` add:
```php
Route::get('/health', fn () => response()->json(['status' => 'ok']))->name('health');
```

- [ ] **Step 2: Apply the middleware to existing route declarations**

In `Modules/IdentityAccess/routes/web.php`, add `->middleware('throttle:auth')` to the POST `/createaccount` (AuthController@register) and POST `/accessaccount` (AuthController@login) route definitions (keep their names — `createaccount`/`accessaccount` are un-named POSTs; the `login`/`signup` GET closures stay as-is).
In `Modules/MarketplacePipeline/routes/web.php`, add `->middleware('throttle:checkout')` to the `orders.store` and `paypal.store` definitions.

- [ ] **Step 3: Verify**

```bash
php artisan test
php artisan serve --port=8000 &
curl -s http://127.0.0.1:8000/health
kill %1
```
Expected: suite green; `/health` returns `{"status":"ok"}`.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "feat(telemetrypipeline): rate limiting on auth/checkout; health route"
```

### Task 6.5: No-SQL / no-CSS audit + phase verification

- [ ] **Step 1: Audit module blades for SQL and inline CSS**

```bash
rg -n "::where|DB::|selectRaw|->get\(\)|Model::" Modules/*/resources/views || echo "no SQL in module blades"
rg -n "<style" Modules/*/resources/views || echo "no inline styles in module blades"
rg -n "@php" Modules/*/resources/views || echo "no @php blocks in module blades"
```
Fix any straggler into the owning module's `Services/` (SQL) or `resources/assets/scss/app.scss` (styles). Re-run until all three commands print their `no ...` message.

- [ ] **Step 2: Full verification**

```bash
php artisan test
php artisan route:list | wc -l
php artisan migrate:status | tail -8
npm run build
```
Expected: full suite green; build succeeds; migrations all present.

- [ ] **Step 3: Commit**

```bash
git add -A && git commit -m "chore: phase 6 telemetrypipeline complete"
```

## Phase 7 — Hardening: dead code cleanup, migrations, docs, CI

### Task 7.1: Delete dead / legacy / broken code (§11)

**Files:**
- Delete: `resources/components/product-card.blade.php`, `resources/dashboard/home.blade.php`, `resources/partials/nav.blade.php`, `resources/users/`, `resources/views/admin.blade.php`, `resources/views/products/show.blade.php`, `resources/views/users/{index,edit}.blade.php`, `resources/views/partner/pagination/` (9 files), `resources/js/app.js`, `app/Providers/AuthServiceProvider.php`, `Modules/CatalogDelivery/app/Policies/{ProductPolicy,ReviewPolicy}.php`, `database/migrations/2026_05_15_070331_add_provider_to_payments_table.php`, `database_dump.sql`
- Modify: `Modules/MarketplacePipeline/app/Models/Payment.php` (drop `provider` from `$fillable`)

- [ ] **Step 1: Delete the files**

```bash
git rm resources/components/product-card.blade.php
git rm resources/dashboard/home.blade.php
git rm resources/partials/nav.blade.php
rmdir resources/dashboard resources/users 2>/dev/null
git rm resources/views/admin.blade.php
git rm resources/views/products/show.blade.php
git rm resources/views/users/index.blade.php resources/views/users/edit.blade.php
git rm resources/views/partner/pagination/*.blade.php
rmdir resources/views/partner/pagination 2>/dev/null
git rm resources/js/app.js
git rm app/Providers/AuthServiceProvider.php
git rm Modules/CatalogDelivery/app/Policies/ProductPolicy.php Modules/CatalogDelivery/app/Policies/ReviewPolicy.php
rmdir Modules/CatalogDelivery/app/Policies 2>/dev/null
git rm database/migrations/2026_05_15_070331_add_provider_to_payments_table.php
git rm database_dump.sql
```
(Do NOT touch `resources/views/partner/orders/show.blade.php` — it is the live fulfillment view; the stray `tml>` typo in `emails/orders/confirmed.blade.php` was fixed in Task 5.5. Do NOT delete `AuthController@apiRegister/apiLogin` — live API surface.)

- [ ] **Step 2: Clean the dead fillable**

In `Modules/MarketplacePipeline/app/Models/Payment.php`, remove `'provider'` from `$fillable` (the column never existed — no-op migration deleted in Step 1).

- [ ] **Step 3: Verify**

```bash
composer dump-autoload
php artisan test
php artisan route:list | wc -l
```
Expected: green; route count unchanged.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "chore: delete dead code inventory (§11); remove no-op payment migration"
```

### Task 7.2: Move migrations + seeders into owning modules

**Files:**
- Move (by ownership map §15.5): migrations below; seeders below
- Modify: `database/seeders/DatabaseSeeder.php` (call module seeders)

**Interfaces:**
- Produces: all app migrations in module dirs (framework files `0001_01_01_000000/1/2` stay in core — they create `users`, `password_reset_tokens`, `sessions`, `cache`, `jobs`, `job_batches`, `failed_jobs`); module seeders `Modules\IdentityAccess\Database\Seeders\UserSeeder`, `Modules\CatalogDelivery\Database\Seeders\{CategorySeeder,ProductSeeder,ReviewSeeder}`, `Modules\MarketplacePipeline\Database\Seeders\OrderSeeder`. Global migration order is preserved (Laravel sorts the merged file list by filename string, timestamps unchanged).

- [ ] **Step 1: Move migrations** (one `git mv` per file — run in the owning module's `database/migrations/` dirs)

```bash
mkdir -p Modules/IdentityAccess/database/migrations
mkdir -p Modules/CatalogDelivery/database/migrations
mkdir -p Modules/MarketplacePipeline/database/migrations
mkdir -p Modules/PartnerHub/database/migrations
mkdir -p Modules/TelemetryPipeline/database/migrations

# IdentityAccess (addresses belong with user profile data)
git mv database/migrations/2026_04_18_102205_create_users_table.php Modules/IdentityAccess/database/migrations/
git mv database/migrations/2026_06_13_115632_add_status_and_confirmations_to_users_table.php Modules/IdentityAccess/database/migrations/
git mv database/migrations/2026_06_11_111553_create_wishlists_table.php Modules/IdentityAccess/database/migrations/
git mv database/migrations/2026_04_18_102755_create_addresses_table.php Modules/IdentityAccess/database/migrations/
git mv database/migrations/2026_06_03_142915_add_is_primary_to_addresses_table.php Modules/IdentityAccess/database/migrations/
git mv database/migrations/2026_05_04_214206_create_personal_access_tokens_table.php Modules/IdentityAccess/database/migrations/

# CatalogDelivery
git mv database/migrations/2026_04_18_102301_create_categories_table.php Modules/CatalogDelivery/database/migrations/
git mv database/migrations/2026_04_18_102334_create_products_table.php Modules/CatalogDelivery/database/migrations/
git mv database/migrations/2026_04_18_102558_create_product_images_table.php Modules/CatalogDelivery/database/migrations/
git mv database/migrations/2026_04_18_102624_create_product_variants_table.php Modules/CatalogDelivery/database/migrations/
git mv database/migrations/2026_04_18_102653_create_reviews_table.php Modules/CatalogDelivery/database/migrations/
git mv database/migrations/2026_06_13_104753_add_status_to_reviews_table.php Modules/CatalogDelivery/database/migrations/

# MarketplacePipeline
git mv database/migrations/2026_04_18_102405_create_cart_table.php Modules/MarketplacePipeline/database/migrations/
git mv database/migrations/2026_04_18_102433_create_cart_items_table.php Modules/MarketplacePipeline/database/migrations/
git mv database/migrations/2026_04_18_102500_create_orders_table.php Modules/MarketplacePipeline/database/migrations/
git mv database/migrations/2026_04_18_102528_create_order_items_table.php Modules/MarketplacePipeline/database/migrations/
git mv database/migrations/2026_04_18_102720_create_payments_table.php Modules/MarketplacePipeline/database/migrations/
git mv database/migrations/2026_06_14_103206_create_payouts_table.php Modules/MarketplacePipeline/database/migrations/

# PartnerHub (rename chain stays in order: vendors → partners)
git mv database/migrations/2026_04_18_102828_create_vendors_table.php Modules/PartnerHub/database/migrations/
git mv database/migrations/2026_06_13_104753_add_details_to_vendors_table.php Modules/PartnerHub/database/migrations/
git mv database/migrations/2026_06_13_142000_add_user_id_to_vendors_table.php Modules/PartnerHub/database/migrations/
git mv database/migrations/2026_06_13_182400_rename_vendors_to_partners_table.php Modules/PartnerHub/database/migrations/
git mv database/migrations/2026_04_18_102853_create_vendor_products_table.php Modules/PartnerHub/database/migrations/
git mv database/migrations/2026_06_13_182928_rename_vendor_id_to_partner_id_in_partner_products_table.php Modules/PartnerHub/database/migrations/
```
The two files sharing timestamp `2026_06_13_104753` (`add_details_to_vendors_table` → PartnerHub, `add_status_to_reviews_table` → CatalogDelivery) keep their relative order because the merged list sorts by full filename (`a-d-d-d` < `a-d-d-s`).

- [ ] **Step 2: Move seeders + fix namespaces**

```bash
mkdir -p Modules/IdentityAccess/database/seeders
mkdir -p Modules/CatalogDelivery/database/seeders
mkdir -p Modules/MarketplacePipeline/database/seeders
git mv database/seeders/UserSeeder.php Modules/IdentityAccess/database/seeders/UserSeeder.php
git mv database/seeders/CategorySeeder.php Modules/CatalogDelivery/database/seeders/CategorySeeder.php
git mv database/seeders/ProductSeeder.php Modules/CatalogDelivery/database/seeders/ProductSeeder.php
git mv database/seeders/ReviewSeeder.php Modules/CatalogDelivery/database/seeders/ReviewSeeder.php
git mv database/seeders/OrderSeeder.php Modules/MarketplacePipeline/database/seeders/OrderSeeder.php
```
Rewrite each seeder's namespace + imports (`Modules\IdentityAccess\Database\Seeders\UserSeeder`; CatalogDelivery: `Modules\CatalogDelivery\Database\Seeders\{CategorySeeder,ProductSeeder,ReviewSeeder}` with model imports from the owning modules; `Modules\MarketplacePipeline\Database\Seeders\OrderSeeder`). Update `database/seeders/DatabaseSeeder.php` to call the module seeders in the same order as today:
```php
$this->call([
    \Modules\IdentityAccess\Database\Seeders\UserSeeder::class,
    \Modules\CatalogDelivery\Database\Seeders\CategorySeeder::class,
    \Modules\CatalogDelivery\Database\Seeders\ProductSeeder::class,
    \Modules\CatalogDelivery\Database\Seeders\ReviewSeeder::class,
    \Modules\MarketplacePipeline\Database\Seeders\OrderSeeder::class,
]);
```
Keep the original call order — diff the file first.

- [ ] **Step 3: Verify — migrate order preserved + fresh seed works**

```bash
composer dump-autoload
php artisan migrate:status
touch /tmp/opencode/migrate_check.sqlite
DB_CONNECTION=sqlite DB_DATABASE=/tmp/opencode/migrate_check.sqlite php artisan migrate:fresh --seed
rm -f /tmp/opencode/migrate_check.sqlite
php artisan test
```
Expected: `migrate:status` shows every migration (order identical to pre-move); fresh migrate + seed completes on a scratch DB; test suite green.

- [ ] **Step 4: Commit**

```bash
git add -A
git commit -m "refactor: move migrations + seeders into owning modules"
```

### Task 7.3: Per-module test suites + ownership docs

**Files:**
- Move: `tests/Feature/WishlistTest.php` → `Modules/IdentityAccess/tests/Feature/`, `tests/Feature/ReviewSubmissionTest.php` → `Modules/CatalogDelivery/tests/Feature/`
- Create: `MODULE_OWNERSHIP.md`
- Modify: `docs/PROJECT_ARCHITECTURE.md` (§12.3 git state + §15 status note)

**Interfaces:**
- Produces: module test suites runnable per-module (`php artisan test Modules/<M>/tests`); root `tests/` keeps only `ExampleTest`. Ownership doc follows the Atlas `MODULE_OWNERSHIP.md` convention (table + service map + cross-module rules).

- [ ] **Step 1: Move remaining root tests into module suites**

```bash
mkdir -p Modules/IdentityAccess/tests/Feature
mkdir -p Modules/CatalogDelivery/tests/Feature
git mv tests/Feature/WishlistTest.php Modules/IdentityAccess/tests/Feature/WishlistTest.php
git mv tests/Feature/ReviewSubmissionTest.php Modules/CatalogDelivery/tests/Feature/ReviewSubmissionTest.php
```
Rewrite headers: `namespace Modules\IdentityAccess\Tests\Feature;` + `use Modules\IdentityAccess\Tests\TestCase;` (WishlistTest); `namespace Modules\CatalogDelivery\Tests\Feature;` + `use Modules\CatalogDelivery\Tests\TestCase;` (ReviewSubmissionTest). Fix model imports to module namespaces. Delete `tests/Feature/ExampleTest.php` if present.

- [ ] **Step 2: Run every module suite**

```bash
php artisan test Modules/IdentityAccess/tests
php artisan test Modules/CatalogDelivery/tests
php artisan test Modules/MarketplacePipeline/tests
php artisan test Modules/PartnerHub/tests
php artisan test Modules/TelemetryPipeline/tests
```
Expected: each green (phpunit.xml suites from Task 1.5 pick the dirs up).

- [ ] **Step 3: Create MODULE_OWNERSHIP.md** (repo root, Atlas convention) — table: Module / Owns (tables) / Reads / View alias / Key services, plus a "Cross-module rules" section (read via the owning module's services; blades never query; single DB; new features land in their module).

- [ ] **Step 4: Update the architecture doc**

Append to `docs/PROJECT_ARCHITECTURE.md` §12.3 (git state) and add a status line to §15: migration complete, phases 0–7 done, pointing to `MODULE_OWNERSHIP.md`.

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "docs: MODULE_OWNERSHIP.md; move remaining tests into module suites"
```

### Task 7.4: CI workflow

**Files:**
- Create: `.github/workflows/tests.yml`

**Interfaces:**
- Produces: GitHub Actions workflow running on push to `main` + pull requests: PHP 8.3, composer install, `.env` + key, npm ci + build (module assets required by `@vite` in views), `php artisan test` (sqlite `:memory:` per phpunit.xml).

- [ ] **Step 1: Create the workflow**

`.github/workflows/tests.yml`:
```yaml
name: Tests

on:
  push:
    branches: [main]
  pull_request:

jobs:
  tests:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, xml, curl, zip, sqlite3
          coverage: none

      - name: Get composer cache directory
        id: composer-cache
        run: echo "dir=$(composer config cache-files-dir)" >> "$GITHUB_OUTPUT"

      - name: Cache composer dependencies
        uses: actions/cache@v4
        with:
          path: ${{ steps.composer-cache.outputs.dir }}
          key: ${{ runner.os }}-composer-${{ hashFiles('**/composer.lock') }}
          restore-keys: ${{ runner.os }}-composer-

      - name: Install dependencies
        run: composer install --no-interaction --prefer-dist --no-progress

      - name: Prepare environment
        run: cp .env.example .env && php artisan key:generate

      - name: Install frontend dependencies
        run: npm ci

      - name: Build assets
        run: npm run build

      - name: Run tests
        run: php artisan test
```
(No MySQL service needed — the suite runs on sqlite `:memory:`.)

- [ ] **Step 2: Verify**

```bash
php artisan test
npm run build
```
Expected: both green locally (the workflow itself validates on the next push/PR).

- [ ] **Step 3: Commit**

```bash
git add -A
git commit -m "ci: GitHub Actions test workflow"
```

### Task 7.5: Final verification + close-out commit

- [ ] **Step 1: Full verification battery**

```bash
php artisan test
php artisan route:list | wc -l
rg -n "::where|DB::|selectRaw|<style|@php" Modules/*/resources/views || echo "module blades clean"
composer validate
npm run build
php artisan migrate:status | tail -5
```
Expected: suite green (~60+ tests incl. PayoutSplitTest, CheckoutFlowTest, TelemetryTest, WishlistTest, ReviewSubmissionTest); route count stable (~70); no SQL/inline CSS in module blades; composer valid; build succeeds.

- [ ] **Step 2: Final commit**

```bash
git add -A && git commit -m "chore: modular monolith migration complete (phases 0-7)"
```