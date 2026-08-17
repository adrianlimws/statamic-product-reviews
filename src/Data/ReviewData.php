<?php

namespace Brainjuredstudio\ProductReviews\Data;

class ReviewData
{
    /**
     * @param  list<string>  $images
     */
    public function __construct(
        public readonly string $externalId,
        public readonly string $provider,
        public readonly int $rating,
        public readonly ?string $title,
        public readonly ?string $body,
        public readonly ?string $authorName,
        public readonly ?string $productId,
        public readonly bool $verified,
        public readonly ?string $reviewedAt,
        public readonly bool $published = true,
        public readonly bool $deleted = false,
        public readonly array $images = [],
        public readonly array $raw = [],
    ) {
    }

    public function toEntryData(): array
    {
        return [
            'external_id' => $this->externalId,
            'provider' => $this->provider,
            'rating' => $this->rating,
            'title' => $this->title,
            'body' => $this->body,
            'author_name' => $this->authorName,
            'product_id' => $this->productId,
            'verified' => $this->verified,
            'reviewed_at' => $this->reviewedAt,
            'images' => $this->images,
            'raw' => $this->raw ? json_encode($this->raw) : null,
        ];
    }
}
