<?php

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('persists a category with a unique slug', function () {
    $category = Category::factory()->create(['slug' => 'kitchen']);

    expect($category->fresh()->slug)->toBe('kitchen');
});
