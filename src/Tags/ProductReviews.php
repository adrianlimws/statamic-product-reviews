<?php

namespace Brainjuredstudio\ProductReviews\Tags;

use Brainjuredstudio\ProductReviews\Support\SchemaBuilder;
use Statamic\Facades\Entry;
use Statamic\Tags\Tags;

class ProductReviews extends Tags
{
    protected static $handle = 'product_reviews';

    /**
     * {{ product_reviews product_id="sku" limit="10" }}
     *   {{ rating }} {{ author_name }} {{ body }}
     * {{ /product_reviews }}
     */
    public function index()
    {
        return $this->query()->get()->toAugmentedArray();
    }

    /**
     * {{ product_reviews:average product_id="sku" }}
     */
    public function average()
    {
        $entries = $this->query()->get();

        if ($entries->isEmpty()) {
            return 0;
        }

        return round($entries->avg(fn ($entry) => (float) $entry->get('rating')), 2);
    }

    /**
     * {{ product_reviews:count product_id="sku" }}
     */
    public function count()
    {
        return $this->query()->count();
    }

    /**
     * {{ product_reviews:schema product_id="sku" product_name="Mug" }}
     */
    public function schema()
    {
        return app(SchemaBuilder::class)->scriptTag(
            $this->params->get('product_id'),
            $this->params->get('product_name'),
        );
    }

    protected function query()
    {
        $query = Entry::query()
            ->where('collection', config('product-reviews.collection', 'product_reviews'))
            ->whereStatus('published');

        if ($productId = $this->params->get('product_id')) {
            $query->where('product_id', $productId);
        }

        if ($minRating = $this->params->get('min_rating')) {
            $query->where('rating', '>=', (int) $minRating);
        }

        if ($provider = $this->params->get('provider')) {
            $query->where('provider', $provider);
        }

        $sort = $this->params->get('sort', 'reviewed_at:desc');
        [$field, $direction] = array_pad(explode(':', $sort, 2), 2, 'desc');
        $query->orderBy($field, $direction);

        if ($limit = $this->params->int('limit')) {
            $query->limit($limit);
        }

        return $query;
    }
}
