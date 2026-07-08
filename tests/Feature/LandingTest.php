<?php

use Inertia\Testing\AssertableInertia as Assert;

it('shows the landing page at the root', function () {
    $this->get('/')->assertInertia(fn (Assert $page) => $page->component('Landing'));
});

it('serves the wizard at /wizard', function () {
    $this->get('/wizard')->assertInertia(fn (Assert $page) => $page->component('Wizard'));
});
