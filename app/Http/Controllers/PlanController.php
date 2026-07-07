<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Recommendation\PlanAdvisor;
use App\Recommendation\StoreRollup;
use App\Repositories\PlanRepository;
use App\Support\PlanSession;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PlanController extends Controller
{
    public function add(Product $product, PlanSession $session): RedirectResponse
    {
        $session->addToPlan($product->id);

        return back();
    }

    public function remove(Product $product, PlanSession $session): RedirectResponse
    {
        $session->removeFromPlan($product->id);

        return back();
    }

    public function save(PlanSession $session, PlanRepository $plans): RedirectResponse
    {
        $spec = $session->specArray();
        abort_if($spec === null, 400);

        $plans->save(request()->user(), $spec, $session->planIds());

        return back();
    }

    public function show(PlanSession $session, PlanAdvisor $advisor, StoreRollup $rollup): Response|RedirectResponse
    {
        $spec = $session->spec();
        if (! $spec) {
            return redirect()->route('wizard');
        }

        $products = Product::whereIn('id', $session->planIds())->with('prices')->get();

        $rollupLines = $products->map(fn (Product $p) => [
            'product' => $p,
            'qty' => $p->qty_scales_by === 'occupants' ? $spec->occupants : 1,
        ])->all();

        $lines = $products->map(fn (Product $p) => [
            'productId' => $p->id,
            'name' => $p->name,
            'tier' => $p->tier,
            'lineTotal' => $p->cheapestPrice() * ($p->qty_scales_by === 'occupants' ? $spec->occupants : 1),
        ]);

        $summary = $advisor->summarize($lines->all(), $spec->budget);

        return Inertia::render('MyPlan', [
            'items' => $lines->map(fn ($l) => [
                'productId' => $l['productId'],
                'name' => $l['name'],
                'tier' => $l['tier']->value,
                'lineTotal' => $l['lineTotal'],
                'suggested' => in_array($l['productId'], $summary->suggestedDeferrals, true),
            ])->values(),
            'budget' => $spec->budget,
            'total' => $summary->total,
            'overBudgetBy' => $summary->overBudgetBy,
            'mustExceedsBudget' => $summary->mustExceedsBudget,
            'storeRollup' => $rollup->summarize($rollupLines),
        ]);
    }
}
