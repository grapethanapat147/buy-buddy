<?php

use App\Enums\ProductTier;
use App\Recommendation\RecommendationItem;
use App\Recommendation\RecommendationResult;

it('separates in-plan and deferred items and computes the planned total', function () {
    $inPlan = new RecommendationItem(productId: 1, name: 'A', tier: ProductTier::Must, quantity: 1, lineTotal: 500, status: 'in_plan');
    $deferred = new RecommendationItem(productId: 2, name: 'B', tier: ProductTier::Optional, quantity: 1, lineTotal: 300, status: 'deferred');

    $result = new RecommendationResult(items: [$inPlan, $deferred], budget: 1000, mustExceedsBudget: false);

    expect($result->plannedTotal())->toBe(500)
        ->and($result->overBudgetBy())->toBe(0)
        ->and($result->inPlan())->toHaveCount(1)
        ->and($result->deferred())->toHaveCount(1);
});
