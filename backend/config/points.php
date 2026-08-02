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

    /*
    |--------------------------------------------------------------------------
    | Point withdrawal (cash-out) settings
    |--------------------------------------------------------------------------
    |
    | Always converted at the "basic" package's base rate (not the bonus
    | rates of the bigger packages) - otherwise buying a bonus-heavy package
    | and immediately withdrawing would let a user extract more cash than
    | they put in. `minimum` keeps single payouts above PayPal's per-payout
    | fee overhead.
    |
    */

    'withdraw_rate' => 100, // points per 1 USD
    'withdraw_minimum' => 500, // points (= $5)

];
