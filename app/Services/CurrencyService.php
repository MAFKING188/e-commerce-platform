<?php

namespace App\Services;

class CurrencyService
{
    public static function convert($amount)
    {
        $currency = session('currency', config('currency.default'));
        $rate = config("currency.supported.{$currency}.rate", 1.0);
        
        return $amount * $rate;
    }

    public static function format($amount)
    {
        $currency = session('currency', config('currency.default'));
        $symbol = config("currency.supported.{$currency}.symbol", '$');
        $converted = self::convert($amount);

        return $symbol . number_format($converted, 2);
    }

    public static function getCurrent()
    {
        return session('currency', config('currency.default'));
    }
}
