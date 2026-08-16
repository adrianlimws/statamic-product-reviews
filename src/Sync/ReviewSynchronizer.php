<?php

namespace Brainjuredstudio\ProductReviews\Sync;

use Brainjuredstudio\ProductReviews\Contracts\ReviewProvider;
use Brainjuredstudio\ProductReviews\Data\ReviewData;
use Brainjuredstudio\ProductReviews\Support\SyncStatus;
use Illuminate\Support\Str;
use RuntimeException;
use Statamic\Facades\Entry;
use Statamic\Facades\Site;

class ReviewSynchronizer
{
    public function __construct(
        protected ReviewProvider $provider,
    ) {
    }

    public function sync(): array
    {
        if (! $this->provider->isConfigured()) {
            throw new RuntimeException("Review provider [{$this->provider->key()}] is not configured.");
        }

        $collection = config('product-reviews.collection', 'product_reviews');
        $stats = [
            'provider' => $this->provider->key(),
            'success' => true,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'unpublished' => 0,
            'error' => null,
        ];

        try {
            foreach ($this->provider->fetchReviews() as $review) {
                $result = $this->upsert($collection, $review);
                $stats[$result]++;
            }
        } catch (\Throwable $e) {
            $stats['success'] = false;
            $stats['error'] = $e->getMessage();
            SyncStatus::remember($stats);
            throw $e;
        }

        SyncStatus::remember($stats);

        return $stats;
    }

    protected function upsert(string $collection, ReviewData $review): string
    {
        $slug = $this->slugFor($review);

        $entry = Entry::query()
            ->where('collection', $collection)
            ->where('slug', $slug)
            ->first();

        if (! $entry) {
            $entry = Entry::query()
                ->where('collection', $collection)
                ->where('provider', $review->provider)
                ->where('external_id', $review->externalId)
                ->first();
        }

        if ($entry && $entry->get('manual_override')) {
            return 'skipped';
        }

        if ($review->deleted) {
            if ($entry && $entry->published()) {
                $entry->published(false)->save();

                return 'unpublished';
            }

            return 'skipped';
        }

        $data = $review->toEntryData();

        if (! $entry) {
            $title = $review->title
                ?: Str::limit(trim((string) $review->body), 60)
                ?: "Review {$review->externalId}";

            $entry = Entry::make()
                ->collection($collection)
                ->locale(Site::default()->handle())
                ->slug($slug)
                ->data(array_merge($data, [
                    'title' => $title,
                    'manual_override' => false,
                ]))
                ->published($review->published);

            $entry->save();

            return 'created';
        }

        foreach ($data as $key => $value) {
            $entry->set($key, $value);
        }

        if ($review->title) {
            $entry->set('title', $review->title);
        }

        $entry->published($review->published);
        $entry->save();

        return $review->published ? 'updated' : 'unpublished';
    }

    protected function slugFor(ReviewData $review): string
    {
        return Str::slug($review->provider.'-'.$review->externalId);
    }
}
