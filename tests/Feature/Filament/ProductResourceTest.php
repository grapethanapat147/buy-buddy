<?php

use App\Enums\ProductMode;
use App\Enums\ProductTier;
use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Filament\Resources\ProductResource\Pages\ListProducts;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs(User::factory()->create()));

it('lists products', function () {
    livewire(ListProducts::class)->assertOk();
});

it('creates a product and normalizes an "in" trigger to an array', function () {
    $category = Category::factory()->create();

    livewire(CreateProduct::class)
        ->fillForm([
            'category_id' => $category->id,
            'name' => 'หม้อหุงข้าว',
            'slug' => 'rice-cooker',
            'tier' => ProductTier::Must->value,
            'mode' => ProductMode::MoveIn->value,
            'ref_price' => 590,
            'triggers' => [
                ['field' => 'cooking', 'op' => 'in', 'value' => 'sometimes,often'],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::where('slug', 'rice-cooker')->firstOrFail();
    expect($product->triggers)->toBe([['field' => 'cooking', 'op' => 'in', 'value' => ['sometimes', 'often']]]);
});
