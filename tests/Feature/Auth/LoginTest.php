<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Repositories\PlanRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('logs in and restores the saved plan into the session', function () {
    $user = User::factory()->create(['password' => bcrypt('password123')]);
    $product = Product::factory()->for(Category::factory())->create();
    (new PlanRepository)->save($user, ['budget' => 7000, 'room_type' => 'studio', 'occupants' => 2, 'cooking' => 'often', 'owned_product_ids' => []], [$product->id]);

    $this->post('/login', ['email' => $user->email, 'password' => 'password123'])
        ->assertRedirect(route('plan.show'));

    $this->assertAuthenticatedAs($user);
    expect(session('plan_ids'))->toBe([$product->id]);
    expect(session('spec')['budget'])->toBe(7000);
});

it('rejects bad credentials', function () {
    $user = User::factory()->create(['password' => bcrypt('password123')]);

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
        ->assertSessionHasErrors('email');
});
