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
    |
    | schedule: daily, twice_daily, hourly, every_thirty_minutes,
    |           every_fifteen_minutes, every_five_minutes
    |
    */

    'sync' => [
        'page_size' => (int) env('PRODUCT_REVIEWS_PAGE_SIZE', 100),
        'schedule' => env('PRODUCT_REVIEWS_SCHEDULE', 'daily'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Yotpo
    |--------------------------------------------------------------------------
    |
    | source:
    |   auto — Core API first; fall back to CDN widget API on timeout/failure
    |   core — Core API only
    |   cdn  — CDN widget API only (needs product_ids and/or existing reviews)
    |
    | product_ids: comma-separated storefront product IDs / SKUs used by the
    | CDN path (same values you pass to {{ product_reviews product_id="..." }}).
    |
    */

    'yotpo' => [
        'app_key' => env('PRODUCT_REVIEWS_YOTPO_APP_KEY'),
        'secret' => env('PRODUCT_REVIEWS_YOTPO_SECRET'),
        'base_url' => env('PRODUCT_REVIEWS_YOTPO_BASE_URL', 'https://api.yotpo.com'),
        'cdn_base_url' => env('PRODUCT_REVIEWS_YOTPO_CDN_BASE_URL', 'https://api-cdn.yotpo.com'),
        'source' => env('PRODUCT_REVIEWS_YOTPO_SOURCE', 'auto'),
        'product_ids' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('PRODUCT_REVIEWS_YOTPO_PRODUCT_IDS', ''))
        ))),
        'include_site_reviews' => (bool) env('PRODUCT_REVIEWS_YOTPO_INCLUDE_SITE_REVIEWS', true),
        'token_cache_ttl' => (int) env('PRODUCT_REVIEWS_YOTPO_TOKEN_TTL', 3500),
        'timeout' => (int) env('PRODUCT_REVIEWS_YOTPO_TIMEOUT', 60),
        'connect_timeout' => (int) env('PRODUCT_REVIEWS_YOTPO_CONNECT_TIMEOUT', 15),
    ],

];
