<?php

namespace App\Recommendation;

use App\Enums\ProductTier;

class PlanAdvisor
{
    /**
     * @param  array<array{productId:int,tier:ProductTier,lineTotal:int}>  $lines
     */
    public function summarize(array $lines, int $budget): PlanSummary
    {
        $total = array_sum(array_map(fn (array $l) => $l['lineTotal'], $lines));
        $overBy = max(0, $total - $budget);

        $mustTotal = array_sum(array_map(
            fn (array $l) => $l['lineTotal'],
            array_filter($lines, fn (array $l) => $l['tier'] === ProductTier::Must),
        ));

        if ($mustTotal > $budget) {
            return new PlanSummary($total, $budget, $overBy, true, []);
        }

        $suggested = [];
        if ($overBy > 0) {
            $candidates = array_values(array_filter($lines, fn (array $l) => $l['tier'] !== ProductTier::Must));
            usort($candidates, fn (array $a, array $b) => [$b['tier']->priority(), $b['lineTotal']] <=> [$a['tier']->priority(), $a['lineTotal']]);

            $need = $overBy;
            foreach ($candidates as $line) {
                if ($need <= 0) {
                    break;
                }
                $suggested[] = $line['productId'];
                $need -= $line['lineTotal'];
            }
        }

        return new PlanSummary($total, $budget, $overBy, false, $suggested);
    }
}
