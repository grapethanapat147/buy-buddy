<?php

use App\Recommendation\Spec;

it('reports whether a product id is already owned', function () {
    $spec = new Spec(budget: 5000, roomType: 'studio', occupants: 1, cooking: 'sometimes', ownedProductIds: [4, 7]);

    expect($spec->owns(4))->toBeTrue()
        ->and($spec->owns(9))->toBeFalse();
});

it('exposes behavioral fields for triggers', function () {
    $spec = new Spec(budget: 5000, roomType: 'studio', occupants: 1, cooking: 'often', laundry: 'hand', workStyle: 'home', spendingStyle: 'comfort');

    expect($spec->value('laundry'))->toBe('hand')
        ->and($spec->value('work_style'))->toBe('home')
        ->and($spec->value('spending_style'))->toBe('comfort');
});
