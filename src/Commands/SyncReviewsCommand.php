<?php

namespace Brainjuredstudio\ProductReviews\Commands;

use Brainjuredstudio\ProductReviews\Sync\ReviewSynchronizer;
use Illuminate\Console\Command;

class SyncReviewsCommand extends Command
{
    protected $signature = 'product-reviews:sync';

    protected $description = 'Sync product reviews from the configured provider';

    public function handle(ReviewSynchronizer $synchronizer): int
    {
        $this->info('Syncing product reviews...');

        try {
            $stats = $synchronizer->sync();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Done. created=%d updated=%d skipped=%d unpublished=%d',
            $stats['created'],
            $stats['updated'],
            $stats['skipped'],
            $stats['unpublished'],
        ));

        return self::SUCCESS;
    }
}
