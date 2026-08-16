<?php

namespace Brainjuredstudio\ProductReviews\Providers;

use Brainjuredstudio\ProductReviews\Contracts\ReviewProvider;
use Brainjuredstudio\ProductReviews\Data\ReviewData;
use Brainjuredstudio\ProductReviews\Yotpo\YotpoClient;
use Generator;

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
        $page = 1;
        $pageSize = (int) config('product-reviews.sync.page_size', 100);

        do {
            $payload = $this->client->fetchReviewsPage($page, $pageSize);
            $reviews = data_get($payload, 'response.reviews', data_get($payload, 'reviews', []));

            if (! is_array($reviews) || count($reviews) === 0) {
                break;
            }

            foreach ($reviews as $review) {
                yield $this->mapReview($review);
            }

            $page++;
        } while (count($reviews) >= $pageSize);
    }

    protected function mapReview(array $review): ReviewData
    {
        $author = data_get($review, 'user.display_name')
            ?? data_get($review, 'name')
            ?? data_get($review, 'display_name');

        $productId = data_get($review, 'sku')
            ?? data_get($review, 'external_product_id')
            ?? data_get($review, 'product.domain_key')
            ?? data_get($review, 'product_id');

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
