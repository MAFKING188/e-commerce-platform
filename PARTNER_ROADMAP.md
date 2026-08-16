# Partner Ecosystem Functional Roadmap

## 1. Core Principles
- **Data Isolation:** Partners only see products they own and orders containing their products.
- **Identity Sovereignty:** Partner accounts must be manually approved by Admins.
- **Transparency:** Public catalog explicitly credits the Partner/Artisan for every product.

## 2. Functional Modules

### A. Partner Portal (The Dashboard)
- [x] **Performance Overview:** Real-time metrics on sales, inventory levels, and pending order items.
- [x] **Visual Analytics:** Time-series charts for revenue and fulfillment tracking.
- [x] **Financial Sovereignty:** Earnings tracking, pending balance visibility, and payout history.
- [x] **Inventory Management:**
    - [x] CRUD interface for Partner products (hardened with validation).
    - [x] Status tracking (In Stock, Low Stock, Out of Stock).
    - [x] Advanced Media Manager (Multi-image, Drag-and-drop reordering).
- [x] **Order Fulfillment:**
    - [x] View recent orders containing Partner items.
    - [x] Status update interface (e.g., "Ready for Shipment").

### B. Administrative Command Center
- [x] **Registry & Approval:** Admin portal to approve, suspend, or reactivate Partner accounts.
- [x] **Automated Outreach:** Instant notification on account status changes.
- [x] **Financial Command:** Disbursement management for partner payouts.
- [x] **Moderation:** Review moderation portal to curate feedback on Partner products.

### D. Profile & Trust Hardening (Pending)
- [ ] **Data Normalization:** Implement `profiles` table for user metadata.
- [ ] **Legal Compliance:** Add GDPR/CCPA consent timestamps (`terms_accepted_at`).
- [ ] **Partner KYC:** Secure storage for legal/tax identification documents.
- [ ] **Admin Accountability:** Implement system-wide Audit Log for sensitive administrative actions.

## 3. UI/UX Implementation Plan
- **Navigation:**
    - Admin: Consolidated command center.
    - Partner: Specific "My Partner Portal" link for authorized users.
- **Dashboard Aesthetics:** Consistent with SmartShop’s luxury minimalist design.

## 4. Security Rules
- **Middleware:** `PartnerMiddleware` restricts access to `/partner/*` routes.
- **Policies:** `ProductPolicy` and `OrderPolicy` enforce row-level security based on `partner_id`.
