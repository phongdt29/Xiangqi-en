<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Point top-up packages
    |--------------------------------------------------------------------------
    |
    | Fixed price/point tiers for the PayPal top-up flow. Prices are server
    | -authoritative: the client only ever sends a package key, never an
    | amount, so a tampered request can't buy points below the listed price.
    |
    */

    'packages' => [
        'basic' => ['usd' => 1.00, 'points' => 100],
        'plus' => ['usd' => 5.00, 'points' => 550],
        'pro' => ['usd' => 10.00, 'points' => 1200],
    ],

];
