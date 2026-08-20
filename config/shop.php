<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Platform business rules
    |--------------------------------------------------------------------------
    */
    'commission_rate' => env('SHOP_COMMISSION_RATE', 0.10),
    'currency_default' => 'USD',
    'contact_email' => env('CONTACT_RECIPIENT', 'mafuletil@gmail.com'),
];
