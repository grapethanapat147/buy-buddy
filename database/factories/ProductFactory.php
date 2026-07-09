<?php

namespace Database\Factories;

use App\Enums\ProductMode;
use App\Enums\ProductTier;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'tier' => fake()->randomElement(ProductTier::cases()),
            'mode' => ProductMode::MoveIn,
            'ref_price' => fake()->numberBetween(50, 2000),
            'restock_cadence' => null,
            'qty_scales_by' => null,
            'triggers' => [],
            'icon' => '📦',
        ];
    }

    public function must(): static
    {
        return $this->state(['tier' => ProductTier::Must]);
    }

    public function optional(): static
    {
        return $this->state(['tier' => ProductTier::Optional]);
    }
}
