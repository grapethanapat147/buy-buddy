<?php

use App\Enums\ProductTier;
use App\Recommendation\PlanAdvisor;

function line(int $id, ProductTier $tier, int $total): array
{
    return ['productId' => $id, 'tier' => $tier, 'lineTotal' => $total];
}

it('reports in-budget with no suggestions when total fits', function () {
    $summary = (new PlanAdvisor)->summarize([
        line(1, ProductTier::Must, 800),
        line(2, ProductTier::Optional, 150),
    ], budget: 1000);

    expect($summary->total)->toBe(950)
        ->and($summary->overBudgetBy)->toBe(0)
        ->and($summary->mustExceedsBudget)->toBeFalse()
        ->and($summary->suggestedDeferrals)->toBe([]);
});

it('suggests deferring lowest-priority non-must items until back in budget', function () {
    $summary = (new PlanAdvisor)->summarize([
        line(1, ProductTier::Must, 800),
        line(2, ProductTier::Optional, 250),
        line(3, ProductTier::Recommended, 120),
    ], budget: 1000);

    // total 1170, over by 170. Defer optional (250) first -> back under budget.
    expect($summary->overBudgetBy)->toBe(170)
        ->and($summary->mustExceedsBudget)->toBeFalse()
        ->and($summary->suggestedDeferrals)->toBe([2]);
});

it('flags when musts alone exceed budget and suggests nothing', function () {
    $summary = (new PlanAdvisor)->summarize([
        line(1, ProductTier::Must, 2000),
        line(2, ProductTier::Must, 1500),
    ], budget: 3000);

    expect($summary->overBudgetBy)->toBe(500)
        ->and($summary->mustExceedsBudget)->toBeTrue()
        ->and($summary->suggestedDeferrals)->toBe([]);
});
