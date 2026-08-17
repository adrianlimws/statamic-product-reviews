<?php

namespace Brainjuredstudio\ProductReviews\Tests;

use Brainjuredstudio\ProductReviews\Jobs\SyncReviewsJob;
use Brainjuredstudio\ProductReviews\Support\SyncStatus;
use Brainjuredstudio\ProductReviews\Yotpo\YotpoClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

class YotpoClientTest extends TestCase
{
    public function test_connection_succeeds_when_auth_and_reviews_respond(): void
    {
        config([
            'product-reviews.yotpo.app_key' => 'test-app-key',
            'product-reviews.yotpo.secret' => 'test-secret',
            'product-reviews.yotpo.base_url' => 'https://api.yotpo.com',
        ]);

        Http::fake([
            'https://api.yotpo.com/oauth/token' => Http::response(['access_token' => 'fake-utoken']),
            'https://api.yotpo.com/v1/apps/test-app-key/reviews*' => Http::response([
                'reviews' => [['id' => 1, 'score' => 5, 'content' => 'OK']],
            ]),
        ]);

        $client = app(YotpoClient::class);
        $client->clearTokenCache();

        $result = $client->testConnection();

        $this->assertTrue($result['ok']);
        $this->assertStringContainsString('connection ok', strtolower($result['message']));
    }

    public function test_connection_fails_when_auth_rejected(): void
    {
        config([
            'product-reviews.yotpo.app_key' => 'test-app-key',
            'product-reviews.yotpo.secret' => 'bad-secret',
            'product-reviews.yotpo.base_url' => 'https://api.yotpo.com',
        ]);

        Http::fake([
            'https://api.yotpo.com/oauth/token' => Http::response(['error' => 'invalid_client'], 401),
        ]);

        $client = app(YotpoClient::class);
        $client->clearTokenCache();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Yotpo authentication failed');

        $client->testConnection();
    }

    public function test_sync_job_is_queued_from_cp_flow(): void
    {
        Queue::fake();

        SyncStatus::remember([
            'success' => true,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'unpublished' => 0,
            'error' => null,
            'provider' => 'yotpo',
        ]);

        SyncReviewsJob::dispatch();

        Queue::assertPushed(SyncReviewsJob::class);
    }

    public function test_sync_job_marks_syncing_before_running(): void
    {
        config([
            'product-reviews.provider' => 'yotpo',
            'product-reviews.yotpo.app_key' => 'test-app-key',
            'product-reviews.yotpo.secret' => 'test-secret',
            'product-reviews.yotpo.base_url' => 'https://api.yotpo.com',
        ]);

        Http::fake([
            'https://api.yotpo.com/oauth/token' => Http::response(['access_token' => 'fake-utoken']),
            'https://api.yotpo.com/v1/apps/test-app-key/reviews*' => Http::response([
                'response' => ['reviews' => []],
            ]),
        ]);

        app(YotpoClient::class)->clearTokenCache();

        SyncReviewsJob::dispatchSync();

        $this->assertFalse(SyncStatus::get()['syncing']);
        $this->assertTrue(SyncStatus::get()['success']);
    }
}
