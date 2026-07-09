<?php

namespace App\Http\Controllers;

use App\Enums\ProductTier;
use App\Recommendation\RecommendationService;
use App\Support\PlanSession;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RecommendationController extends Controller
{
    public function index(PlanSession $session, RecommendationService $service): Response|RedirectResponse
    {
        $spec = $session->spec();
        if (! $spec) {
            return redirect()->route('wizard');
        }

        $result = $service->recommend($spec);
        $planIds = $session->planIds();
        $items = collect($result->items);

        $categories = $items->groupBy('category')->map(function ($group, $name) use ($planIds) {
            $rows = $group->map(fn ($i) => [
                'productId' => $i->productId,
                'name' => $i->name,
                'icon' => $i->icon,
                'tier' => $i->tier->value,
                'lineTotal' => $i->lineTotal,
                'inPlan' => in_array($i->productId, $planIds, true),
            ])->values();

            return [
                'name' => $name,
                'total' => $rows->count(),
                'collected' => $rows->where('inPlan', true)->count(),
                'items' => $rows,
            ];
        })->values();

        $musts = $items->where('tier', ProductTier::Must);
        $mustsInPlan = $musts->filter(fn ($i) => in_array($i->productId, $planIds, true))->count();
        $readiness = [
            'collected' => $mustsInPlan,
            'total' => $musts->count(),
            'percent' => $musts->count() ? (int) round($mustsInPlan / $musts->count() * 100) : 0,
        ];

        return Inertia::render('Recommendations', [
            'categories' => $categories,
            'budget' => $spec->budget,
            'plannedTotal' => $result->plannedTotal(),
            'readiness' => $readiness,
        ]);
    }
}
