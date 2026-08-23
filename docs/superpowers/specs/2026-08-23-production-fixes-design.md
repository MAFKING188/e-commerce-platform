# Production Fixes Design Document

## Overview
Implement production-level fixes based on user feedback, reduce product count for testing cost optimization, and ensure image-product matching.

## Current State
- 110 products across 7 categories (15 per category)
- Images already downloaded to `storage/app/public/products/curated/` (115 images)
- Images correctly mapped by product name in `CatalogInventory::IMAGES`
- Prices are intentionally high (test data) - NOT changing per user request

## Design Sections

### 1. Reduce Product Count (5-6 per category)
**Goal:** ~35-42 total products to minimize SQL query costs on DigitalOcean

**Approach:** Modify `CatalogInventory::CATALOG` to keep only first 5-6 products per category. The IMAGES mapping already exists for all products, so we just reduce the CATALOG array.

**Categories & Target Counts:**
- Electronics: 6 (from 18)
- Clothing: 6 (from 15)
- Home & Kitchen: 6 (from 17)
- Books: 5 (from 15)
- Beauty & Wellness: 5 (from 15)
- Sports & Outdoors: 6 (from 15)
- Toys & Games: 5 (from 15)
- **Total: ~39 products**

### 2. Cart Icon with Count Badge in Header
**Goal:** Persistent visible cart access with item count

**Implementation:**
- Add cart icon SVG to navbar (next to theme toggle)
- Show count badge using `Cart::count()` or session cart count
- Link to `cart.index` route
- Style: match existing theme-toggle button styling

**Data Source:** 
- Authenticated: `Cart::where('user_id', auth()->id())->first()->items()->sum('quantity')`
- Guest: session cart count from `session('cart', [])`

### 3. Toast Notification on Add to Cart
**Goal:** Immediate feedback when adding to bag

**Implementation:**
- Modify `storefront/cart` add endpoint to return JSON with success message
- Frontend: AJAX call to add endpoint, show toast on success
- Use existing `showToast()` function from app-layout

### 4. Guest Add-to-Cart Redirect Fix
**Goal:** Preserve context when guest adds to cart

**Implementation:**
- In `AddToCartController` (or similar), check `auth()->check()`
- If guest: store intended product in session, redirect to login with `intended` URL
- After login, redirect back to product page or cart with flash message
- Flash message: "Sign in to save your bag — your item is waiting"

### 5. Checkout UX Improvements
**Goal:** Reduce abandonment during checkout

**Changes:**
- **Show step-up section on first load**: Don't hide verification section behind failed submit
- **Persist address data**: Use `old()` helper on all address form inputs
- **Don't burn OTP on unrelated failures**: Only consume OTP on successful verification, not on validation errors
- **Preserve form state**: Use `old()` for all form fields including verification code input

### 6. Homepage Deduplication
**Goal:** Editor's Choice and Latest Drop show different products

**Implementation:**
- **Editor's Choice**: Products with highest average rating (reviews) → fallback to `is_featured` flag
- **Latest Drop**: Most recently created products (`latest()`)
- Ensure no overlap: exclude Editor's Choice IDs from Latest Drop query
- Add `is_featured` boolean column to products table (migration)
- Seed 2-3 featured products per category

### 7. Image-Product Matching Verification
**Goal:** Ensure all images semantically match their products

**Current State:** Images are mapped by exact product name in `CatalogInventory::IMAGES` and downloaded locally. The mapping appears correct for the 3 reported mismatches (Chalk Bag, LED Bike Lights, Wooden Blocks).

**Action:** 
- Verify all 39 products have correct local image paths
- Run a quick visual check by visiting product pages
- If any mismatches found, update `CatalogInventory::IMAGES` with correct Unsplash URLs and re-download

### 8. Additional Polish (from feedback)
- **Contact page**: Replace student email with generic business contact
- **Footer**: Remove "Support This Project" PayPal link
- **Hero text**: Make "COLLECTION / 26" dynamic from `Product::published()->count()`
- **404 page**: Create branded 404 with search link
- **Mobile header z-index**: Fix overlap with section headings
- **Review UX**: Show "Submitted for moderation" toast + pending state indicator
- **Login accessibility**: Add proper labels, fix "Remember me" functionality

## Technical Details

### Migrations Needed
1. Add `is_featured` boolean to products table (default false, index)

### Controllers to Modify
1. `CartController` - add count API endpoint, guest redirect logic
2. `CheckoutController` - address persistence, OTP handling
3. `HomeController` - deduplicated product queries

### Views to Modify
1. `components/app-layout.blade.php` - cart icon in header
2. `home.blade.php` - Editor's Choice vs Latest Drop queries
3. `checkout.blade.php` - show step-up first, old() helpers
4. `auth/login.blade.php` - labels, remember me
4. `contact.blade.php` - update contact info
5. `404.blade.php` - new branded page

### JavaScript
1. Add cart count fetch on page load
2. AJAX add-to-cart with toast feedback
3. Update cart count badge after add

## Success Criteria
- [ ] Products reduced to ~39 total (5-6 per category)
- [ ] Cart icon visible in header with live count
- [ ] Toast appears on "Add to Bag" click
- [ ] Guest add-to-cart redirects to login with context preserved
- [ ] Checkout shows verification step-up immediately
- [ ] Address form persists on reload/validation error
- [ ] OTP not consumed on validation failures
- [ ] Editor's Choice and Latest Drop show different products
- [ ] All product images match their descriptions
- [ ] Contact page has professional contact info
- [ ] Footer has no PayPal donation link
- [ ] Hero shows dynamic product count
- [ ] Branded 404 page exists
- [ ] Mobile header doesn't overlap content
- [ ] Review submission shows moderation status
- [ ] Login form has proper labels and working "Remember me"

## Risks & Mitigations
- **Risk**: Reducing products breaks existing tests
  - **Mitigation**: Run test suite after changes, update any hardcoded counts
- **Risk**: Cart count performance on every page load
  - **Mitigation**: Cache count for 30 seconds, or compute via AJAX after page load
- **Risk**: OTP logic change breaks 2FA flow
  - **Mitigation**: Test thoroughly with both buyer and admin/partner flows