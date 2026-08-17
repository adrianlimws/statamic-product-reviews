<?php

namespace Brainjuredstudio\ProductReviews\Tests;

use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;
use Statamic\Statamic;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

class ProductReviewsTagTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'product-reviews.collection' => 'product_reviews',
        ]);

        Collection::make('product_reviews')
            ->title('Product Reviews')
            ->save();

        $this->seedReview('1001', 'mug-01', 5, 'Alex R.', 'Great mug', 0);
        $this->seedReview('1002', 'mug-01', 4, 'Jamie L.', 'Good mug', 1);
        $this->seedReview('1003', 'shirt-01', 3, 'Sam T.', 'Nice shirt', 2);
    }

    public function test_count_tag_returns_matching_reviews(): void
    {
        $count = Statamic::tag('product_reviews:count')
            ->param('product_id', 'mug-01')
            ->fetch();

        $this->assertSame(2, $count);
    }

    public function test_average_tag_returns_rounded_mean(): void
    {
        $average = Statamic::tag('product_reviews:average')
            ->param('product_id', 'mug-01')
            ->fetch();

        $this->assertSame(4.5, $average);
    }

    public function test_index_tag_respects_page_and_limit(): void
    {
        $page1 = Statamic::tag('product_reviews')
            ->param('product_id', 'mug-01')
            ->param('limit', 1)
            ->param('page', 1)
            ->fetch();

        $page2 = Statamic::tag('product_reviews')
            ->param('product_id', 'mug-01')
            ->param('limit', 1)
            ->param('page', 2)
            ->fetch();

        $this->assertCount(1, $page1);
        $this->assertCount(1, $page2);
        $this->assertNotSame((string) $page1[0]['external_id'], (string) $page2[0]['external_id']);
    }

    public function test_index_tag_respects_offset(): void
    {
        $results = Statamic::tag('product_reviews')
            ->param('product_id', 'mug-01')
            ->param('limit', 1)
            ->param('offset', 1)
            ->fetch();

        $this->assertCount(1, $results);
        $this->assertSame('1001', (string) $results[0]['external_id']);
    }

    public function test_schema_tag_outputs_json_ld_when_reviews_exist(): void
    {
        $output = (string) Statamic::tag('product_reviews:schema')
            ->param('product_id', 'mug-01')
            ->param('product_name', 'Test Mug')
            ->fetch();

        $this->assertStringContainsString('application/ld+json', $output);
        $this->assertStringContainsString('AggregateRating', $output);
        $this->assertStringContainsString('Test Mug', $output);
    }

    public function test_images_are_available_in_tag_output(): void
    {
        $entry = Entry::query()
            ->where('collection', 'product_reviews')
            ->where('external_id', '1002')
            ->first();

        $entry->set('images', ['https://cdn.example.com/photo.jpg'])->save();

        $results = Statamic::tag('product_reviews')
            ->param('product_id', 'mug-01')
            ->param('limit', 1)
            ->fetch();

        $this->assertSame(['https://cdn.example.com/photo.jpg'], $results[0]['images']->raw());
    }

    protected function seedReview(
        string $externalId,
        string $productId,
        int $rating,
        string $author,
        string $title,
        int $sortOffsetDays,
    ): void {
        Entry::make()
            ->collection('product_reviews')
            ->locale(Site::default()->handle())
            ->slug("yotpo-{$externalId}")
            ->published(true)
            ->data([
                'title' => $title,
                'body' => "{$title} body",
                'author_name' => $author,
                'rating' => $rating,
                'product_id' => $productId,
                'verified' => true,
                'reviewed_at' => now()->subDays(10 - $sortOffsetDays)->toIso8601String(),
                'external_id' => $externalId,
                'provider' => 'yotpo',
                'manual_override' => false,
                'images' => [],
            ])
            ->save();
    }
}
