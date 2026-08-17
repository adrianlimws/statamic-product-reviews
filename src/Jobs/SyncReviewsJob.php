<?php

namespace Brainjuredstudio\ProductReviews\Jobs;

use Brainjuredstudio\ProductReviews\Support\SyncStatus;
use Brainjuredstudio\ProductReviews\Sync\ReviewSynchronizer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncReviewsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function handle(ReviewSynchronizer $synchronizer): void
    {
        SyncStatus::markSyncing();

        $synchronizer->sync();
    }
}
