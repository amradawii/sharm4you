<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Locale → Currency Mapping
    |--------------------------------------------------------------------------
    |
    | Each public locale shows packages priced in its preferred currency.
    |
    */

    'locale_map' => [
        'en' => 'USD',
        'ru' => 'RUB',
        'it' => 'EUR',
        'ro' => 'EUR',
        'bg' => 'EUR',
        'tr' => 'USD',
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency Display Metadata
    |--------------------------------------------------------------------------
    */

    'symbols' => [
        'USD' => ['symbol' => '$',  'label' => 'US Dollar',     'position' => 'after', 'locale' => 'en', 'locale_label' => 'English / Turkish site'],
        'EUR' => ['symbol' => '€',  'label' => 'Euro',          'position' => 'after', 'locale' => 'it', 'locale_label' => 'Italian / Romanian / Bulgarian site'],
        'RUB' => ['symbol' => '₽',  'label' => 'Russian Ruble', 'position' => 'after', 'locale' => 'ru', 'locale_label' => 'Russian site'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Booking Payment Currencies
    |--------------------------------------------------------------------------
    |
    | Currencies the admin can record money in when logging an ad-hoc booking.
    | Wider than the package currencies above (cash is often taken in EGP).
    |
    */

    'booking' => [
        'USD' => ['symbol' => '$',  'label' => 'US Dollar'],
        'EUR' => ['symbol' => '€',  'label' => 'Euro'],
        'EGP' => ['symbol' => 'E£', 'label' => 'Egyptian Pound'],
        'RUB' => ['symbol' => '₽',  'label' => 'Russian Ruble'],
    ],

];
