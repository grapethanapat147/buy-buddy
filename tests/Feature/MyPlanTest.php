<?php

use App\Enums\ProductTier;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function planWith(array $ids): void
{
    session([
        'spec' => ['budget' => 1000, 'room_type' => 'studio', 'occupants' => 1, 'cooking' => 'sometimes', 'owned_product_ids' => []],
        'plan_ids' => $ids,
    ]);
}

it('reports in-budget when the chosen basket fits', function () {
    $p = Product::factory()->for(Category::factory())->create(['tier' => ProductTier::Must, 'ref_price' => 800]);
    planWith([$p->id]);

    $this->get('/plan')->assertInertia(fn (Assert $page) => $page
        ->component('MyPlan')->where('total', 800)->where('overBudgetBy', 0)->where('mustExceedsBudget', false));
});

it('flags over-budget and suggests deferring an optional item', function () {
    $must = Product::factory()->for(Category::factory())->create(['tier' => ProductTier::Must, 'ref_price' => 800]);
    $opt = Product::factory()->for(Category::factory())->create(['tier' => ProductTier::Optional, 'ref_price' => 300]);
    planWith([$must->id, $opt->id]);

    $this->get('/plan')->assertInertia(fn (Assert $page) => $page
        ->where('overBudgetBy', 100)
        ->where('mustExceedsBudget', false)
        ->has('items', 2));
});

it('flags must-exceeds-budget', function () {
    $a = Product::factory()->for(Category::factory())->create(['tier' => ProductTier::Must, 'ref_price' => 700]);
    $b = Product::factory()->for(Category::factory())->create(['tier' => ProductTier::Must, 'ref_price' => 500]);
    planWith([$a->id, $b->id]);

    $this->get('/plan')->assertInertia(fn (Assert $page) => $page->where('mustExceedsBudget', true)->where('overBudgetBy', 200));
});

it('includes a per-store rollup', function () {
    $product = Product::factory()->for(Category::factory())->create(['ref_price' => 300]);
    \App\Models\ProductPrice::factory()->for($product)->create(['platform' => 'Shopee', 'price' => 300]);
    planWith([$product->id]);

    $this->get('/plan')->assertInertia(fn (Assert $page) => $page
        ->has('storeRollup', 1, fn (Assert $row) => $row->where('platform', 'Shopee')->where('total', 300)));
});
