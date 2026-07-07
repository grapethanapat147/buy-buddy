<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Recommendation\StoreRollup;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('totals each platform, falling back to cheapest where a store lacks the item', function () {
    $a = Product::factory()->for(Category::factory())->create(['ref_price' => 100]);
    ProductPrice::factory()->for($a)->create(['platform' => 'Shopee', 'price' => 100]);
    ProductPrice::factory()->for($a)->create(['platform' => 'Lazada', 'price' => 120]);

    $b = Product::factory()->for(Category::factory())->create(['ref_price' => 200]);
    ProductPrice::factory()->for($b)->create(['platform' => 'Shopee', 'price' => 200]);

    $rows = (new StoreRollup)->summarize([
        ['product' => $a->fresh('prices'), 'qty' => 1],
        ['product' => $b->fresh('prices'), 'qty' => 2],
    ]);

    // Shopee: 100*1 + 200*2 = 500 ; Lazada: 120*1 + cheapest(200)*2 = 520
    expect($rows[0])->toBe(['platform' => 'Shopee', 'total' => 500])
        ->and($rows[1])->toBe(['platform' => 'Lazada', 'total' => 520]);
});
