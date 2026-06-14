<p align="center">
    <h1 align="center">SmartShop | Premium E-Commerce Ecosystem</h1>
</p>

SmartShop is a high-fidelity, production-grade e-commerce platform designed for luxury artisans and curators. It features a sophisticated multi-role architecture, automated financial settlements, and a data-driven intelligence suite.

## 🚀 Core Features

### 1. Autonomous Partner Ecosystem
*   **Artisan Portals:** Dedicated dashboards for Partners to manage their specific inventory and fulfillments.
*   **Inventory Sovereignty:** Full CRUD control for portfolio pieces with strictly validated form requests.
*   **Logistics Isolation:** Order fulfillment views that dynamically filter items based on the authenticated Partner.

### 2. Financial Command & Transparency
*   **Automated Payouts:** Intelligent settlement engine that calculates earnings and 10% platform commissions upon order completion.
*   **Financial Registry:** Transparent tracking of processed earnings and pending balances for both Admins and Partners.
*   **Transaction Auditing:** Reference-based payout processing to maintain clear financial trails.

### 3. Intelligence & Analytics
*   **Visual Sales Intelligence:** Interactive 30-day time-series charts (powered by Chart.js) for revenue tracking.
*   **Operational Pulse:** Real-time metrics on inventory levels, active orders, and member registrations.

### 4. Global Sovereignty
*   **Multi-Currency Support:** Seamless session-based valuation supporting USD, EUR, GBP, and MAD.
*   **Localized Pricing:** Custom `@money` Blade directive for unified, high-fidelity price formatting.
*   **International Registry:** Premium currency toggle integrated into the global navigation.

### 5. Curatorial Media Management
*   **Narrative Storytelling:** Support for multiple high-resolution images per product to build visual narratives.
*   **Interactive Choreography:** Drag-and-drop image reordering using SortableJS for surgical control over product galleries.

### 6. Administrative Command Center
*   **Community Moderation:** Robust portal for curating and moderating user feedback/reviews.
*   **Member Registry:** Multi-tier confirmation workflow for Partner and Admin registrations.
*   **Automated Outreach:** Event-driven email notifications for account status updates.

## 🛠️ Tech Stack
*   **Framework:** Laravel (PHP 8.3+)
*   **Security:** Laravel Sanctum & Role-Based Access Control (RBAC)
*   **Analytics:** Chart.js
*   **Interactivity:** SortableJS, Vanilla JS, AJAX
*   **Database:** MySQL

## 🔧 Installation

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

---
**Status:** SmartShop is currently in its high-maturity phase, fully equipped for international expansion and autonomous marketplace operations.
