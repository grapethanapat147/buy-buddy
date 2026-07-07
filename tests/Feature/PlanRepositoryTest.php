<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Repositories\PlanRepository;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('saves and updates a single plan per user with synced products', function () {
    $user = User::factory()->create();
    $a = Product::factory()->for(Category::factory())->create();
    $b = Product::factory()->for(Category::factory())->create();
    $repo = new PlanRepository;

    $spec = ['budget' => 5000, 'room_type' => 'studio', 'occupants' => 1, 'cooking' => 'sometimes', 'owned_product_ids' => []];
    $repo->save($user, $spec, [$a->id, $b->id]);
    $repo->save($user, $spec, [$a->id]); // update, not duplicate

    expect(\App\Models\Plan::where('user_id', $user->id)->count())->toBe(1);
    expect($repo->load($user)->products->pluck('id')->all())->toBe([$a->id]);
});
