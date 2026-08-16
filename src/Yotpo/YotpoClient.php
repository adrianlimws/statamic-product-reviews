<?php

namespace Brainjuredstudio\ProductReviews\Yotpo;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class YotpoClient
{
    public function __construct(
        protected ?string $appKey = null,
        protected ?string $secret = null,
        protected ?string $baseUrl = null,
        protected ?int $tokenTtl = null,
    ) {
        $this->appKey ??= config('product-reviews.yotpo.app_key');
        $this->secret ??= config('product-reviews.yotpo.secret');
        $this->baseUrl ??= rtrim(config('product-reviews.yotpo.base_url', 'https://api.yotpo.com'), '/');
        $this->tokenTtl ??= (int) config('product-reviews.yotpo.token_cache_ttl', 3500);
    }

    public function isConfigured(): bool
    {
        return filled($this->appKey) && filled($this->secret);
    }

    public function appKey(): string
    {
        return (string) $this->appKey;
    }

    public function fetchReviewsPage(int $page = 1, int $count = 100): array
    {
        $response = $this->http()
            ->get("{$this->baseUrl}/v1/apps/{$this->appKey}/reviews", [
                'utoken' => $this->token(),
                'page' => $page,
                'count' => $count,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('Yotpo reviews request failed: '.$response->body());
        }

        return $response->json() ?? [];
    }

    public function token(): string
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Yotpo credentials are not configured.');
        }

        $cacheKey = 'product-reviews.yotpo.utoken.'.md5($this->appKey);

        return Cache::remember($cacheKey, $this->tokenTtl, function () {
            $response = Http::asJson()->post("{$this->baseUrl}/oauth/token", [
                'client_id' => $this->appKey,
                'client_secret' => $this->secret,
                'grant_type' => 'client_credentials',
            ]);

            if ($response->failed()) {
                throw new RuntimeException('Yotpo authentication failed: '.$response->body());
            }

            $token = $response->json('access_token') ?? $response->json('utoken');

            if (! filled($token)) {
                throw new RuntimeException('Yotpo authentication response did not include an access token.');
            }

            return $token;
        });
    }

    public function clearTokenCache(): void
    {
        Cache::forget('product-reviews.yotpo.utoken.'.md5((string) $this->appKey));
    }

    protected function http(): PendingRequest
    {
        return Http::acceptJson()->asJson();
    }
}
