# Remaining Feedback Fixes Design Document

## Overview
Implement production-level fixes for 8 remaining feedback items (excluding checkout UX #7). All changes are low-to-medium risk, primarily view/controller updates.

## Items to Implement

| # | Issue | Priority |
|---|-------|----------|
| 2 | Image-content audit (verify current mappings) | Low |
| 5 | Global toast on add-to-cart (collection/shop pages) | Medium |
| 8 | Login labels + "Remember me" functionality | Low |
| 9 | Contact page: replace student email | Trivial |
| 10 | Remove PayPal "Support This Project" from footer | Trivial |
| 12 | Branded 404 page | Low |
| 13 | Mobile header z-index overlap | Low |
| 14 | Review submission: "awaiting moderation" toast + pending state | Medium |

---

## Design Sections

### 1. Image-Content Audit (#2)
**Goal:** Verify all 39 product images semantically match their products.

**Approach:** No code changes needed. The `CatalogInventory::IMAGES` array maps exact product names to curated Unsplash URLs. Run visual verification on product pages. If mismatches found, update URLs in `CatalogInventory::IMAGES` and re-run seeder.

**Files:** `Modules/CatalogDelivery/database/seeders/CatalogInventory.php` (only if changes needed)

---

### 2. Global Toast on Add-to-Cart (#5)
**Goal:** Show toast notification when adding to cart from collection/shop pages (not just product page).

**Approach:** Add a global form submit listener in `app-layout.blade.php` that intercepts POST to `/cart/add`, sends via fetch, shows toast, updates cart count badge.

**Files:**
- `resources/views/components/app-layout.blade.php` - add global JS listener
- `Modules/CatalogDelivery/resources/views/components/product-card.blade.php` - add data attributes for AJAX

**Technical Details:**
- Product cards currently use anchor tags for wishlist, but "Add to Bag" is on product page only
- Collection/shop pages show product cards only (no add-to-cart button)
- **Decision needed:** Add "Add to Bag" button to product cards? Or keep add-to-cart only on product page?
- **Recommendation:** Keep add-to-cart on product page only (standard e-commerce pattern). The toast already works there. Close this item as "working as designed" - collection pages are for browsing, product page for purchasing.

---

### 3. Login Labels + Remember Me (#8)
**Goal:** Fix accessibility - inputs already have labels, but "Remember me" checkbox needs backend support.

**Current State:** Login view already has `<label class="form-label">` for email/password. "Remember me" checkbox exists but not wired to backend.

**Approach:** 
1. Verify LoginController uses `$request->remember` for `Auth::attempt()`
2. If not, update controller

**Files:**
- `Modules/IdentityAccess/app/Http/Controllers/Auth/LoginController.php` (or similar)
- Login view already correct

---

### 4. Contact Page Email (#9)
**Goal:** Replace student email `m.luwi0049@uca.ca.ma` with professional business email.

**Approach:** Update contact view to use generic support email (e.g., `support@smartshop-luwi.tech` or `hello@smartshop-luwi.tech`)

**Files:**
- `Modules/CatalogDelivery/resources/views/contact.blade.php` - line 13

---

### 5. Remove PayPal from Footer (#10)
**Goal:** Remove "Support This Project" PayPal donation link from footer.

**Approach:** Remove the `<li>` containing the PayPal link from footer in app-layout.

**Files:**
- `resources/views/components/app-layout.blade.php` - lines 175-176

---

### 6. Branded 404 Page (#12)
**Goal:** Create branded 404 page with search link and "Back to Shop" button.

**Approach:** Create `resources/views/errors/404.blade.php` extending app-layout.

**Design:**
- Hero section with "404" and "Page Not Found"
- Brief message
- Search link (to `/shop`)
- "Back to Shop" button (to `/`)
- Consistent with site styling (dark/light mode support)

**Files:**
- `resources/views/errors/404.blade.php` (new)

---

### 7. Mobile Header Z-Index (#13)
**Goal:** Fix sticky header overlapping section headings on mobile scroll.

**Current State:** Header has `z-index: 1000`, `position: sticky`. Section headings may have higher stacking context.

**Approach:** 
1. Ensure header z-index is highest (1000+)
2. Add `padding-top` to main content equal to header height
3. Or use `scroll-padding-top` on html

**Files:**
- `resources/css/app.css` - header styles, main padding

---

### 8. Review Submission UX (#14)
**Goal:** Show "Submitted for moderation" toast + display pending reviews to author.

**Current State:** 
- ReviewController@store sets status='pending' and flashes 'success' message
- Product view only shows approved reviews (`$product->reviews` - need to check if filtered)
- No toast shown (session flash only)

**Approach:**
1. Update ReviewController to return JSON for AJAX + flash for traditional
2. Show toast via existing `showToast()` on product page
3. In product view, show user's own pending reviews with "Awaiting moderation" badge
4. Filter `$product->reviews` to only show approved by default, but include user's pending

**Files:**
- `Modules/CatalogDelivery/app/Http/Controllers/ReviewController.php` - store method
- `Modules/CatalogDelivery/resources/views/product.blade.php` - reviews section
- `Modules/CatalogDelivery/app/Models/Review.php` - add scope for approved + user's pending

**Technical Details:**
- Product view receives `$reviews` from CatalogQueryService::product()
- Currently: `$product->reviews` loads all reviews (no status filter in relation)
- Need to: filter to approved + current user's pending

---

## Implementation Order

1. **Quick wins** (parallel): #9, #10, #12, #13 (~2h)
2. **Login/Review UX**: #8, #14 (~3h)
3. **Image audit**: #2 (~1h manual)
4. **Global toast**: #5 - decision needed on scope (~2h if needed)

---

## Risk Assessment

| Item | Risk | Mitigation |
|------|------|------------|
| #2 Image audit | None | Manual verification only |
| #5 Global toast | Medium | Only if adding buttons to cards |
| #8 Login | Low | Labels already exist, just Remember me |
| #9 Contact | None | Single string change |
| #10 Footer | None | Single line removal |
| #12 404 page | None | New file, no existing deps |
| #13 Mobile CSS | Low | CSS only, test on mobile |
| #14 Reviews | Low | Additive - shows more, doesn't hide |

---

## Success Criteria

- [ ] Contact page shows professional email
- [ ] Footer has no PayPal link
- [ ] 404 page renders with branding + navigation
- [ ] Mobile header doesn't overlap content
- [ ] Login "Remember me" works (persists session)
- [ ] Review submission shows toast + pending badge visible to author
- [ ] All product images verified as matching
- [ ] All 200 tests still pass