<?php

namespace App\Recommendation;

use App\Enums\ProductTier;

readonly class RecommendationItem
{
    public function __construct(
        public int $productId,
        public string $name,
        public ProductTier $tier,
        public int $quantity,
        public int $lineTotal,
        public string $status,
    ) {}
}
