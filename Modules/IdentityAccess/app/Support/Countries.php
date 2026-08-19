<?php

namespace Modules\IdentityAccess\Support;

class Countries
{
    public static function all(): array
    {
        return [
            'MA' => 'Morocco',
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'FR' => 'France',
            'IT' => 'Italy',
            'ES' => 'Spain',
            'DE' => 'Germany',
            'BE' => 'Belgium',
            'PT' => 'Portugal',
            'NL' => 'Netherlands',
            'CH' => 'Switzerland',
            'AE' => 'United Arab Emirates',
            'SA' => 'Saudi Arabia',
            'QA' => 'Qatar',
            'CA' => 'Canada',
            'AU' => 'Australia',
            'JP' => 'Japan',
            'CN' => 'China',
            'BR' => 'Brazil',
            'TR' => 'Turkey',
            'EG' => 'Egypt',
            'SN' => 'Senegal',
            'CI' => "Côte d'Ivoire",
        ];
    }

    public static function name(string $code): ?string
    {
        return self::all()[strtoupper($code)] ?? null;
    }
}
