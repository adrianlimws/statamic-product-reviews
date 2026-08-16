<?php

namespace Brainjuredstudio\ProductReviews\Yotpo;

use Illuminate\Http\Client\ConnectionException;
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
        protected ?int $timeout = null,
        protected ?int $connectTimeout = null,
    ) {
        $this->appKey ??= config('product-reviews.yotpo.app_key');
        $this->secret ??= config('product-reviews.yotpo.secret');
        $this->baseUrl ??= rtrim(config('product-reviews.yotpo.base_url', 'https://api.yotpo.com'), '/');
        $this->tokenTtl ??= (int) config('product-reviews.yotpo.token_cache_ttl', 3500);
        $this->timeout ??= (int) config('product-reviews.yotpo.timeout', 60);
        $this->connectTimeout ??= (int) config('product-reviews.yotpo.connect_timeout', 15);
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
        try {
            $response = $this->http()
                ->get("{$this->baseUrl}/v1/apps/{$this->appKey}/reviews", [
                    'utoken' => $this->token(),
                    'page' => $page,
                    'count' => min($count, 100),
                ]);
        } catch (ConnectionException $e) {
            throw new RuntimeException(
                'Yotpo reviews request timed out or could not connect. Try again, or increase PRODUCT_REVIEWS_YOTPO_TIMEOUT. '.$this->sanitizeMessage($e->getMessage()),
                previous: $e,
            );
        }

        if ($response->failed()) {
            throw new RuntimeException($this->formatFailedResponse('Yotpo reviews request failed', $response));
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
            try {
                $response = $this->http()->post("{$this->baseUrl}/oauth/token", [
                    'client_id' => $this->appKey,
                    'client_secret' => $this->secret,
                    'grant_type' => 'client_credentials',
                ]);
            } catch (ConnectionException $e) {
                throw new RuntimeException(
                    'Yotpo authentication timed out or could not connect. '.$this->sanitizeMessage($e->getMessage()),
                    previous: $e,
                );
            }

            if ($response->failed()) {
                throw new RuntimeException($this->formatFailedResponse('Yotpo authentication failed', $response));
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
        return Http::acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->connectTimeout($this->connectTimeout)
            ->retry(2, 500, function ($exception) {
                return $exception instanceof ConnectionException;
            }, throw: false);
    }

    protected function formatFailedResponse(string $prefix, $response): string
    {
        $status = $response->status();
        $body = trim((string) $response->body());

        if ($status === 504 || str_contains(strtolower($body), 'gateway time-out') || str_contains(strtolower($body), 'gateway timeout')) {
            return "{$prefix}: Yotpo returned a 504 Gateway Timeout. Their API is slow or overloaded — wait a moment and try Sync Now again.";
        }

        if ($status === 502 || $status === 503) {
            return "{$prefix}: Yotpo returned HTTP {$status}. Try again shortly.";
        }

        if ($body === '' || str_starts_with($body, '<') || str_contains(strtolower($body), '<html')) {
            return "{$prefix}: Yotpo returned HTTP {$status} with an empty or HTML error page. Try again shortly.";
        }

        return $this->sanitizeMessage("{$prefix}: ".$body);
    }

    protected function sanitizeMessage(string $message): string
    {
        $message = preg_replace('/([?&]utoken=)[^&\s]+/i', '$1[redacted]', $message) ?? $message;

        if (filled($this->appKey)) {
            $message = str_replace((string) $this->appKey, '[redacted]', $message);
        }

        return $message;
    }
}
