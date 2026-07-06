<?php

use Inertia\Testing\AssertableInertia as Assert;

it('shows the wizard', function () {
    $this->get('/')->assertInertia(fn (Assert $page) => $page->component('Wizard'));
});

it('stores the spec and redirects to recommendations', function () {
    $this->post('/wizard', ['budget' => 5000, 'room_type' => 'studio', 'occupants' => 1, 'cooking' => 'sometimes'])
        ->assertRedirect(route('recommendations'));

    expect(session('spec'))->toMatchArray(['budget' => 5000, 'cooking' => 'sometimes']);
});

it('validates the spec', function () {
    $this->post('/wizard', ['budget' => 5000, 'room_type' => 'studio', 'occupants' => 1, 'cooking' => 'wrong'])
        ->assertSessionHasErrors('cooking');
});
