<?php

namespace Brainjuredstudio\ProductReviews\Support;

use Illuminate\Support\Collection as LaravelCollection;
use Statamic\Facades\Entry;

class SchemaBuilder
{
    public function forProduct(?string $productId = null, ?string $productName = null): array
    {
        $reviews = $this->reviews($productId);

        if ($reviews->isEmpty()) {
            return [];
        }

        $average = round($reviews->avg(fn ($entry) => (float) $entry->get('rating')), 2);
        $count = $reviews->count();

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $productName ?: ($productId ? "Product {$productId}" : 'Product'),
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => $average,
                'reviewCount' => $count,
                'bestRating' => 5,
                'worstRating' => 1,
            ],
            'review' => $reviews->take(10)->map(function ($entry) {
                return [
                    '@type' => 'Review',
                    'author' => [
                        '@type' => 'Person',
                        'name' => $entry->get('author_name') ?: 'Anonymous',
                    ],
                    'datePublished' => self::formatDate($entry->get('reviewed_at')),
                    'reviewBody' => $entry->get('body'),
                    'name' => $entry->get('title'),
                    'reviewRating' => [
                        '@type' => 'Rating',
                        'ratingValue' => (int) $entry->get('rating'),
                        'bestRating' => 5,
                        'worstRating' => 1,
                    ],
                ];
            })->values()->all(),
        ];

        if ($productId) {
            $schema['sku'] = $productId;
        }

        return $schema;
    }

    public function scriptTag(?string $productId = null, ?string $productName = null): string
    {
        $schema = $this->forProduct($productId, $productName);

        if ($schema === []) {
            return '';
        }

        return '<script type="application/ld+json">'.json_encode(
            $schema,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        ).'</script>';
    }

    protected function reviews(?string $productId): LaravelCollection
    {
        $query = Entry::query()
            ->where('collection', config('product-reviews.collection', 'product_reviews'))
            ->whereStatus('published');

        if ($productId) {
            $query->where('product_id', $productId);
        }

        return $query->get()->collect();
    }

    protected static function formatDate(mixed $value): ?string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value) && $value !== '') {
            return substr($value, 0, 10);
        }

        return null;
    }
}
