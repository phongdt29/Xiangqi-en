<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Country-based API access control
    |--------------------------------------------------------------------------
    |
    | When enabled, requests to /api/* are geo-located and rejected if their
    | country is in `blocked_countries`, unless the request IP is explicitly
    | whitelisted. Off by default so local dev/testing is never affected.
    |
    */

    'enabled' => env('GEOBLOCK_ENABLED', false),

    'blocked_countries' => ['VN'],

    'whitelist_ips' => array_filter(array_map('trim', explode(',', env('GEOBLOCK_WHITELIST_IPS', '')))),

];
