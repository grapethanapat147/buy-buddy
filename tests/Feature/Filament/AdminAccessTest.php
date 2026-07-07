<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('lets an admin reach the admin panel', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertSuccessful();
});

it('forbids a non-admin from the admin panel', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

it('redirects guests away from the admin panel', function () {
    $this->get('/admin')->assertRedirect();
});
