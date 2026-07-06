<?php

use App\Filament\Resources\ProductResource\Pages\EditProduct;
use App\Filament\Resources\ProductResource\RelationManagers\ProductPricesRelationManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class);

it('adds a platform price to a product', function () {
    $this->actingAs(User::factory()->create());
    $product = Product::factory()->for(Category::factory())->create();

    livewire(ProductPricesRelationManager::class, [
        'ownerRecord' => $product,
        'pageClass' => EditProduct::class,
    ])
        ->callTableAction('create', data: ['platform' => 'Shopee', 'price' => 590])
        ->assertHasNoTableActionErrors();

    $this->assertDatabaseHas('product_prices', ['product_id' => $product->id, 'platform' => 'Shopee', 'price' => 590]);
});
