<?php

namespace Brainjuredstudio\ProductReviews\Contracts;

use Brainjuredstudio\ProductReviews\Data\ReviewData;
use Generator;

interface ReviewProvider
{
    public function key(): string;

    public function isConfigured(): bool;

    /**
     * @return Generator<int, ReviewData>
     */
    public function fetchReviews(): Generator;
}
