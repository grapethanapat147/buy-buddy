<?php

use App\Filament\Resources\CategoryResource\Pages\CreateCategory;
use App\Filament\Resources\CategoryResource\Pages\ListCategories;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('lists categories', function () {
    livewire(ListCategories::class)->assertOk();
});

it('creates a category', function () {
    livewire(CreateCategory::class)
        ->fillForm(['name' => 'ครัว', 'slug' => 'kitchen', 'sort_order' => 1])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('categories', ['slug' => 'kitchen']);
});
