<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('shows the cheapest price and bundle', function () {
    $product = Product::factory()->for(Category::factory())->create(['ref_price' => 999]);
    ProductPrice::factory()->for($product)->create(['platform' => 'Shopee', 'price' => 590]);
    ProductPrice::factory()->for($product)->create(['platform' => 'Lazada', 'price' => 620]);
    $rice = Product::factory()->for(Category::factory())->create(['ref_price' => 180]);
    $product->pairedProducts()->attach($rice->id);

    $this->get("/products/{$product->id}")->assertInertia(fn (Assert $page) => $page
        ->component('ProductDetail')
        ->where('product.cheapest.price', 590)
        ->where('product.otherStoreCount', 1)
        ->has('bundle', 1));
});
