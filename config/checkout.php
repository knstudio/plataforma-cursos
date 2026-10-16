<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Checkout page defaults
    |--------------------------------------------------------------------------
    |
    | Seller data, pixels and so on are edited in Admin > Configurações.
    | These values are only the fallback while nothing was saved there.
    |
    */

    'support_email' => env('CHECKOUT_SUPPORT_EMAIL', 'contato@cursodebaloes.net'),

    'company_name' => env('CHECKOUT_COMPANY_NAME', 'Juliana Balões'),

    'company_document' => env('CHECKOUT_COMPANY_DOCUMENT', '26.084.018/0001-02'),

    'default_banner' => 'assets/images/checkout/banner-padrao.jpg',

    'default_timer_minutes' => 15,

];
