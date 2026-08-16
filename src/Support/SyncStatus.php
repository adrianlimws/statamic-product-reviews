<?php

namespace Brainjuredstudio\ProductReviews\Support;

use Illuminate\Support\Facades\Cache;

class SyncStatus
{
    public const CACHE_KEY = 'product-reviews.sync.status';

    public static function remember(array $status): void
    {
        Cache::forever(self::CACHE_KEY, array_merge([
            'synced_at' => now()->toIso8601String(),
        ], $status));
    }

    public static function get(): array
    {
        return Cache::get(self::CACHE_KEY, [
            'synced_at' => null,
            'success' => null,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'unpublished' => 0,
            'error' => null,
            'provider' => null,
        ]);
    }
}
