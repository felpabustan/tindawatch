<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Store limits (current subscription tier)
    |--------------------------------------------------------------------------
    |
    | Owners can create up to this many stores on the base plan.
    | Higher tiers can raise this later without changing the data model.
    |
    */
    'max_stores_per_user' => (int) env('TINDAWATCH_MAX_STORES', 3),

    /*
    |--------------------------------------------------------------------------
    | E-wallet providers
    |--------------------------------------------------------------------------
    |
    | Stores may only add providers from this catalog (one of each).
    | To support another wallet later, append an entry here and drop a logo
    | under public/images/ewallet/. No schema change required.
    |
    */
    'ewallet_providers' => [
        [
            'name' => 'GCash',
            'slug' => 'gcash',
            'logo' => '/images/ewallet/gcash.svg',
        ],
        [
            'name' => 'Maya',
            'slug' => 'maya',
            'logo' => '/images/ewallet/maya.svg',
        ],
    ],
];
