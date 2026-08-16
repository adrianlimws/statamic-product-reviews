<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Active Provider
    |--------------------------------------------------------------------------
    |
    | Only Yotpo is implemented in v1. The config key exists so additional
    | providers can be added without changing the sync pipeline.
    |
    */

    'provider' => env('PRODUCT_REVIEWS_PROVIDER', 'yotpo'),

    /*
    |--------------------------------------------------------------------------
    | Collection
    |--------------------------------------------------------------------------
    */

    'collection' => 'product_reviews',

    /*
    |--------------------------------------------------------------------------
    | Sync
    |--------------------------------------------------------------------------
    */

    'sync' => [
        'page_size' => (int) env('PRODUCT_REVIEWS_PAGE_SIZE', 100),
        'schedule' => env('PRODUCT_REVIEWS_SCHEDULE', 'daily'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Yotpo
    |--------------------------------------------------------------------------
    */

    'yotpo' => [
        'app_key' => env('PRODUCT_REVIEWS_YOTPO_APP_KEY'),
        'secret' => env('PRODUCT_REVIEWS_YOTPO_SECRET'),
        'base_url' => env('PRODUCT_REVIEWS_YOTPO_BASE_URL', 'https://api.yotpo.com'),
        'token_cache_ttl' => (int) env('PRODUCT_REVIEWS_YOTPO_TOKEN_TTL', 3500),
        'timeout' => (int) env('PRODUCT_REVIEWS_YOTPO_TIMEOUT', 60),
        'connect_timeout' => (int) env('PRODUCT_REVIEWS_YOTPO_CONNECT_TIMEOUT', 15),
    ],

];
