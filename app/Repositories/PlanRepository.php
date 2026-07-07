<?php

namespace App\Repositories;

use App\Models\Plan;
use App\Models\User;

class PlanRepository
{
    /**
     * @param  array<string, mixed>  $spec
     * @param  array<int>  $productIds
     */
    public function save(User $user, array $spec, array $productIds): Plan
    {
        $plan = Plan::updateOrCreate(['user_id' => $user->id], ['spec' => $spec]);
        $plan->products()->sync($productIds);

        return $plan;
    }

    public function load(User $user): ?Plan
    {
        return Plan::where('user_id', $user->id)->with('products')->first();
    }
}
