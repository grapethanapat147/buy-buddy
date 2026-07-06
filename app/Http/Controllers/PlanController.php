<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\PlanSession;
use Illuminate\Http\RedirectResponse;

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
}
