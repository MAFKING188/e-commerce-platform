<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Platform business rules
    |--------------------------------------------------------------------------
    */
    'commission_rate' => env('SHOP_COMMISSION_RATE', 0.10),
    'low_stock_threshold' => env('SHOP_LOW_STOCK_THRESHOLD', 5),
    'currency_default' => 'USD',
    'contact_email' => env('CONTACT_RECIPIENT', 'mafuletil@gmail.com'),
];
