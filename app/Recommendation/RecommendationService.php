<?php

namespace App\Recommendation;

use App\Enums\ProductTier;
use App\Models\Product;
use Illuminate\Support\Collection;

class RecommendationService
{
    public function __construct(private TriggerEvaluator $triggers) {}

    public function recommend(Spec $spec): RecommendationResult
    {
        $items = $this->eligible($spec)
            ->map(fn (Product $p) => $this->toItem($p, $spec))
            ->sort(function (RecommendationItem $a, RecommendationItem $b) {
                return [$a->tier->priority(), $a->lineTotal] <=> [$b->tier->priority(), $b->lineTotal];
            })
            ->values();

        return $this->fitToBudget($items->all(), $spec->budget);
    }

    /**
     * @return Collection<int, Product>
     */
    private function eligible(Spec $spec): Collection
    {
        return Product::with('prices')
            ->get()
            ->reject(fn (Product $p) => $spec->owns($p->id))
            ->filter(fn (Product $p) => $this->triggers->passes($p->triggers ?? [], $spec));
    }

    private function toItem(Product $product, Spec $spec): RecommendationItem
    {
        $quantity = $product->qty_scales_by === 'occupants' ? $spec->occupants : 1;

        return new RecommendationItem(
            productId: $product->id,
            name: $product->name,
            tier: $product->tier,
            quantity: $quantity,
            lineTotal: $product->cheapestPrice() * $quantity,
            status: 'in_plan',
        );
    }

    /**
     * @param  array<RecommendationItem>  $items
     */
    private function fitToBudget(array $items, int $budget): RecommendationResult
    {
        return new RecommendationResult(items: $items, budget: $budget, mustExceedsBudget: false);
    }
}
