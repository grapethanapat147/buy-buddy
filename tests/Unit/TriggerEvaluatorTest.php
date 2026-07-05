<?php

use App\Recommendation\Spec;
use App\Recommendation\TriggerEvaluator;

function spec(array $overrides = []): Spec
{
    return new Spec(
        budget: $overrides['budget'] ?? 5000,
        roomType: $overrides['roomType'] ?? 'studio',
        occupants: $overrides['occupants'] ?? 1,
        cooking: $overrides['cooking'] ?? 'sometimes',
        ownedProductIds: $overrides['ownedProductIds'] ?? [],
    );
}

it('passes when there are no triggers', function () {
    expect((new TriggerEvaluator)->passes([], spec()))->toBeTrue();
});

it('matches an "in" rule', function () {
    $rules = [['field' => 'cooking', 'op' => 'in', 'value' => ['sometimes', 'often']]];
    expect((new TriggerEvaluator)->passes($rules, spec(['cooking' => 'sometimes'])))->toBeTrue()
        ->and((new TriggerEvaluator)->passes($rules, spec(['cooking' => 'never'])))->toBeFalse();
});

it('matches a ">=" rule on occupants', function () {
    $rules = [['field' => 'occupants', 'op' => '>=', 'value' => 2]];
    expect((new TriggerEvaluator)->passes($rules, spec(['occupants' => 2])))->toBeTrue()
        ->and((new TriggerEvaluator)->passes($rules, spec(['occupants' => 1])))->toBeFalse();
});

it('requires all rules to pass (AND)', function () {
    $rules = [
        ['field' => 'cooking', 'op' => '=', 'value' => 'often'],
        ['field' => 'occupants', 'op' => '>=', 'value' => 2],
    ];
    expect((new TriggerEvaluator)->passes($rules, spec(['cooking' => 'often', 'occupants' => 1])))->toBeFalse();
});
