<?php

use Inertia\Testing\AssertableInertia as Assert;

it('shows the wizard', function () {
    $this->get('/wizard')->assertInertia(fn (Assert $page) => $page->component('Wizard'));
});

it('stores the spec and redirects to recommendations', function () {
    $this->post('/wizard', [
        'budget' => 5000, 'room_type' => 'studio', 'occupants' => 1, 'cooking' => 'sometimes',
        'laundry' => 'own_machine', 'work_style' => 'office', 'spending_style' => 'balanced',
    ])->assertRedirect(route('recommendations'));

    expect(session('spec'))->toMatchArray(['budget' => 5000, 'cooking' => 'sometimes', 'work_style' => 'office']);
});

it('validates the spec', function () {
    $this->post('/wizard', [
        'budget' => 5000, 'room_type' => 'studio', 'occupants' => 1, 'cooking' => 'sometimes',
        'laundry' => 'own_machine', 'work_style' => 'office', 'spending_style' => 'nope',
    ])->assertSessionHasErrors('spending_style');
});
