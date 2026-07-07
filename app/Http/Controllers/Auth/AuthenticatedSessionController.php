<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Repositories\PlanRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request, PlanRepository $plans): RedirectResponse
    {
        if (! Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages(['email' => 'อีเมลหรือรหัสผ่านไม่ถูกต้อง']);
        }

        $request->session()->regenerate();

        $plan = $plans->load(Auth::user());
        if ($plan) {
            $guestIds = $request->session()->get('plan_ids', []);
            $mergedIds = array_values(array_unique([...$plan->products->pluck('id')->all(), ...$guestIds]));

            $request->session()->put('plan_ids', $mergedIds);
            if (! $request->session()->has('spec')) {
                $request->session()->put('spec', $plan->spec);
            }
        }

        return redirect()->route('plan.show');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('wizard');
    }
}
