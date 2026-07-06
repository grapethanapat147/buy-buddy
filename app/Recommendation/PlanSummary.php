<?php

namespace App\Recommendation;

readonly class PlanSummary
{
    /**
     * @param  array<int>  $suggestedDeferrals
     */
    public function __construct(
        public int $total,
        public int $budget,
        public int $overBudgetBy,
        public bool $mustExceedsBudget,
        public array $suggestedDeferrals,
    ) {}
}
