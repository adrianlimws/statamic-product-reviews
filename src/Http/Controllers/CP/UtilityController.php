<?php

namespace Brainjuredstudio\ProductReviews\Http\Controllers\CP;

use Brainjuredstudio\ProductReviews\Contracts\ReviewProvider;
use Brainjuredstudio\ProductReviews\Jobs\SyncReviewsJob;
use Brainjuredstudio\ProductReviews\Support\SyncStatus;
use Brainjuredstudio\ProductReviews\Yotpo\YotpoClient;
use Illuminate\Http\RedirectResponse;
use Statamic\Http\Controllers\CP\CpController;

class UtilityController extends CpController
{
    public function sync(ReviewProvider $provider): RedirectResponse
    {
        $this->authorize('access product_reviews utility');

        if (! $provider->isConfigured()) {
            return back()->with('error', 'Review provider is not configured. Add credentials to your .env file.');
        }

        if (SyncStatus::get()['syncing'] ?? false) {
            return back()->with('success', 'Sync is already in progress. Refresh this page shortly.');
        }

        SyncReviewsJob::dispatch();

        return back()->with('success', 'Sync queued. Refresh this page in a moment to see results.');
    }

    public function test(ReviewProvider $provider, YotpoClient $client): RedirectResponse
    {
        $this->authorize('access product_reviews utility');

        if (! $provider->isConfigured()) {
            return back()->with('error', 'Review provider is not configured. Add credentials to your .env file.');
        }

        try {
            $result = $client->testConnection();
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $result['message'] ?? 'Connection OK.');
    }
}
