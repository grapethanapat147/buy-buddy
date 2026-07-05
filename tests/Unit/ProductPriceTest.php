<?php

use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns the lowest platform price as cheapest', function () {
    $product = Product::factory()->create(['ref_price' => 999]);
    ProductPrice::factory()->for($product)->create(['price' => 650]);
    ProductPrice::factory()->for($product)->create(['price' => 590]);
    ProductPrice::factory()->for($product)->create(['price' => 610]);

    expect($product->fresh()->cheapestPrice())->toBe(590);
});
