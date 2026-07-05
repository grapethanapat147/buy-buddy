<?php

use App\Enums\ProductTier;
use App\Models\Category;
use App\Models\Product;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('casts tier to the enum', function () {
    $product = Product::factory()->must()->create();

    expect($product->tier)->toBe(ProductTier::Must);
});

it('falls back to ref_price when it has no platform prices', function () {
    $product = Product::factory()->create(['ref_price' => 300]);

    expect($product->cheapestPrice())->toBe(300);
});

it('lets a category read back its products', function () {
    $category = Category::factory()->create();
    Product::factory()->count(2)->for($category)->create();

    expect($category->products)->toHaveCount(2);
});
