<?php

use App\Models\Product;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('exposes paired products for a bundle', function () {
    $riceCooker = Product::factory()->create();
    $rice = Product::factory()->create();
    $spoon = Product::factory()->create();

    $riceCooker->pairedProducts()->attach([$rice->id, $spoon->id]);

    expect($riceCooker->pairedProducts->pluck('id'))
        ->toContain($rice->id, $spoon->id)
        ->toHaveCount(2);
});
