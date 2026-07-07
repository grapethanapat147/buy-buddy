<?php

use App\Models\Category;
use App\Models\Plan;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

it('shows no auth user to guests on my plan', function () {
    session(['spec' => ['budget' => 5000, 'room_type' => 'studio', 'occupants' => 1, 'cooking' => 'sometimes', 'owned_product_ids' => []], 'plan_ids' => []]);

    $this->get('/plan')->assertInertia(fn (Assert $page) => $page->where('auth.user', null));
});

it('lets an authenticated user save the current plan', function () {
    $user = User::factory()->create();
    $product = Product::factory()->for(Category::factory())->create();
    session([
        'spec' => ['budget' => 5000, 'room_type' => 'studio', 'occupants' => 1, 'cooking' => 'sometimes', 'owned_product_ids' => []],
        'plan_ids' => [$product->id],
    ]);

    $this->actingAs($user)->post('/plan/save')->assertRedirect();

    expect(Plan::where('user_id', $user->id)->first()->products->pluck('id')->all())->toBe([$product->id]);
});

it('blocks guests from the save endpoint', function () {
    $this->post('/plan/save')->assertRedirect(route('login'));
});
