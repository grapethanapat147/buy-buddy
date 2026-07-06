<?php

namespace App\Http\Controllers;

use App\Recommendation\RecommendationService;
use App\Support\PlanSession;
use Inertia\Inertia;
use Inertia\Response;

class RecommendationController extends Controller
{
    public function index(PlanSession $session, RecommendationService $service): Response|\Illuminate\Http\RedirectResponse
    {
        $spec = $session->spec();
        if (! $spec) {
            return redirect()->route('wizard');
        }

        $result = $service->recommend($spec);
        $planIds = $session->planIds();

        $items = collect($result->inPlan())->map(fn ($i) => [
            'productId' => $i->productId,
            'name' => $i->name,
            'tier' => $i->tier->value,
            'lineTotal' => $i->lineTotal,
            'inPlan' => in_array($i->productId, $planIds, true),
        ])->values();

        return Inertia::render('Recommendations', [
            'items' => $items,
            'budget' => $spec->budget,
            'plannedTotal' => $result->plannedTotal(),
        ]);
    }
}
