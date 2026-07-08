<?php

use App\Enums\ProductTier;
use App\Models\Category;
use App\Models\Product;
use App\Recommendation\RecommendationService;
use App\Recommendation\Spec;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeProduct(array $attrs): Product
{
    return Product::factory()->for(Category::factory())->create($attrs);
}

it('excludes products the user already owns', function () {
    $keep = makeProduct(['ref_price' => 100, 'tier' => ProductTier::Must, 'triggers' => []]);
    $owned = makeProduct(['ref_price' => 100, 'tier' => ProductTier::Must, 'triggers' => []]);

    $spec = new Spec(budget: 5000, roomType: 'studio', occupants: 1, cooking: 'sometimes', ownedProductIds: [$owned->id]);
    $result = app(RecommendationService::class)->recommend($spec);

    $ids = collect($result->items)->pluck('productId');
    expect($ids)->toContain($keep->id)->not->toContain($owned->id);
});

it('excludes products whose triggers do not match', function () {
    $cook = makeProduct(['tier' => ProductTier::Must, 'triggers' => [['field' => 'cooking', 'op' => 'in', 'value' => ['often']]]]);

    $spec = new Spec(budget: 5000, roomType: 'studio', occupants: 1, cooking: 'never');
    $result = app(RecommendationService::class)->recommend($spec);

    expect(collect($result->items)->pluck('productId'))->not->toContain($cook->id);
});

it('scales quantity for consumables by occupants', function () {
    $detergent = makeProduct(['ref_price' => 60, 'tier' => ProductTier::Must, 'qty_scales_by' => 'occupants', 'triggers' => []]);

    $spec = new Spec(budget: 5000, roomType: 'studio', occupants: 3, cooking: 'sometimes');
    $result = app(RecommendationService::class)->recommend($spec);

    $item = collect($result->items)->firstWhere('productId', $detergent->id);
    expect($item->quantity)->toBe(3)->and($item->lineTotal)->toBe(180);
});

it('orders must-have items before optional ones', function () {
    $optional = makeProduct(['ref_price' => 100, 'tier' => ProductTier::Optional, 'triggers' => []]);
    $must = makeProduct(['ref_price' => 100, 'tier' => ProductTier::Must, 'triggers' => []]);

    $spec = new Spec(budget: 5000, roomType: 'studio', occupants: 1, cooking: 'sometimes');
    $result = app(RecommendationService::class)->recommend($spec);

    expect($result->items[0]->productId)->toBe($must->id);
});

it('hides optional items for essentials spenders but shows them for comfort spenders', function () {
    $optional = makeProduct(['tier' => ProductTier::Optional, 'triggers' => []]);

    $essentials = new Spec(budget: 5000, roomType: 'studio', occupants: 1, cooking: 'sometimes', spendingStyle: 'essentials');
    $comfort = new Spec(budget: 5000, roomType: 'studio', occupants: 1, cooking: 'sometimes', spendingStyle: 'comfort');

    expect(collect(app(RecommendationService::class)->recommend($essentials)->items)->pluck('productId'))->not->toContain($optional->id)
        ->and(collect(app(RecommendationService::class)->recommend($comfort)->items)->pluck('productId'))->toContain($optional->id);
});
