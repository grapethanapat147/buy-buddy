<?php

use App\Models\Category;
use App\Models\Product;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('registers a guest and saves their session plan without losing it', function () {
    $product = Product::factory()->for(Category::factory())->create();
    session([
        'spec' => ['budget' => 5000, 'room_type' => 'studio', 'occupants' => 1, 'cooking' => 'sometimes', 'owned_product_ids' => []],
        'plan_ids' => [$product->id],
    ]);

    $this->post('/register', [
        'name' => 'Fah', 'email' => 'fah@example.com',
        'password' => 'password123', 'password_confirmation' => 'password123',
    ])->assertRedirect(route('plan.show'));

    $this->assertAuthenticated();
    $user = \App\Models\User::where('email', 'fah@example.com')->firstOrFail();
    $plan = \App\Models\Plan::where('user_id', $user->id)->first();
    expect($plan)->not->toBeNull();
    expect($plan->products->pluck('id')->all())->toBe([$product->id]);
    // lossless: session plan still present
    expect(session('plan_ids'))->toBe([$product->id]);
});
