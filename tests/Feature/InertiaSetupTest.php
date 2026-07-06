<?php

use Inertia\Testing\AssertableInertia as Assert;

it('renders an inertia page', function () {
    $this->get('/__ping')->assertInertia(fn (Assert $page) => $page->component('Ping')->where('ok', true));
});
