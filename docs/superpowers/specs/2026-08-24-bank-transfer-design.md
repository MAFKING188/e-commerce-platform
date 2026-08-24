# Bank Transfer Payment Method Design

## Overview
Add bank transfer as a second payment method alongside PayPal. **Single payment to platform** (platform bank account shown at checkout). Platform team validates proof; on approval, order becomes `paid` → existing payout flow distributes to vendors. Clear 24-hour SLA messaging throughout.

---

## Architecture

### Data Flow
```
Checkout → Select "Bank Transfer" → Show PLATFORM bank details → Create pending order
    → Upload proof screenshot → Order status: pending_payment
    → Platform team (admin) sees in dashboard → Reviews screenshot → Approve/Reject
    → Approve: order status → paid (triggers existing payout flow to vendors)
    → Reject: order status → pending_payment, notify user to re-upload
```

### Key Principles
- **Bank details**: Platform bank account displayed at checkout (configurable in .env)
- **Validation**: Platform team (admin) validates, NOT individual vendors
- **Rejection UX**: Order stays `pending_payment`, user notified to re-upload
- **24-hour SLA**: Prominent messaging at checkout, upload, and admin dashboard
- **Payouts**: Unchanged — existing payout flow runs after order becomes `paid`

---

## Database Changes

### 1. Payment Model - Add fields
```php
// Migration: add to payments table
$table->string('proof_path')->nullable();      // stored screenshot path
$table->timestamp('validated_at')->nullable();  // when admin approved/rejected
$table->unsignedBigInteger('validated_by')->nullable(); // admin user_id
$table->text('validation_notes')->nullable();   // optional admin notes
```

### 2. Order Status - Add new status
```php
// Order statuses: pending, pending_payment, paid, shipped, completed, cancelled
// 'pending_payment' = bank transfer uploaded, awaiting admin validation
```

### 3. Vendor Bank Details (SEPARATE - for payouts)
```php
// New table: vendor_bank_details
// - partner_id (FK)
// - iban, account_holder, bank_name, branch, swift_bic
// - is_primary (boolean)
// Vendor configures this in partner portal for receiving payouts
```

---

## Checkout Flow

### Step 1: Payment Method Selection
- Radio buttons: PayPal (default) | Bank Transfer
- If Bank Transfer selected:
  - Show **Platform** bank details card (from config/env)
  - Show **24-hour validation notice** prominently
  - Reference format: `ORDER-{order_id}-{random}`

### Step 2: Place Order
- Creates order with status `pending_payment`
- Creates payment record with `method=bank_transfer`, `status=pending`
- Redirects to "Upload Proof" page

### Step 3: Upload Proof
- Dedicated page: `/orders/{id}/upload-proof`
- Drag-drop / click to upload screenshot (image only, max 5MB)
- Shows preview, submit button
- On submit: payment.proof_path saved, order stays `pending_payment`
- Email sent to user: "Proof received, platform team will validate within 24 hours"

---

## Admin Dashboard (Platform Team)

### Admin Orders Index
- Add filter: `status=pending_payment` (new tab)
- New column: "Proof" (eye icon to view screenshot)
- Action buttons per order: **Approve** | **Reject**

### Admin Order Show
- If status `pending_payment`:
  - Show uploaded proof screenshot (large, modal on click)
  - Show "Validate Payment" card with:
    - Approve button (green)
    - Reject button (red) + required text area for reason
  - **24-hour SLA reminder** visible

### Approve Action
```
POST /admin/orders/{id}/validate-payment
{
  "action": "approve",
  "notes": "optional"
}
```
- Payment.status → paid, validated_at, validated_by
- Order.status → paid
- Email to user: "Payment approved, order confirmed"
- **Triggers existing payout flow** → vendors paid per existing logic

### Reject Action
```
POST /admin/orders/{id}/validate-payment
{
  "action": "reject",
  "reason": "required"
}
```
- Payment.status → rejected, validation_notes = reason
- Order.status → pending_payment (unchanged)
- Email to user: "Payment proof rejected: {reason}. Please upload new proof."
- User can re-upload on `/orders/{id}/upload-proof`

---

## Email Notifications

### 1. User: Proof Received
- Subject: "Payment proof received for Order #{id}"
- Body: "We've received your proof. Our team will validate within 24 hours."

### 2. User: Payment Approved
- Subject: "Payment approved — Order #{id} confirmed"
- Body: "Your payment has been validated. Vendor will ship soon."

### 3. User: Payment Rejected
- Subject: "Action needed: Payment proof rejected for Order #{id}"
- Body: "Reason: {reason}. Please upload a new proof at [link]."

### 4. Admin: New Proof Uploaded
- Subject: "New bank transfer proof for Order #{id}"
- Body: "Customer uploaded proof. Please validate within 24 hours."

---

## Vendor Payouts (UNCHANGED)
- After order becomes `paid`, existing `Payout` logic runs
- Vendors receive their share via existing payout flow
- Vendor bank details configured separately in partner portal (vendor_bank_details table)

---

## Frontend UX Details

### Checkout Page
```
┌─────────────────────────────────────┐
│  Payment Method                     │
│  ○ PayPal (recommended)             │
│  ● Bank Transfer                    │
│                                     │
│  ┌─ Platform Bank Details ────────┐  │
│  │ IBAN: MA64 1234 5678 9012...   │  │
│  │ Account: SmartShop Platform     │  │
│  │ Bank: Attijariwafa Bank         │  │
│  │ Reference: ORDER-1234-ABC      │  │
│  └────────────────────────────────┘  │
│                                     │
│  ⚠️ Bank transfers take up to 24    │
│     hours to validate. Your order   │
│     will be confirmed once our      │
│     team verifies your proof.       │
└─────────────────────────────────────┘
```

### Upload Proof Page
- Clean dropzone with preview
- "Upload Proof" button (disabled until file selected)
- Shows order summary + platform bank details for reference

### Admin Order Card (pending_payment)
```
Order #1234  |  $299.00  |  pending_payment  |  Proof 👁  |  [Approve] [Reject]
```

---

## Files to Modify/Create

| File | Change |
|------|--------|
| `database/migrations/xxx_add_bank_transfer_fields_to_payments.php` | New migration |
| `database/migrations/xxx_create_vendor_bank_details_table.php` | **New** vendor payout bank details |
| `Modules/MarketplacePipeline/app/Models/Payment.php` | Add fillable fields |
| `Modules/MarketplacePipeline/app/Models/Order.php` | Add `pending_payment` status |
| `Modules/PartnerHub/app/Models/Partner.php` | Add `bankDetails()` relationship |
| `Modules/MarketplacePipeline/app/Http/Controllers/PaymentController.php` | Add `bankTransfer()` method |
| `Modules/MarketplacePipeline/app/Http/Controllers/AdminOrderController.php` | Add `validatePayment()` method |
| `Modules/MarketplacePipeline/routes/web.php` | New routes |
| `Modules/MarketplacePipeline/resources/views/checkout.blade.php` | Payment method selector + platform bank details |
| `Modules/MarketplacePipeline/resources/views/payments/upload-proof.blade.php` | **New** upload page |
| `Modules/MarketplacePipeline/resources/views/admin/orders/index.blade.php` | Add pending_payment filter |
| `Modules/MarketplacePipeline/resources/views/admin/orders/show.blade.php` | Add validation UI |
| `Modules/PartnerHub/resources/views/partner/profile/edit.blade.php` | **New** vendor bank details form |
| `Modules/MarketplacePipeline/Mail/PaymentProofReceived.php` | **New** mail class |
| `Modules/MarketplacePipeline/Mail/PaymentValidated.php` | **New** mail class |
| `Modules/MarketplacePipeline/Mail/PaymentRejected.php` | **New** mail class |

---

## Routes

```php
// Customer
GET  /checkout                     // existing - add payment method radio
POST /bank-transfer/store          // create order with bank_transfer method
GET  /orders/{order}/upload-proof  // upload proof page
POST /orders/{order}/upload-proof  // handle upload

// Admin (platform team)
GET  /admin/orders                 // existing - add pending_payment filter
POST /admin/orders/{id}/validate-payment // approve/reject

// Vendor (for payout bank details)
GET  /partner/profile/bank-details // view/edit bank details
POST /partner/profile/bank-details // save bank details
```

---

## 24-Hour SLA Messaging Locations

1. **Checkout** (below platform bank details): "Validation within 24 hours"
2. **Upload Proof page**: "Our team will review within 24 hours"
3. **Admin dashboard** (pending_payment tab): "Validate within 24 hours"
4. **Email to user**: "Typically validated within 24 hours"
5. **Email to admin**: "Please validate within 24 hours"

---

## Success Criteria

- [ ] User can select Bank Transfer at checkout
- [ ] Platform bank details displayed clearly with reference format
- [ ] Order created with `pending_payment` status
- [ ] User can upload proof screenshot
- [ ] Admin sees pending_payment orders in dashboard
- [ ] Admin can view proof screenshot
- [ ] Admin can Approve → order becomes `paid` → payouts triggered
- [ ] Admin can Reject → user notified to re-upload
- [ ] All 4 email notifications work
- [ ] 24-hour messaging visible at all touchpoints
- [ ] Vendor bank details page in partner portal (for payouts)
- [ ] All existing tests pass + new tests for bank transfer flow