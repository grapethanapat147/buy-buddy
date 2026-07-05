<?php

namespace App\Recommendation;

readonly class Spec
{
    /**
     * @param  array<int>  $ownedProductIds
     */
    public function __construct(
        public int $budget,
        public string $roomType,
        public int $occupants,
        public string $cooking,
        public array $ownedProductIds = [],
    ) {}

    public function owns(int $productId): bool
    {
        return in_array($productId, $this->ownedProductIds, true);
    }

    public function value(string $field): int|string
    {
        return match ($field) {
            'budget' => $this->budget,
            'room_type' => $this->roomType,
            'occupants' => $this->occupants,
            'cooking' => $this->cooking,
        };
    }
}
