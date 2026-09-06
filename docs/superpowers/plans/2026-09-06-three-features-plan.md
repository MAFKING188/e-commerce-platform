# Implementation Plan: Cart Sharing, Share Button, Admin Email Replies

## Overview

Three features to implement following the existing modular monolith architecture:
1. **Share Cart** — token-based shareable cart link; recipient logs in, cart is cloned, proceeds to checkout
2. **Share Button** — Web Share API + copy-to-clipboard on product pages and cart
3. **Admin Email Replies** — reply to contact inquiries from admin interface, sent via SmartShop Gmail SMTP

---

## Feature 1: Share Cart

### Concept
- Each cart gets a unique `share_token` (UUID)
- Cart page shows a "Share Cart" button that copies a link like `https://smartshop-luwi.tech/cart/shared/{token}`
- Recipient opens the link → sees the shared cart contents → must log in → cart items are cloned into their own cart → redirected to checkout

### Database Changes (Migration)
**New migration:** `Modules/MarketplacePipeline/database/migrations/2026_09_06_200000_add_share_token_to_carts_table.php`
```php
Schema::table('carts', function (Blueprint $table) {
    $table->string('share_token', 36)->nullable()->unique()->after('user_id');
});
```
- Add `share_token` to `Cart` model `$fillable`
- Generate token in `CartController::index()` if null: `$cart->updateOrCreate(['share_token' => Str::uuid()])`

### New Files
1. **Route:** Add to `Modules/MarketplacePipeline/routes/web.php` (inside auth group):
   ```php
   Route::get('/cart/shared/{token}', [CartController::class, 'showShared'])->name('cart.shared');
   Route::post('/cart/clone/{token}', [CartController::class, 'cloneShared'])->name('cart.clone');
   ```

2. **Controller methods** in `CartController`:
   - `showShared($token)` — finds cart by share_token, loads items with products, renders shared view
   - `cloneShared($token)` — finds cart by share_token, clones items into authenticated user's cart, redirects to cart.index

3. **View:** `Modules/MarketplacePipeline/resources/views/cart/shared.blade.php`
   - Read-only view of shared cart items (product images, names, prices, quantities)
   - "Clone to My Cart & Checkout" button (POST form, requires login)
   - "Login to Clone" link if not authenticated
   - Uses `<x-app-layout>`

### Existing Files Modified
- `Modules/MarketplacePipeline/app/Models/Cart.php` — add `share_token` to `$fillable`
- `Modules/MarketplacePipeline/app/Http/Controllers/CartController.php` — add `showShared()`, `cloneShared()`, generate token in `index()`
- `Modules/MarketplacePipeline/resources/views/cart/index.blade.php` — add "Share Cart" button (copy-to-clipboard)
- `Modules/MarketplacePipeline/routes/web.php` — add 2 new routes

---

## Feature 2: Share Button

### Concept
- On product detail pages: a "Share" icon button next to "Add to Bag"
- Uses Web Share API (native share on mobile) with fallback to copy-to-clipboard
- On cart page: a "Share Cart" button (handled by Feature 1)

### New Files
None — all changes are inline in existing views.

### Existing Files Modified
1. **`Modules/CatalogDelivery/resources/views/product.blade.php`** — add share button after the add-to-cart form:
   ```html
   <button type="button" class="btn-share" onclick="shareProduct()" aria-label="Share this product">
       <svg>...</svg> Share
   </button>
   <script>
   function shareProduct() {
       const url = '{{ url()->current() }}';
       const title = '{{ $product->name }}';
       const text = 'Check out {{ $product->name }} on SmartShop — @money($product->price)';
       if (navigator.share) {
           navigator.share({ title, text, url });
       } else {
           navigator.clipboard.writeText(url);
           // show "Link copied!" toast
       }
   }
   </script>
   ```

2. **`Modules/CatalogDelivery/resources/assets/scss/app.scss`** — add `.btn-share` styles (inline-flex, icon + text, subtle border)

3. **`Modules/MarketplacePipeline/resources/views/cart/index.blade.php`** — add share cart button in summary card (copy share link to clipboard)

---

## Feature 3: Admin Email Replies to Contact Inquiries

### Concept
- Admin sees each contact message with a "Reply" button
- Clicking Reply opens an inline reply form (textarea + send button)
- On submit, a Mailable is sent via SmartShop Gmail SMTP (`no-reply@smartshop-luwi.tech`)
- Reply is stored in a new `contact_replies` table
- Contact message gets a `status` field (new/replied/archived)

### Database Changes (Migration)
**New migration:** `Modules/CatalogDelivery/database/migrations/2026_09_06_210000_add_status_to_contact_messages_table.php`
```php
Schema::table('contact_messages', function (Blueprint $table) {
    $table->string('status', 20)->default('new')->after('message');
});
```

**New migration:** `Modules/CatalogDelivery/database/migrations/2026_09_06_220000_create_contact_replies_table.php`
```php
Schema::create('contact_replies', function (Blueprint $table) {
    $table->id();
    $table->foreignId('contact_message_id')->constrained()->cascadeOnDelete();
    $table->text('body');
    $table->string('admin_name');
    $table->timestamps();
});
```

### New Files
1. **Model:** `Modules/CatalogDelivery/app/Models/ContactReply.php`
   - `$fillable`: `contact_message_id`, `body`, `admin_name`
   - `belongsTo(ContactMessage::class)`

2. **Mailable:** `Modules/CatalogDelivery/app/Mail/ContactReplyMail.php`
   - Sends from `no-reply@smartshop-luwi.tech` (existing SMTP)
   - To: the inquiry's email address
   - Subject: `Re: Your inquiry to SmartShop`
   - Markdown template with admin reply body + original message quote
   - Implements `ShouldQueue`

3. **View:** `Modules/CatalogDelivery/resources/views/emails/contact-reply.blade.php`
   - Markdown mail showing the admin's reply
   - Quoted original message below
   - SmartShop branding

4. **Route:** Add to `Modules/CatalogDelivery/routes/web.php`:
   ```php
   Route::post('/admin/contacts/{id}/reply', [AdminContactController::class, 'reply'])->name('contacts.reply');
   ```

### Existing Files Modified
- `Modules/CatalogDelivery/app/Models/ContactMessage.php` — add `status` to `$fillable`, add `replies()` hasMany
- `Modules/CatalogDelivery/app/Http/Controllers/AdminContactController.php` — add `reply()` method
- `Modules/CatalogDelivery/resources/views/admin/contacts/index.blade.php` — add status badges, reply button, inline reply form per message
- `Modules/CatalogDelivery/routes/web.php` — add reply route
- `Modules/CatalogDelivery/app/Http/Controllers/ContactController.php` — set `status = 'new'` on creation

---

## Execution Order

1. **Feature 3 (Admin Email Replies)** — highest value, immediate business need
   - Create migration for `status` column + `contact_replies` table
   - Create `ContactReply` model
   - Create `ContactReplyMail` mailable
   - Create email template
   - Add `reply()` method to `AdminContactController`
   - Update admin contacts view with reply form + status badges
   - Add route
   - Run migration, test

2. **Feature 2 (Share Button)** — quick win, no DB changes
   - Add share button to product detail view
   - Add share cart button to cart page
   - Add CSS styles
   - Test

3. **Feature 1 (Share Cart)** — requires DB migration
   - Create migration for `share_token`
   - Update Cart model
   - Add controller methods
   - Create shared cart view
   - Add routes
   - Run migration, test

---

## Verification

- Run `php artisan test` after each feature
- Run `php artisan migrate` on prod (no data loss — additive only)
- Manual test on prod:
  - Feature 3: Submit contact form → see in admin → reply → verify email arrives from no-reply@smartshop-luwi.tech
  - Feature 2: Open product page → click Share → verify Web Share API or clipboard works
  - Feature 1: Open cart → click Share → copy link → open in incognito → verify shared cart view → login → verify items cloned
