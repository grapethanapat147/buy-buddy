<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function show(Product $product): Response
    {
        $product->load(['prices', 'pairedProducts']);

        $cheapest = $product->prices->sortBy('price')->first();

        return Inertia::render('ProductDetail', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'tier' => $product->tier->value,
                'cheapest' => $cheapest ? ['platform' => $cheapest->platform, 'price' => $cheapest->price] : ['platform' => null, 'price' => $product->cheapestPrice()],
                'otherStoreCount' => max(0, $product->prices->count() - 1),
            ],
            'bundle' => $product->pairedProducts->map(fn (Product $p) => [
                'id' => $p->id, 'name' => $p->name, 'price' => $p->cheapestPrice(),
            ])->values(),
        ]);
    }
}
