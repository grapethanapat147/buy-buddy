<?php

use App\Enums\ProductTier;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

function withSpec(): void
{
    session(['spec' => ['budget' => 5000, 'room_type' => 'studio', 'occupants' => 1, 'cooking' => 'sometimes', 'owned_product_ids' => []]]);
}

it('redirects to the wizard when there is no spec', function () {
    $this->get('/recommendations')->assertRedirect(route('wizard'));
});

it('renders recommended items for the session spec', function () {
    $p = Product::factory()->for(Category::factory())->create(['tier' => ProductTier::Must, 'ref_price' => 500, 'triggers' => []]);
    withSpec();

    $this->get('/recommendations')->assertInertia(fn (Assert $page) => $page
        ->component('Recommendations')
        ->where('budget', 5000)
        ->has('items', 1, fn (Assert $item) => $item->where('productId', $p->id)->etc()));
});

it('adds a product to the session plan', function () {
    $p = Product::factory()->for(Category::factory())->create();
    withSpec();

    $this->post("/plan/items/{$p->id}")->assertRedirect();
    expect(session('plan_ids'))->toContain($p->id);
});
