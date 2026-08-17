<?php

namespace Brainjuredstudio\ProductReviews\Providers;

use Brainjuredstudio\ProductReviews\Contracts\ReviewProvider;
use Brainjuredstudio\ProductReviews\Data\ReviewData;
use Brainjuredstudio\ProductReviews\Yotpo\YotpoClient;
use Generator;
use Illuminate\Support\Facades\Log;
use Statamic\Facades\Entry;

class YotpoProvider implements ReviewProvider
{
    public function __construct(
        protected YotpoClient $client,
    ) {
    }

    public function key(): string
    {
        return 'yotpo';
    }

    public function isConfigured(): bool
    {
        return $this->client->isConfigured();
    }

    public function fetchReviews(): Generator
    {
        $source = strtolower((string) config('product-reviews.yotpo.source', 'auto'));

        if ($source === 'cdn') {
            yield from $this->fetchFromCdn();

            return;
        }

        try {
            yield from $this->fetchFromCore();
        } catch (\Throwable $e) {
            if ($source === 'core' || $this->shouldNotFallBackToCdn($e)) {
                throw $e;
            }

            Log::warning('Product Reviews: Core Yotpo API failed; falling back to CDN widget API.', [
                'message' => $e->getMessage(),
            ]);

            yield from $this->fetchFromCdn();
        }
    }

    protected function shouldNotFallBackToCdn(\Throwable $e): bool
    {
        $message = strtolower($e->getMessage());

        return str_contains($message, 'authentication failed')
            || str_contains($message, 'credentials are not configured')
            || str_contains($message, 'did not include an access token');
    }

    protected function fetchFromCore(): Generator
    {
        $page = 1;
        $pageSize = (int) config('product-reviews.sync.page_size', 100);
        $seen = [];

        do {
            $payload = $this->client->fetchReviewsPage($page, $pageSize);
            $reviews = data_get($payload, 'response.reviews', data_get($payload, 'reviews', []));

            if (! is_array($reviews) || count($reviews) === 0) {
                break;
            }

            foreach ($reviews as $review) {
                if (! is_array($review)) {
                    continue;
                }

                $id = (string) data_get($review, 'id', '');
                if ($id !== '' && isset($seen[$id])) {
                    continue;
                }
                if ($id !== '') {
                    $seen[$id] = true;
                }

                yield $this->mapReview($review);
            }

            $page++;
        } while (count($reviews) >= $pageSize);
    }

    protected function fetchFromCdn(): Generator
    {
        $pageSize = min((int) config('product-reviews.sync.page_size', 100), 150);
        $seen = [];

        foreach ($this->cdnProductIds() as $productId) {
            $page = 1;

            do {
                $payload = $this->client->fetchWidgetReviewsPage($productId, $page, $pageSize);
                $reviews = data_get($payload, 'response.reviews', data_get($payload, 'reviews', []));
                $domainKey = data_get($payload, 'response.products.0.domain_key');

                if (! is_array($reviews) || count($reviews) === 0) {
                    break;
                }

                foreach ($reviews as $review) {
                    if (! is_array($review)) {
                        continue;
                    }

                    $id = (string) data_get($review, 'id', '');
                    if ($id !== '' && isset($seen[$id])) {
                        continue;
                    }
                    if ($id !== '') {
                        $seen[$id] = true;
                    }

                    $fallbackProductId = $productId === 'yotpo_site_reviews'
                        ? null
                        : ($domainKey ?: $productId);

                    yield $this->mapReview($review, $fallbackProductId);
                }

                $page++;
                $total = (int) data_get($payload, 'response.pagination.total', 0);
                $perPage = (int) data_get($payload, 'response.pagination.per_page', $pageSize);
            } while ($perPage > 0 && (($page - 1) * $perPage) < $total && count($reviews) >= $perPage);
        }
    }

    /**
     * @return list<string>
     */
    protected function cdnProductIds(): array
    {
        $ids = [];

        if (config('product-reviews.yotpo.include_site_reviews', true)) {
            $ids[] = 'yotpo_site_reviews';
        }

        $configured = config('product-reviews.yotpo.product_ids', []);
        if (is_string($configured)) {
            $configured = array_filter(array_map('trim', explode(',', $configured)));
        }

        foreach ((array) $configured as $id) {
            $id = trim((string) $id);
            if ($id !== '') {
                $ids[] = $id;
            }
        }

        $collection = config('product-reviews.collection', 'product_reviews');
        $existing = Entry::query()
            ->where('collection', $collection)
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($existing as $id) {
            $id = trim((string) $id);
            if ($id !== '' && $id !== 'yotpo_site_reviews') {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }

    protected function mapReview(array $review, ?string $fallbackProductId = null): ReviewData
    {
        $author = data_get($review, 'user.display_name')
            ?? data_get($review, 'name')
            ?? data_get($review, 'display_name');

        $productId = data_get($review, 'sku')
            ?? data_get($review, 'external_product_id')
            ?? data_get($review, 'product.domain_key')
            ?? data_get($review, 'domain_key')
            ?? $fallbackProductId
            ?? data_get($review, 'product_id');

        // Prefer storefront product ID over Yotpo's internal numeric product_id.
        if (is_numeric($productId) && filled($fallbackProductId) && ! is_numeric($fallbackProductId)) {
            $productId = $fallbackProductId;
        }

        $published = array_key_exists('published', $review)
            ? (bool) $review['published']
            : true;

        return new ReviewData(
            externalId: (string) data_get($review, 'id'),
            provider: $this->key(),
            rating: (int) data_get($review, 'score', data_get($review, 'rating', 0)),
            title: data_get($review, 'title'),
            body: data_get($review, 'content') ?? data_get($review, 'body'),
            authorName: $author,
            productId: $productId !== null ? (string) $productId : null,
            verified: (bool) (
                data_get($review, 'verified_buyer')
                || data_get($review, 'reviewer_type') === 'verified_buyer'
            ),
            reviewedAt: data_get($review, 'created_at'),
            published: $published,
            deleted: (bool) data_get($review, 'deleted', false),
            raw: $review,
        );
    }
}
