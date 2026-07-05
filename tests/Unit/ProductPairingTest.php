<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('exposes paired products for a bundle', function () {
    $riceCooker = Product::factory()->create();
    $rice = Product::factory()->create();
    $spoon = Product::factory()->create();

    $riceCooker->pairedProducts()->attach([$rice->id, $spoon->id]);

    expect($riceCooker->pairedProducts->pluck('id'))
        ->toContain($rice->id, $spoon->id)
        ->toHaveCount(2);
});
