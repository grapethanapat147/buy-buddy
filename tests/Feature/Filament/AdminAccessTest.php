<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lets an authenticated user reach the admin panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin')->assertSuccessful();
});

it('redirects guests away from the admin panel', function () {
    $this->get('/admin')->assertRedirect();
});
