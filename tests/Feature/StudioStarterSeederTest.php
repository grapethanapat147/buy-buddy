<?php

use App\Models\Product;
use Database\Seeders\StudioStarterSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds a starter catalog with a rice cooker bundle', function () {
    $this->seed(StudioStarterSeeder::class);

    $riceCooker = Product::where('slug', 'rice-cooker')->firstOrFail();

    expect(Product::count())->toBeGreaterThanOrEqual(6)
        ->and($riceCooker->pairedProducts)->not->toBeEmpty()
        ->and($riceCooker->prices)->not->toBeEmpty();

    expect(Product::where('slug', 'rice-cooker')->value('icon'))->toBe('🍚');
});
