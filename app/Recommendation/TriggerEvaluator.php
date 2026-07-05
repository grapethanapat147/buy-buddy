<?php

namespace App\Recommendation;

class TriggerEvaluator
{
    /**
     * @param  array<array{field:string,op:string,value:mixed}>  $triggers
     */
    public function passes(array $triggers, Spec $spec): bool
    {
        foreach ($triggers as $rule) {
            if (! $this->ruleMatches($rule, $spec)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array{field:string,op:string,value:mixed}  $rule
     */
    private function ruleMatches(array $rule, Spec $spec): bool
    {
        $actual = $spec->value($rule['field']);

        return match ($rule['op']) {
            '=' => $actual === $rule['value'],
            '>=' => $actual >= $rule['value'],
            'in' => in_array($actual, $rule['value'], true),
            default => false,
        };
    }
}
