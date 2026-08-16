<?php

namespace Brainjuredstudio\ProductReviews\Http\Controllers\CP;

use Brainjuredstudio\ProductReviews\Contracts\ReviewProvider;
use Brainjuredstudio\ProductReviews\Jobs\SyncReviewsJob;
use Brainjuredstudio\ProductReviews\Support\SyncStatus;
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

        try {
            SyncReviewsJob::dispatchSync();
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        $status = SyncStatus::get();

        return back()->with(
            'success',
            sprintf(
                'Sync complete. Created %d, updated %d, skipped %d.',
                $status['created'] ?? 0,
                $status['updated'] ?? 0,
                $status['skipped'] ?? 0,
            )
        );
    }
}
