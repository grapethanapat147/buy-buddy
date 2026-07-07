<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\PlanSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExploreController extends Controller
{
    public function index(Request $request, PlanSession $session): Response
    {
        $categorySlug = $request->string('category')->toString();
        $query = $request->string('q')->toString();

        $products = Product::query()
            ->with('category')
            ->when($categorySlug !== '', fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $categorySlug)))
            ->when($query !== '', fn ($q) => $q->where('name', 'like', "%{$query}%"))
            ->orderBy('name')
            ->get();

        $planIds = $session->planIds();

        return Inertia::render('Explore', [
            'categories' => Category::orderBy('sort_order')->get(['slug', 'name']),
            'activeCategory' => $categorySlug,
            'query' => $query,
            'products' => $products->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category->name,
                'price' => $p->cheapestPrice(),
                'inPlan' => in_array($p->id, $planIds, true),
            ])->values(),
        ]);
    }
}
