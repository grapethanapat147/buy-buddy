<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('lets an authenticated user reach the admin panel', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin')->assertSuccessful();
});

it('redirects guests away from the admin panel', function () {
    $this->get('/admin')->assertRedirect();
});
