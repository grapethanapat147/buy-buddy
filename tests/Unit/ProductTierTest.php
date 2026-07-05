<?php

use App\Enums\ProductTier;

it('orders tiers must before recommended before optional', function () {
    expect(ProductTier::Must->priority())->toBeLessThan(ProductTier::Recommended->priority())
        ->and(ProductTier::Recommended->priority())->toBeLessThan(ProductTier::Optional->priority());
});
