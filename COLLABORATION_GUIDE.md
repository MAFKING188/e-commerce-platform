# SmartShop: Pilot-Navigator Collaborative Protocol

This document defines the working relationship between the Senior Developer (AI) and the Junior Developer (User) for the SmartShop Project.

## 1. The Pilot-Navigator Roles

| Phase | Navigator (Senior AI) | Pilot (Junior Developer) |
| :--- | :--- | :--- |
| **1. Strategic Plan** | Provides a technical spec, DB schema changes, and security edge cases. | Reviews the spec, asks clarifying questions, and approves the approach. |
| **2. Heavy Lifting** | Monitors progress and provides specific logic "snippets" for complex math/algorithms. | **Writes the code.** Generates migrations, creates controllers, and builds Blade views. |
| **3. Code Review** | Audits the implementation for N+1 queries, race conditions, and clean code standards. | Explains the implementation choices and performs "Surgical Edits" based on feedback. |
| **4. Hardening** | Proposes test cases and provides shell commands for validation. | Runs tests, verifies UI responsiveness, and documents the result in `PROGRESS.md`. |

---

## 2. Senior Developer Audit Report (June 2026)

### A. Critical Technical Debt
- **The Financial Leak:** Payout logic pays 100% (minus commission) to *every* partner linked to a product. Multi-partner products result in immediate financial loss.
- **Mass Assignment Vulnerability:** `Product` and other models lack `$fillable` protection, relying solely on Request validation.
- **Hardcoded Business Rules:** Commissions and currency rates are hardcoded in controllers.

### B. Production Readiness Gaps
- **Storage:** Local file storage is not scalable; needs S3/Cloudinary.
- **Security:** Missing rate-limiting on sensitive routes (Login/Checkout).
- **Observability:** No logging for asynchronous failures (Mails, Payments).

---

## 3. High-Priority Task List

1. **[ ] Fix Payout Logic:** Refactor `partner_products` pivot to support revenue sharing and update the settlement engine.
2. **[ ] Model Hardening:** Implement strict `$fillable` arrays and relationship constraints across all core models.
3. **[ ] Config Decentralization:** Move business constants to `config/shop.php`.
4. **[ ] GDPR & Identity Layer:** Implement legal compliance and metadata handling via JSON columns or Profiles.

---

## 4. Operational Commands
- Use `composer test` to verify changes.
- Use `npm run build` for UI updates.
- Update `PROGRESS.md` after every session milestone.
