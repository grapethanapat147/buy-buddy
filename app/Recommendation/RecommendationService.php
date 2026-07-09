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
        return Product::with(['prices', 'category'])
            ->get()
            ->reject(fn (Product $p) => $spec->owns($p->id))
            ->filter(fn (Product $p) => $this->triggers->passes($p->triggers ?? [], $spec))
            ->reject(fn (Product $p) => $spec->spendingStyle === 'essentials' && $p->tier === ProductTier::Optional);
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
            icon: $product->icon,
            category: $product->category->name,
        );
    }

    /**
     * @param  array<RecommendationItem>  $items
     */
    private function fitToBudget(array $items, int $budget): RecommendationResult
    {
        $musts = array_filter($items, fn (RecommendationItem $i) => $i->tier === ProductTier::Must);
        $rest = array_filter($items, fn (RecommendationItem $i) => $i->tier !== ProductTier::Must);

        $mustTotal = array_sum(array_map(fn (RecommendationItem $i) => $i->lineTotal, $musts));

        $fitted = [];
        foreach ($musts as $item) {
            $fitted[] = $this->withStatus($item, 'in_plan');
        }

        if ($mustTotal > $budget) {
            foreach ($rest as $item) {
                $fitted[] = $this->withStatus($item, 'deferred');
            }

            return new RecommendationResult(items: $fitted, budget: $budget, mustExceedsBudget: true);
        }

        $running = $mustTotal;
        foreach ($rest as $item) {
            if ($running + $item->lineTotal <= $budget) {
                $running += $item->lineTotal;
                $fitted[] = $this->withStatus($item, 'in_plan');
            } else {
                $fitted[] = $this->withStatus($item, 'deferred');
            }
        }

        return new RecommendationResult(items: $fitted, budget: $budget, mustExceedsBudget: false);
    }

    private function withStatus(RecommendationItem $item, string $status): RecommendationItem
    {
        return new RecommendationItem(
            productId: $item->productId,
            name: $item->name,
            tier: $item->tier,
            quantity: $item->quantity,
            lineTotal: $item->lineTotal,
            status: $status,
            icon: $item->icon,
            category: $item->category,
        );
    }
}
