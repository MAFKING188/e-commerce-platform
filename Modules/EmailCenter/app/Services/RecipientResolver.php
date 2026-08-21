<?php

namespace Modules\EmailCenter\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\IdentityAccess\Models\User;
use Modules\MarketplacePipeline\Models\Order;
use Modules\PartnerHub\Models\Partner;

class RecipientResolver
{
    public static function resolveForAdmin(?string $group, ?array $userIds = null, bool $newsletterOnly = false): Collection
    {
        $query = User::where('status', 'active')->whereNotNull('email_verified_at');

        if ($userIds) {
            $query->whereIn('id', $userIds);
        } else {
            switch ($group) {
                case 'all':
                    break;
                case 'admins':
                    $query->where('role', 'admin');
                    break;
                case 'partners':
                    $query->where('role', 'partner');
                    break;
                case 'members':
                    $query->where('role', 'user');
                    break;
                case 'newsletter':
                    $query->where('newsletter_optin', true);
                    break;
            }
        }

        $recipients = $query->get();

        if ($newsletterOnly) {
            $recipients = $recipients->filter(fn(User $u) => $u->newsletter_optin);
        }

        return $recipients;
    }

    public static function resolveForPartner(int $partnerUserId): Collection
    {
        $partner = Partner::where('user_id', $partnerUserId)->first();

        if (! $partner) {
            return new Collection();
        }

        $buyers = Order::whereHas('items.product.partners', function ($q) use ($partner) {
                $q->where('partners.id', $partner->id);
            })
            ->whereHas('user', function ($q) {
                $q->where('status', 'active')->whereNotNull('email_verified_at');
            })
            ->with('user')
            ->get()
            ->pluck('user')
            ->unique('id')
            ->values();

        return new Collection($buyers);
    }

    public static function replacePlaceholders(string $text, User $user): string
    {
        return str_replace(
            ['{name}', '{email}'],
            [$user->name, $user->email],
            $text
        );
    }
}