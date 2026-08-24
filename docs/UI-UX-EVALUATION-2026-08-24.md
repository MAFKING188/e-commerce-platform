# SmartShop (LUWI) — Production UI/UX Evaluation
**Date:** 2026-08-24 · **Target:** https://smartshop-luwi.tech (live)
**Method:** Full persona walkthrough via browser automation — guest, buyer (real signup→cart→checkout→OTP→order), partner (login→orders→ship→email center), admin (members edit, audit trail, email center). Desktop + mobile (390×844), light + dark themes. Screenshots: `/tmp/opencode/prod-eval/`.

---

## Part 1 — What a user wouldn't like

### 1.1 Deal-breakers (would stop a purchase)
| # | Finding | Evidence |
|---|---|---|
| 1 | **Absurd pricing.** Bamboo Toothbrush Set **$2,999**, Dead Sea Salt Scrub $1,800, Carbon Road Helmet $2,443, Yoga Mat Pro $2,712. Nothing is priced like a real store; the seed prices destroy purchase trust on the first screen. | `04-shop-light.png`, `01-home-light.png` |
| 2 | **Product images don't match products.** "Carbon Road Helmet" → photo of an entire bicycle; "Rose Quartz Facial Roller" → eyeshadow palette. Files are valid and fast — the *content* is random. Buyers cannot trust what arrives. | `01`, `13` |
| 3 | **Checkout button unreachable** (found & fixed during this eval, commit `a8c1aea`): the only submit button lived in a sticky summary card that scrolls out of range once you enter the tall delivery form — at y=−1098px. Real users could not complete a purchase by clicking. The in-form button now ships. | geometry probe in eval log |
| 4 | **Silent failures around checkout.** (a) First "Confirm & Checkout" click renders no visible guidance — the verification-code section appears only *after* a failed submit; (b) errors passed as bare strings (`withErrors($e->getMessage())`) have **no render path** — a post-OTP failure shows nothing; (c) the OTP is single-use and is **burned even when the failure is unrelated** to the code. | `07-checkout-stepup.png`, prod probes |

### 1.2 Friction (annoying but survivable)
- **No order-confirmation page.** After paying you land on order history with a toast; the email is the only real receipt.
- **Reviews vanish.** Submission succeeds, then the review is invisible (pending moderation) with no "awaiting approval" state shown on the product page.
- **Zero social proof anywhere.** Every product shows "No testimonials" — on a marketplace claiming "Editor's Choice".
- **Cart icon only appears for logged-in buyers**; guests get no bag indicator after adding (pre-login add bounces to login with no context kept message).
- **"Remember me" checkbox does nothing.**

### 1.3 Credibility details
- Contact page shows a **personal student email** (`m.luwi0049@uca.ca.ma`) as the business contact.
- Hero eyebrow reads **"Collection / 39"** — it's a live in-stock count (not stale, verified), but a bare number in the hero tells a shopper nothing.
- Editor's Choice and Latest Drop are both recency-driven — conceptually the same list twice.

---

## Part 2 — What makes it look AI-generated / template-made

This is the harder, more important question. Ranked by signal strength:

1. **The signup form offers "System Administrator" as a self-service account type.** Even though middleware gates it to `pending`, no real store ever shows this. It is the loudest possible "this is a scaffold" signal to any evaluator — technical or not.
2. **The logo says "SINCE 2026."** Brands only print an founding year once it lends credibility. On a 2026 project it reads as generated placeholder branding.
3. **LLM-pattern product naming across the whole catalog:** *Aura* Linen Set, *Elysian* Evening Gown, *Zenith* Studio Cam, *Nova* Mobile 12, *Vertex* Mechanical Keyboard — adjective + noun (+ number/vol.) repeated 39 times. No brand names, no suppliers, no model numbers.
4. **The same marketing sentence appears in three places:** "Curating exceptional products with a focus on quality, sustainability, and timeless design." (footer, meta description, about). Real brands write copy per surface.
5. **The default "premium e-commerce" aesthetic:** full-bleed stock face hero + "Beyond The Ordinary." + 3-column footer + glass nav + pill buttons + Inter/Jakarta font pairing. This is the exact configuration every LLM produces for "luxury storefront".
6. **The category set is the LLM default:** Electronics, Clothing, Home & Kitchen, Books, Beauty & Wellness, Sports & Outdoors, Toys & Games — the seven categories every generated marketplace gets.
7. **"Secure Checkout Powered by LUWI"** — a self-invented trust badge that references nobody.
8. **No human traces:** no team page, no workshop/story photos, no supplier names, no city, no returns signature, no social links that go anywhere real.

**What would fix the "AI smell" fastest:** remove admin from signup; drop "SINCE 2026"; rewrite the hero + footer copy to something *specific* (a city, a founder, a point of view); rename 10 products with realistic brand/model names; delete the fake trust badge.

---

## Part 3 — Production-level assessment (what genuinely works)

Verified live, not assumed:
- **Auth is production-grade:** email-OTP challenge for admin/partner works through real SMTP; status gates hold (suspended/pending blocked); API guarded.
- **Partner portal end-to-end:** order visibility scoped correctly, `paid → shipped` transition + buyer notification + audit event all fired on prod.
- **Admin console is complete:** member edit (role/status + partner sync), audit trail, outbound mail log, email templates/compose/history.
- **Performance work is visible:** same-origin images with immutable caching, card variants, lazy loading, no layout overflow on mobile.
- **Light/dark theming** incl. the new dual-logo works correctly in both modes.
- **Buyer order timeline** renders the new pending→paid→shipped→completed progress.

### Priority fix list (next session)
1. Realistic seed pricing (30 min, biggest ROI).
2. Remove `admin` from signup roles; make partner selection request-only copy.
3. Surface checkout errors: render bare `withErrors` strings as toasts; don't burn OTP on non-code failures.
4. Re-source the ~15 worst image mismatches.
5. Rewrite hero/footer/about copy + drop "SINCE 2026" + fake badge.
6. Order-confirmation page after successful checkout.
7. Seed moderated reviews on ~10 products.

---

*Test artifacts created on prod (safe to delete): users `buyer/partner/admin.evaluation@smartshop-luwi.tech` (ids 5/6/7), Partner "Evaluation Atelier", Order #2 (shipped), 1 email_center_log row.*
