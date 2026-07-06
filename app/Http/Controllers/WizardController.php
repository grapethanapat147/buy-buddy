<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSpecRequest;
use App\Support\PlanSession;
use Inertia\Inertia;
use Inertia\Response;

class WizardController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Wizard');
    }

    public function store(StoreSpecRequest $request, PlanSession $session): \Illuminate\Http\RedirectResponse
    {
        $session->setSpec([
            'budget' => (int) $request->integer('budget'),
            'room_type' => $request->string('room_type')->toString(),
            'occupants' => (int) $request->integer('occupants'),
            'cooking' => $request->string('cooking')->toString(),
            'owned_product_ids' => [],
        ]);

        return redirect()->route('recommendations');
    }
}
