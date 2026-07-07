<?php

use App\Models\Category;
use App\Models\Plan;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

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
    $user = User::where('email', 'fah@example.com')->firstOrFail();
    $plan = Plan::where('user_id', $user->id)->first();
    expect($plan)->not->toBeNull();
    expect($plan->products->pluck('id')->all())->toBe([$product->id]);
    // lossless: session plan still present
    expect(session('plan_ids'))->toBe([$product->id]);
});
