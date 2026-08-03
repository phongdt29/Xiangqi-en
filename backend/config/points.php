<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Point top-up packages
    |--------------------------------------------------------------------------
    |
    | Fixed price/point tiers for the PayPal top-up flow, at a flat 10 points
    | per USD. Prices are server-authoritative: the client only ever sends a
    | package key, never an amount, so a tampered request can't buy points
    | below the listed price.
    |
    */

    'packages' => [
        'basic' => ['usd' => 1.00, 'points' => 10],
        'plus' => ['usd' => 5.00, 'points' => 50],
        'pro' => ['usd' => 10.00, 'points' => 100],
    ],

    /*
    |--------------------------------------------------------------------------
    | Point withdrawal (cash-out) settings
    |--------------------------------------------------------------------------
    |
    | Same flat rate as the top-up packages (no bonus tiers exist to arbitrage
    | between). `minimum` keeps single payouts above PayPal's per-payout fee
    | overhead - kept at roughly the same $5 floor as before.
    |
    */

    'withdraw_rate' => 10, // points per 1 USD
    'withdraw_minimum' => 50, // points (= $5)

    /*
    |--------------------------------------------------------------------------
    | Minimum room stake
    |--------------------------------------------------------------------------
    |
    | A room's stake is either 0 (no stake) or at least this many points -
    | never a token amount like 1-14 that isn't worth the escrow/payout
    | machinery around it.
    |
    */

    'min_stake' => 150,

];
