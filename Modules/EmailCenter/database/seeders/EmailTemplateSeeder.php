<?php

namespace Modules\EmailCenter\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\EmailCenter\Models\EmailTemplate;

class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Newsletter — LUWI Digest',
                'subject' => 'LUWI Digest: This Week\'s Curated Finds',
                'body_markdown' => <<<'MD'
# LUWI Weekly Digest

Hello {name},

Welcome to this week's curated selection of exceptional products from our artisan partners.

---

## New Arrivals

Discover the latest additions to the LUWI Collection — hand-picked for quality, sustainability, and timeless design.

## Featured Partner

This week we spotlight one of our talented artisans and their craft.

## Editor's Choice

Our team's personal favorite from the collection.

---

Thank you for being part of the LUWI community.

Regards,<br>
The SmartShop Team
MD,
            ],
            [
                'name' => 'Notice — Order Update',
                'subject' => 'Update on Your Order #{order_id}',
                'body_markdown' => <<<'MD'
# Order Update

Hello {name},

We wanted to keep you informed about your recent order.

**Status:** {status}
**Details:** {details}

You can view the full order details in your account.

Regards,<br>
The SmartShop Team
MD,
            ],
            [
                'name' => 'Notice — General Announcement',
                'subject' => 'Important Update from SmartShop',
                'body_markdown' => <<<'MD'
# Important Announcement

Hello {name},

We have an important update to share with you.

{message}

If you have any questions, please don't hesitate to reach out to our support team.

Regards,<br>
The SmartShop Team
MD,
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::updateOrCreate(
                ['name' => $template['name']],
                $template
            );
        }
    }
}