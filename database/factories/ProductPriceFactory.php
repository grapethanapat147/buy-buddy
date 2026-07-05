<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductPriceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'platform' => fake()->randomElement(['Shopee', 'Lazada', 'Makro']),
            'price' => fake()->numberBetween(50, 2000),
            'url' => fake()->url(),
        ];
    }
}
