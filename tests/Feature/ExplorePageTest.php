<?php

use App\Models\Category;
use App\Models\Product;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('lists all products by default', function () {
    Product::factory()->for(Category::factory())->count(3)->create();

    $this->get('/explore')->assertInertia(fn (Assert $page) => $page->component('Explore')->has('products', 3));
});

it('filters by category', function () {
    $kitchen = Category::factory()->create(['slug' => 'kitchen']);
    Product::factory()->for($kitchen)->create();
    Product::factory()->for(Category::factory()->create(['slug' => 'bedroom']))->create();

    $this->get('/explore?category=kitchen')->assertInertia(fn (Assert $page) => $page->has('products', 1));
});

it('filters by search query', function () {
    Product::factory()->for(Category::factory())->create(['name' => 'หม้อหุงข้าว']);
    Product::factory()->for(Category::factory())->create(['name' => 'พัดลม']);

    $this->get('/explore?q=หม้อ')->assertInertia(fn (Assert $page) => $page->has('products', 1));
});
