# Bank Transfer Payment Method Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

## Goal
Add bank transfer as a payment method alongside PayPal. Users upload proof-of-payment screenshot; platform team (admin) validates within 24 hours; on approval, order becomes `paid` → existing payout flow triggers.

## Architecture
1. Checkout: User selects Bank Transfer → shows platform bank details (from .env config) → creates order with `pending_payment` status
2. User uploads proof screenshot → order stays `pending_payment`, admin notified
3. Admin reviews proof in dashboard → Approves (order → `paid`) or Rejects (user re-uploads)
4. On approval: existing payout flow distributes to vendors
5. All touchpoints show 24-hour SLA messaging

## Tech Stack
Laravel 11, Eloquent ORM, existing Mail system, existing Payout flow

## Global Constraints (from spec)
- Bank details displayed on checkout page (config/env, no DB storage)
- Order statuses: pending, pending_payment, paid, shipped, completed, cancelled
- 24-hour SLA messaging at 5 touchpoints
- Per rejection: order stays pending_payment, user notified to re-upload
- Vendor bank details in separate table (for payouts only)
- Platform team (admin) validates, NOT individual vendors
- Existing PayPal code remains unchanged
- 167 tests / 1121 assertions must continue passing

---