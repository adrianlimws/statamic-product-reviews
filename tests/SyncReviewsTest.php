<?php

namespace Brainjuredstudio\ProductReviews\Tests;

use Brainjuredstudio\ProductReviews\Sync\ReviewSynchronizer;
use Illuminate\Support\Facades\Http;
use Statamic\Facades\Collection;
use Statamic\Facades\Entry;
use Statamic\Testing\Concerns\PreventsSavingStacheItemsToDisk;

class SyncReviewsTest extends TestCase
{
    use PreventsSavingStacheItemsToDisk;

    protected array $yotpoPages = [];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'product-reviews.provider' => 'yotpo',
            'product-reviews.collection' => 'product_reviews',
            'product-reviews.sync.page_size' => 100,
            'product-reviews.yotpo.app_key' => 'test-app-key',
            'product-reviews.yotpo.secret' => 'test-secret',
            'product-reviews.yotpo.base_url' => 'https://api.yotpo.com',
        ]);

        Collection::make('product_reviews')
            ->title('Product Reviews')
            ->save();

        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            if (str_contains($request->url(), '/oauth/token')) {
                return Http::response([
                    'access_token' => 'fake-utoken',
                    'token_type' => 'bearer',
                ]);
            }

            if (str_contains($request->url(), '/reviews')) {
                $payload = array_shift($this->yotpoPages) ?? ['response' => ['reviews' => []]];

                return Http::response($payload);
            }

            return Http::response(['error' => 'unexpected '.$request->url()], 500);
        });
    }

    public function test_sync_creates_entries_from_yotpo(): void
    {
        $this->queueYotpoPages();

        $stats = app(ReviewSynchronizer::class)->sync();

        $this->assertTrue($stats['success']);
        $this->assertSame(2, $stats['created']);

        $entries = Entry::query()
            ->where('collection', 'product_reviews')
            ->get();

        $this->assertCount(2, $entries);

        $mug = Entry::query()
            ->where('collection', 'product_reviews')
            ->where('external_id', '1001')
            ->first();

        $this->assertNotNull($mug);
        $this->assertSame('yotpo', $mug->get('provider'));
        $this->assertSame(5, (int) $mug->get('rating'));
        $this->assertSame('mug-01', $mug->get('product_id'));
        $this->assertSame('Alex R.', $mug->get('author_name'));
        $this->assertTrue((bool) $mug->get('verified'));
        $this->assertTrue($mug->published());
    }

    public function test_sync_skips_manual_override_entries(): void
    {
        $this->queueYotpoPages();

        app(ReviewSynchronizer::class)->sync();

        $entry = Entry::query()
            ->where('collection', 'product_reviews')
            ->where('external_id', '1001')
            ->first();

        $entry
            ->set('manual_override', true)
            ->set('body', 'Locally edited body')
            ->save();

        $this->queueYotpoPages([
            'id' => 1001,
            'title' => 'Great mug',
            'content' => 'API would overwrite this',
            'score' => 5,
            'created_at' => '2024-01-15T12:00:00.000Z',
            'verified_buyer' => true,
            'sku' => 'mug-01',
            'name' => 'Alex R.',
            'deleted' => false,
            'published' => true,
        ]);

        $stats = app(ReviewSynchronizer::class)->sync();

        $this->assertSame(1, $stats['skipped']);

        $entry = Entry::query()
            ->where('collection', 'product_reviews')
            ->where('external_id', '1001')
            ->first();

        $this->assertSame('Locally edited body', $entry->get('body'));
    }

    public function test_sync_updates_existing_entries(): void
    {
        $this->queueYotpoPages();
        app(ReviewSynchronizer::class)->sync();

        $this->queueYotpoPages([
            'id' => 1001,
            'title' => 'Great mug updated',
            'content' => 'Even better now.',
            'score' => 4,
            'created_at' => '2024-01-15T12:00:00.000Z',
            'verified_buyer' => true,
            'sku' => 'mug-01',
            'name' => 'Alex R.',
            'deleted' => false,
            'published' => true,
        ]);

        $stats = app(ReviewSynchronizer::class)->sync();

        $this->assertSame(1, $stats['updated']);

        $entry = Entry::query()
            ->where('collection', 'product_reviews')
            ->where('external_id', '1001')
            ->first();

        $this->assertSame('Great mug updated', $entry->get('title'));
        $this->assertSame('Even better now.', $entry->get('body'));
        $this->assertSame(4, (int) $entry->get('rating'));
    }

    public function test_sync_fails_when_auth_fails(): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake([
            'https://api.yotpo.com/oauth/token' => Http::response(['error' => 'invalid_client'], 401),
        ]);

        app(\Brainjuredstudio\ProductReviews\Yotpo\YotpoClient::class)->clearTokenCache();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Yotpo authentication failed');

        app(ReviewSynchronizer::class)->sync();
    }

    public function test_artisan_sync_command_succeeds(): void
    {
        $this->queueYotpoPages();

        $this->artisan('product-reviews:sync')
            ->assertSuccessful();
    }

    public function test_sync_falls_back_to_cdn_when_core_times_out(): void
    {
        config([
            'product-reviews.yotpo.source' => 'auto',
            'product-reviews.yotpo.product_ids' => ['mug-01'],
            'product-reviews.yotpo.include_site_reviews' => false,
        ]);

        app(\Brainjuredstudio\ProductReviews\Yotpo\YotpoClient::class)->clearTokenCache();

        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake(function (\Illuminate\Http\Client\Request $request) {
            if (str_contains($request->url(), '/oauth/token')) {
                return Http::response([
                    'access_token' => 'fake-utoken',
                    'token_type' => 'bearer',
                ]);
            }

            if (str_contains($request->url(), '/v1/apps/') && str_contains($request->url(), '/reviews')) {
                return Http::response('Gateway Timeout', 504);
            }

            if (str_contains($request->url(), '/v1/widget/') && str_contains($request->url(), '/reviews.json')) {
                return Http::response(
                    json_decode(file_get_contents(__DIR__.'/fixtures/yotpo-widget-reviews.json'), true)
                );
            }

            return Http::response(['error' => 'unexpected '.$request->url()], 500);
        });

        $stats = app(ReviewSynchronizer::class)->sync();

        $this->assertTrue($stats['success']);
        $this->assertSame(1, $stats['created']);

        $entry = Entry::query()
            ->where('collection', 'product_reviews')
            ->where('external_id', '2001')
            ->first();

        $this->assertNotNull($entry);
        $this->assertSame('mug-01', $entry->get('product_id'));
        $this->assertSame('CDN User', $entry->get('author_name'));
        $this->assertSame(['https://cdn.example.com/original.jpg'], $entry->get('images'));
    }

    public function test_cdn_only_source_skips_core_api(): void
    {
        config([
            'product-reviews.yotpo.source' => 'cdn',
            'product-reviews.yotpo.product_ids' => ['mug-01'],
            'product-reviews.yotpo.include_site_reviews' => false,
        ]);

        app(\Brainjuredstudio\ProductReviews\Yotpo\YotpoClient::class)->clearTokenCache();

        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::fake([
            'https://api-cdn.yotpo.com/*' => Http::response(
                json_decode(file_get_contents(__DIR__.'/fixtures/yotpo-widget-reviews.json'), true)
            ),
            'https://api.yotpo.com/*' => Http::response(['error' => 'core should not be called'], 500),
        ]);

        $stats = app(ReviewSynchronizer::class)->sync();

        $this->assertTrue($stats['success']);
        $this->assertSame(1, $stats['created']);
        Http::assertNotSent(fn (\Illuminate\Http\Client\Request $request) => str_contains($request->url(), 'api.yotpo.com'));
    }

    protected function queueYotpoPages(?array $singleReview = null): void
    {
        app(\Brainjuredstudio\ProductReviews\Yotpo\YotpoClient::class)->clearTokenCache();

        $page1 = $singleReview
            ? ['response' => ['reviews' => [$singleReview]]]
            : json_decode(file_get_contents(__DIR__.'/fixtures/yotpo-reviews-page-1.json'), true);

        $page2 = json_decode(file_get_contents(__DIR__.'/fixtures/yotpo-reviews-page-2.json'), true);

        $this->yotpoPages = [$page1, $page2];
    }
}
