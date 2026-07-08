<?php

namespace App\Support;

use App\Recommendation\Spec;
use Illuminate\Http\Request;

class PlanSession
{
    public function __construct(private Request $request) {}

    public function hasSpec(): bool
    {
        return $this->request->session()->has('spec');
    }

    public function spec(): ?Spec
    {
        $data = $this->request->session()->get('spec');

        return $data ? new Spec(
            budget: $data['budget'],
            roomType: $data['room_type'],
            occupants: $data['occupants'],
            cooking: $data['cooking'],
            ownedProductIds: $data['owned_product_ids'] ?? [],
            laundry: $data['laundry'] ?? 'own_machine',
            workStyle: $data['work_style'] ?? 'office',
            spendingStyle: $data['spending_style'] ?? 'balanced',
        ) : null;
    }

    /**
     * @param  array{budget:int,room_type:string,occupants:int,cooking:string,owned_product_ids?:array<int>}  $data
     */
    public function setSpec(array $data): void
    {
        $this->request->session()->put('spec', $data);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function specArray(): ?array
    {
        return $this->request->session()->get('spec');
    }

    /**
     * @return array<int>
     */
    public function planIds(): array
    {
        return $this->request->session()->get('plan_ids', []);
    }

    /**
     * @param  array<int>  $ids
     */
    public function setPlanIds(array $ids): void
    {
        $this->request->session()->put('plan_ids', array_values(array_unique($ids)));
    }

    public function addToPlan(int $productId): void
    {
        $ids = array_values(array_unique([...$this->planIds(), $productId]));
        $this->request->session()->put('plan_ids', $ids);
    }

    public function removeFromPlan(int $productId): void
    {
        $ids = array_values(array_filter($this->planIds(), fn ($id) => $id !== $productId));
        $this->request->session()->put('plan_ids', $ids);
    }
}
