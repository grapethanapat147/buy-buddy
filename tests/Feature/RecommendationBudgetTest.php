<?php

use App\Enums\ProductTier;
use App\Models\Category;
use App\Models\Product;
use App\Recommendation\RecommendationService;
use App\Recommendation\Spec;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function product(ProductTier $tier, int $price): Product
{
    return Product::factory()->for(Category::factory())->create(['tier' => $tier, 'ref_price' => $price, 'triggers' => []]);
}

it('defers optional items that do not fit the budget', function () {
    product(ProductTier::Must, 800);
    product(ProductTier::Optional, 300);

    $spec = new Spec(budget: 1000, roomType: 'studio', occupants: 1, cooking: 'sometimes');
    $result = app(RecommendationService::class)->recommend($spec);

    expect($result->plannedTotal())->toBe(800)
        ->and($result->deferred())->toHaveCount(1)
        ->and($result->mustExceedsBudget)->toBeFalse();
});

it('keeps all musts and flags when musts alone exceed the budget', function () {
    product(ProductTier::Must, 2000);
    product(ProductTier::Must, 1500);
    product(ProductTier::Optional, 100);

    $spec = new Spec(budget: 3000, roomType: 'studio', occupants: 1, cooking: 'sometimes');
    $result = app(RecommendationService::class)->recommend($spec);

    expect($result->mustExceedsBudget)->toBeTrue()
        ->and($result->plannedTotal())->toBe(3500)
        ->and($result->overBudgetBy())->toBe(500)
        ->and($result->deferred())->toHaveCount(1);
});
