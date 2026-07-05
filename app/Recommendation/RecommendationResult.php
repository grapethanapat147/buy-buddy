<?php

namespace App\Recommendation;

readonly class RecommendationResult
{
    /**
     * @param  array<RecommendationItem>  $items
     */
    public function __construct(
        public array $items,
        public int $budget,
        public bool $mustExceedsBudget,
    ) {}

    /**
     * @return array<RecommendationItem>
     */
    public function inPlan(): array
    {
        return array_values(array_filter($this->items, fn (RecommendationItem $i) => $i->status === 'in_plan'));
    }

    /**
     * @return array<RecommendationItem>
     */
    public function deferred(): array
    {
        return array_values(array_filter($this->items, fn (RecommendationItem $i) => $i->status === 'deferred'));
    }

    public function plannedTotal(): int
    {
        return array_sum(array_map(fn (RecommendationItem $i) => $i->lineTotal, $this->inPlan()));
    }

    public function overBudgetBy(): int
    {
        return max(0, $this->plannedTotal() - $this->budget);
    }
}
