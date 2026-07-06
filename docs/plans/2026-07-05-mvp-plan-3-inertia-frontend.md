# Grocery List Web App — MVP Plan 3: Inertia React Frontend Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Turn the six wireframe screens into a working Inertia + React frontend, wired to the real `RecommendationService`, with guest-session plan state and budget handling.

**Architecture:** Laravel serves Inertia pages rendered by React (Vite + Tailwind). The user's spec and chosen plan live in the session (guest-first). `RecommendationService` (Plan 1) produces recommendations; a new pure `PlanAdvisor` computes budget status + suggested deferrals for the user's *chosen* basket (which may exceed budget — the wireframe scenario). Controllers stay thin; all money/tier logic is in tested services.

**Tech Stack:** Inertia (laravel + react adapter v1), React 18, Vite, Tailwind 3, Pest 3 (Inertia assertions).

**Depends on:** Plan 1 (engine + models) and Plan 2 (admin). Visual source of truth: `docs/wireframes/index.html` — port its markup/colors into Tailwind React components.

**Scope (this plan):** Wizard, Recommendations, Product Detail, My Plan (in-budget / manual-defer over-budget / must-exceeds-budget). Guest→account saving is Plan 4. Calendar/Restock tab and Explore are follow-ons.

---

## File Structure (Plan 3)

- `resources/views/app.blade.php`, `resources/js/app.jsx`, `resources/css/app.css`, `vite.config.js`, `tailwind.config.js` — Inertia/React/Tailwind setup
- `app/Http/Middleware/HandleInertiaRequests.php` — shares `plan_count`, `spec` flags
- `app/Recommendation/PlanAdvisor.php` + `PlanSummary.php` — budget status for a chosen basket
- `app/Http/Controllers/{WizardController,RecommendationController,ProductController,PlanController}.php`
- `app/Http/Requests/StoreSpecRequest.php`
- `app/Support/PlanSession.php` — session plan helpers (add/remove/ids)
- `resources/js/Pages/{Wizard,Recommendations,ProductDetail,MyPlan}.jsx`, `resources/js/Layouts/AppLayout.jsx`, `resources/js/Components/BudgetMeter.jsx`
- `routes/web.php`
- `tests/Feature/*` + `tests/Unit/PlanAdvisorTest.php`

---

## Task 1: Install Inertia + React + Tailwind

**Files:** setup files listed above.

- [ ] **Step 1: Server + client deps**

```bash
cd /Users/grapetnp/Herd/grocery-list-webapp
composer require inertiajs/inertia-laravel --no-interaction
php artisan inertia:middleware
npm install
npm install --save-dev @inertiajs/react react react-dom @vitejs/plugin-react
npm install --save-dev tailwindcss@^3 postcss autoprefixer
npx tailwindcss init -p
```

- [ ] **Step 2: Register the Inertia middleware.** In `bootstrap/app.php`, inside `->withMiddleware(function (Middleware $middleware) {`:

```php
$middleware->web(append: [
    \App\Http\Middleware\HandleInertiaRequests::class,
]);
```

- [ ] **Step 3: Root Blade view** `resources/views/app.blade.php`:

```blade
<!DOCTYPE html>
<html lang="th" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @vite(['resources/css/app.css', 'resources/js/app.jsx'])
    @inertiaHead
</head>
<body class="h-full bg-neutral-50 text-neutral-800 antialiased">
    @inertia
</body>
</html>
```

- [ ] **Step 4: CSS** `resources/css/app.css`:

```css
@tailwind base;
@tailwind components;
@tailwind utilities;
```

- [ ] **Step 5: Tailwind config** `tailwind.config.js`:

```js
export default {
    content: [
        './resources/views/**/*.blade.php',
        './resources/js/**/*.jsx',
    ],
    theme: { extend: {} },
    plugins: [],
};
```

- [ ] **Step 6: Vite config** `vite.config.js`:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({ input: ['resources/css/app.css', 'resources/js/app.jsx'], refresh: true }),
        react(),
    ],
});
```

- [ ] **Step 7: React entry** `resources/js/app.jsx`:

```jsx
import { createInertiaApp } from '@inertiajs/react';
import { createRoot } from 'react-dom/client';
import './bootstrap';

createInertiaApp({
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });
        return pages[`./Pages/${name}.jsx`];
    },
    setup({ el, App, props }) {
        createRoot(el).render(<App {...props} />);
    },
});
```

(If `resources/js/bootstrap.js` does not exist, create it empty or remove the import.)

- [ ] **Step 8: Temporary smoke route + page.** In `routes/web.php`:

```php
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/__ping', fn () => Inertia::render('Ping', ['ok' => true]));
```

Create `resources/js/Pages/Ping.jsx`:

```jsx
export default function Ping({ ok }) {
    return <div>ping {ok ? 'ok' : 'no'}</div>;
}
```

- [ ] **Step 9: Build + smoke test**

```bash
cd /Users/grapetnp/Herd/grocery-list-webapp && npm run build
```
Expected: build succeeds, manifest written.

Test `tests/Feature/InertiaSetupTest.php`:

```php
<?php

use Inertia\Testing\AssertableInertia as Assert;

it('renders an inertia page', function () {
    $this->get('/__ping')->assertInertia(fn (Assert $page) => $page->component('Ping')->where('ok', true));
});
```

Run: `./vendor/bin/pest tests/Feature/InertiaSetupTest.php`
Expected: PASS.

- [ ] **Step 10: Commit**

```bash
git add -A && git commit -m "chore: set up Inertia, React, and Tailwind"
```

---

## Task 2: PlanAdvisor — budget status for a chosen basket

The user's chosen basket may exceed budget (they added a bundle). `PlanAdvisor` computes total, over-budget amount, whether musts alone exceed budget, and which lowest-priority non-must items to suggest deferring to get back under budget.

**Files:**
- Create: `app/Recommendation/PlanSummary.php`, `app/Recommendation/PlanAdvisor.php`
- Test: `tests/Unit/PlanAdvisorTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php

use App\Enums\ProductTier;
use App\Recommendation\PlanAdvisor;

function line(int $id, ProductTier $tier, int $total): array
{
    return ['productId' => $id, 'tier' => $tier, 'lineTotal' => $total];
}

it('reports in-budget with no suggestions when total fits', function () {
    $summary = (new PlanAdvisor)->summarize([
        line(1, ProductTier::Must, 800),
        line(2, ProductTier::Optional, 150),
    ], budget: 1000);

    expect($summary->total)->toBe(950)
        ->and($summary->overBudgetBy)->toBe(0)
        ->and($summary->mustExceedsBudget)->toBeFalse()
        ->and($summary->suggestedDeferrals)->toBe([]);
});

it('suggests deferring lowest-priority non-must items until back in budget', function () {
    $summary = (new PlanAdvisor)->summarize([
        line(1, ProductTier::Must, 800),
        line(2, ProductTier::Optional, 250),
        line(3, ProductTier::Recommended, 120),
    ], budget: 1000);

    // total 1170, over by 170. Defer optional (250) first -> back under budget.
    expect($summary->overBudgetBy)->toBe(170)
        ->and($summary->mustExceedsBudget)->toBeFalse()
        ->and($summary->suggestedDeferrals)->toBe([2]);
});

it('flags when musts alone exceed budget and suggests nothing', function () {
    $summary = (new PlanAdvisor)->summarize([
        line(1, ProductTier::Must, 2000),
        line(2, ProductTier::Must, 1500),
    ], budget: 3000);

    expect($summary->overBudgetBy)->toBe(500)
        ->and($summary->mustExceedsBudget)->toBeTrue()
        ->and($summary->suggestedDeferrals)->toBe([]);
});
```

- [ ] **Step 2: Run — expect FAIL**

Run: `./vendor/bin/pest tests/Unit/PlanAdvisorTest.php`
Expected: FAIL ("Class App\Recommendation\PlanAdvisor not found").

- [ ] **Step 3: Write `PlanSummary`**

```php
<?php

namespace App\Recommendation;

readonly class PlanSummary
{
    /**
     * @param  array<int>  $suggestedDeferrals
     */
    public function __construct(
        public int $total,
        public int $budget,
        public int $overBudgetBy,
        public bool $mustExceedsBudget,
        public array $suggestedDeferrals,
    ) {}
}
```

- [ ] **Step 4: Write `PlanAdvisor`**

```php
<?php

namespace App\Recommendation;

use App\Enums\ProductTier;

class PlanAdvisor
{
    /**
     * @param  array<array{productId:int,tier:ProductTier,lineTotal:int}>  $lines
     */
    public function summarize(array $lines, int $budget): PlanSummary
    {
        $total = array_sum(array_map(fn (array $l) => $l['lineTotal'], $lines));
        $overBy = max(0, $total - $budget);

        $mustTotal = array_sum(array_map(
            fn (array $l) => $l['lineTotal'],
            array_filter($lines, fn (array $l) => $l['tier'] === ProductTier::Must),
        ));

        if ($mustTotal > $budget) {
            return new PlanSummary($total, $budget, $overBy, true, []);
        }

        $suggested = [];
        if ($overBy > 0) {
            $candidates = array_values(array_filter($lines, fn (array $l) => $l['tier'] !== ProductTier::Must));
            usort($candidates, fn (array $a, array $b) => [$b['tier']->priority(), $b['lineTotal']] <=> [$a['tier']->priority(), $a['lineTotal']]);

            $need = $overBy;
            foreach ($candidates as $line) {
                if ($need <= 0) {
                    break;
                }
                $suggested[] = $line['productId'];
                $need -= $line['lineTotal'];
            }
        }

        return new PlanSummary($total, $budget, $overBy, false, $suggested);
    }
}
```

- [ ] **Step 5: Run — expect PASS**

Run: `./vendor/bin/pest tests/Unit/PlanAdvisorTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat: add PlanAdvisor for chosen-basket budget status"
```

---

## Task 3: Session helpers + Wizard

**Files:**
- Create: `app/Support/PlanSession.php`, `app/Http/Requests/StoreSpecRequest.php`, `app/Http/Controllers/WizardController.php`, `resources/js/Layouts/AppLayout.jsx`, `resources/js/Components/BudgetMeter.jsx`, `resources/js/Pages/Wizard.jsx`
- Modify: `routes/web.php`, `app/Http/Middleware/HandleInertiaRequests.php`
- Test: `tests/Feature/WizardTest.php`

- [ ] **Step 1: PlanSession helper** `app/Support/PlanSession.php`:

```php
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
     * @return array<int>
     */
    public function planIds(): array
    {
        return $this->request->session()->get('plan_ids', []);
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
```

- [ ] **Step 2: Share plan count + spec flag** in `app/Http/Middleware/HandleInertiaRequests.php` `share()`:

```php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'planCount' => count($request->session()->get('plan_ids', [])),
        'hasSpec' => $request->session()->has('spec'),
    ];
}
```

- [ ] **Step 3: Form request** `app/Http/Requests/StoreSpecRequest.php`:

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSpecRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'budget' => ['required', 'integer', 'min:0'],
            'room_type' => ['required', 'string'],
            'occupants' => ['required', 'integer', 'min:1'],
            'cooking' => ['required', 'in:never,sometimes,often'],
        ];
    }
}
```

- [ ] **Step 4: WizardController** `app/Http/Controllers/WizardController.php`:

```php
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
```

- [ ] **Step 5: Routes** — replace the `/__ping` block in `routes/web.php`:

```php
<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\WizardController;
use App\Http\Controllers\PlanController;
use Illuminate\Support\Facades\Route;

Route::get('/', [WizardController::class, 'show'])->name('wizard');
Route::post('/wizard', [WizardController::class, 'store'])->name('wizard.store');
Route::get('/recommendations', [RecommendationController::class, 'index'])->name('recommendations');
Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::post('/plan/items/{product}', [PlanController::class, 'add'])->name('plan.add');
Route::delete('/plan/items/{product}', [PlanController::class, 'remove'])->name('plan.remove');
Route::get('/plan', [PlanController::class, 'show'])->name('plan.show');
```

(Delete `resources/js/Pages/Ping.jsx`.)

- [ ] **Step 6: Shared React pieces.** `resources/js/Components/BudgetMeter.jsx`:

```jsx
export default function BudgetMeter({ total, budget }) {
    const over = total > budget;
    const pct = Math.min(100, Math.round((total / budget) * 100));
    return (
        <div>
            <div className="flex justify-between text-sm">
                <span className="text-neutral-500">งบของฉัน</span>
                <span>
                    <span className={over ? 'font-medium text-rose-600' : 'font-medium text-emerald-600'}>
                        ฿{total.toLocaleString()}
                    </span>{' '}/ ฿{budget.toLocaleString()}
                </span>
            </div>
            <div className="mt-1 h-2.5 overflow-hidden rounded-full bg-neutral-100">
                <div className={`h-full ${over ? 'bg-rose-500' : 'bg-emerald-500'}`} style={{ width: `${pct}%` }} />
            </div>
        </div>
    );
}
```

`resources/js/Layouts/AppLayout.jsx`:

```jsx
import { Link, usePage } from '@inertiajs/react';

export default function AppLayout({ children }) {
    const { planCount } = usePage().props;
    return (
        <div className="mx-auto max-w-xl p-4">
            <header className="mb-4 flex items-center justify-between">
                <Link href="/" className="font-medium">Grocery List</Link>
                <Link href="/plan" className="text-sm text-neutral-600">กระเป๋า ({planCount ?? 0})</Link>
            </header>
            <main className="rounded-xl border border-neutral-200 bg-white p-4">{children}</main>
        </div>
    );
}
```

- [ ] **Step 7: Wizard page** `resources/js/Pages/Wizard.jsx` (port the wizard wireframe; single cooking step for MVP plus budget):

```jsx
import { useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Wizard() {
    const { data, setData, post, processing } = useForm({
        budget: 5000, room_type: 'studio', occupants: 1, cooking: 'sometimes',
    });

    const submit = (e) => { e.preventDefault(); post('/wizard'); };
    const cook = [
        { v: 'never', label: 'ไม่ทำเลย' },
        { v: 'sometimes', label: 'ทำบ้าง' },
        { v: 'often', label: 'ทำบ่อย' },
    ];

    return (
        <AppLayout>
            <form onSubmit={submit}>
                <h1 className="text-lg font-medium">ตั้งค่าห้องของคุณ</h1>
                <label className="mt-4 block text-sm text-neutral-600">งบประมาณ (฿)</label>
                <input type="number" value={data.budget} onChange={(e) => setData('budget', Number(e.target.value))}
                    className="mt-1 w-full rounded-lg border border-neutral-200 p-2" />
                <label className="mt-4 block text-sm text-neutral-600">อยู่กี่คน</label>
                <input type="number" min="1" value={data.occupants} onChange={(e) => setData('occupants', Number(e.target.value))}
                    className="mt-1 w-full rounded-lg border border-neutral-200 p-2" />
                <p className="mt-4 text-sm text-neutral-600">ทำอาหารเองบ่อยแค่ไหน</p>
                <div className="mt-2 space-y-2">
                    {cook.map((c) => (
                        <button type="button" key={c.v} onClick={() => setData('cooking', c.v)}
                            className={`block w-full rounded-xl border p-3 text-left ${data.cooking === c.v ? 'border-2 border-sky-500 bg-sky-50' : 'border-neutral-200'}`}>
                            {c.label}
                        </button>
                    ))}
                </div>
                <button type="submit" disabled={processing}
                    className="mt-6 w-full rounded-lg bg-neutral-800 p-3 font-medium text-white">
                    ดูชุดของแนะนำ
                </button>
            </form>
        </AppLayout>
    );
}
```

Add the `@` alias in `vite.config.js` resolve (so `@/Layouts/...` works):

```js
resolve: { alias: { '@': '/resources/js' } },
```

- [ ] **Step 8: Test** `tests/Feature/WizardTest.php`:

```php
<?php

use Inertia\Testing\AssertableInertia as Assert;

it('shows the wizard', function () {
    $this->get('/')->assertInertia(fn (Assert $page) => $page->component('Wizard'));
});

it('stores the spec and redirects to recommendations', function () {
    $this->post('/wizard', ['budget' => 5000, 'room_type' => 'studio', 'occupants' => 1, 'cooking' => 'sometimes'])
        ->assertRedirect(route('recommendations'));

    expect(session('spec'))->toMatchArray(['budget' => 5000, 'cooking' => 'sometimes']);
});

it('validates the spec', function () {
    $this->post('/wizard', ['budget' => 5000, 'room_type' => 'studio', 'occupants' => 1, 'cooking' => 'wrong'])
        ->assertSessionHasErrors('cooking');
});
```

- [ ] **Step 9: Build + test**

```bash
cd /Users/grapetnp/Herd/grocery-list-webapp && npm run build && ./vendor/bin/pest tests/Feature/WizardTest.php
```
Expected: build ok, tests PASS.

- [ ] **Step 10: Commit**

```bash
git add -A && git commit -m "feat: wizard collects spec into the session"
```

---

## Task 4: Recommendations page

**Files:**
- Create: `app/Http/Controllers/RecommendationController.php`, `app/Http/Controllers/PlanController.php` (add/remove only here; show in Task 6), `resources/js/Pages/Recommendations.jsx`
- Test: `tests/Feature/RecommendationsPageTest.php`

- [ ] **Step 1: RecommendationController** `app/Http/Controllers/RecommendationController.php`:

```php
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
```

- [ ] **Step 2: PlanController add/remove** `app/Http/Controllers/PlanController.php`:

```php
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
```

- [ ] **Step 3: Recommendations page** `resources/js/Pages/Recommendations.jsx`:

```jsx
import { Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import BudgetMeter from '@/Components/BudgetMeter';

const tierBadge = {
    must: 'bg-rose-50 text-rose-700',
    recommended: 'bg-amber-50 text-amber-700',
    optional: 'bg-neutral-100 text-neutral-600',
};
const tierLabel = { must: 'จำเป็น', recommended: 'แนะนำ', optional: 'ถ้ามีงบ' };

export default function Recommendations({ items, budget, plannedTotal }) {
    return (
        <AppLayout>
            <h1 className="text-lg font-medium">ชุดของแนะนำ</h1>
            <div className="my-3"><BudgetMeter total={plannedTotal} budget={budget} /></div>
            <div className="space-y-2">
                {items.map((it) => (
                    <div key={it.productId} className="flex items-center gap-3 rounded-xl border border-neutral-200 p-3">
                        <div className="flex-1">
                            <Link href={`/products/${it.productId}`} className="text-sm font-medium">{it.name}</Link>
                            <div className="mt-1 text-xs text-neutral-500">฿{it.lineTotal.toLocaleString()}</div>
                            <span className={`mt-1 inline-block rounded-full px-2 py-0.5 text-[11px] ${tierBadge[it.tier]}`}>{tierLabel[it.tier]}</span>
                        </div>
                        {it.inPlan ? (
                            <button onClick={() => router.delete(`/plan/items/${it.productId}`, { preserveScroll: true })}
                                className="h-9 w-9 rounded-full bg-emerald-50 text-emerald-600">✓</button>
                        ) : (
                            <button onClick={() => router.post(`/plan/items/${it.productId}`, {}, { preserveScroll: true })}
                                className="h-9 w-9 rounded-full border border-neutral-300">+</button>
                        )}
                    </div>
                ))}
            </div>
            <Link href="/plan" className="mt-4 block rounded-lg bg-neutral-800 p-3 text-center font-medium text-white">
                ดูแผนของฉัน
            </Link>
        </AppLayout>
    );
}
```

- [ ] **Step 4: Test** `tests/Feature/RecommendationsPageTest.php`:

```php
<?php

use App\Enums\ProductTier;
use App\Models\Category;
use App\Models\Product;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function withSpec(): void
{
    session(['spec' => ['budget' => 5000, 'room_type' => 'studio', 'occupants' => 1, 'cooking' => 'sometimes', 'owned_product_ids' => []]]);
}

it('redirects to the wizard when there is no spec', function () {
    $this->get('/recommendations')->assertRedirect(route('wizard'));
});

it('renders recommended items for the session spec', function () {
    $p = Product::factory()->for(Category::factory())->create(['tier' => ProductTier::Must, 'ref_price' => 500, 'triggers' => []]);
    withSpec();

    $this->get('/recommendations')->assertInertia(fn (Assert $page) => $page
        ->component('Recommendations')
        ->where('budget', 5000)
        ->has('items', 1, fn (Assert $item) => $item->where('productId', $p->id)->etc()));
});

it('adds a product to the session plan', function () {
    $p = Product::factory()->for(Category::factory())->create();
    withSpec();

    $this->post("/plan/items/{$p->id}")->assertRedirect();
    expect(session('plan_ids'))->toContain($p->id);
});
```

- [ ] **Step 5: Build + test, then commit**

```bash
cd /Users/grapetnp/Herd/grocery-list-webapp && npm run build && ./vendor/bin/pest tests/Feature/RecommendationsPageTest.php
git add -A && git commit -m "feat: recommendations page wired to the engine"
```

---

## Task 5: Product Detail page

**Files:**
- Create: `app/Http/Controllers/ProductController.php`, `resources/js/Pages/ProductDetail.jsx`
- Test: `tests/Feature/ProductDetailTest.php`

- [ ] **Step 1: ProductController** `app/Http/Controllers/ProductController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

class ProductController extends Controller
{
    public function show(Product $product): Response
    {
        $product->load(['prices', 'pairedProducts']);

        $cheapest = $product->prices->sortBy('price')->first();

        return Inertia::render('ProductDetail', [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'tier' => $product->tier->value,
                'cheapest' => $cheapest ? ['platform' => $cheapest->platform, 'price' => $cheapest->price] : ['platform' => null, 'price' => $product->cheapestPrice()],
                'otherStoreCount' => max(0, $product->prices->count() - 1),
            ],
            'bundle' => $product->pairedProducts->map(fn (Product $p) => [
                'id' => $p->id, 'name' => $p->name, 'price' => $p->cheapestPrice(),
            ])->values(),
        ]);
    }
}
```

- [ ] **Step 2: Product Detail page** `resources/js/Pages/ProductDetail.jsx`:

```jsx
import { router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function ProductDetail({ product, bundle }) {
    const bundleTotal = bundle.reduce((s, b) => s + b.price, 0);
    return (
        <AppLayout>
            <h1 className="text-lg font-medium">{product.name}</h1>
            <div className="mt-3 rounded-xl border border-neutral-300">
                <div className="flex items-center justify-between p-3">
                    <span className="text-sm text-neutral-500">เทียบราคา</span>
                    <span className="text-xs text-neutral-400">ราคาอ้างอิง</span>
                </div>
                <div className="flex items-center justify-between border-t border-neutral-200 bg-emerald-50 p-3">
                    <span className="text-sm font-medium">{product.cheapest.platform ?? 'ราคาอ้างอิง'} · คุ้มสุด</span>
                    <span className="font-medium">฿{product.cheapest.price.toLocaleString()}</span>
                </div>
                {product.otherStoreCount > 0 && (
                    <div className="border-t border-neutral-200 p-2 text-center text-sm text-neutral-500">
                        ดูอีก {product.otherStoreCount} ร้าน
                    </div>
                )}
            </div>

            {bundle.length > 0 && (
                <>
                    <p className="mt-4 text-sm font-medium">มักซื้อคู่กับ</p>
                    <div className="mt-2 rounded-xl border border-neutral-200">
                        {bundle.map((b) => (
                            <div key={b.id} className="flex items-center justify-between border-b border-neutral-100 p-3 last:border-0">
                                <span className="text-sm">{b.name}</span>
                                <span className="text-sm text-neutral-500">฿{b.price.toLocaleString()}</span>
                            </div>
                        ))}
                        <div className="flex items-center justify-between bg-neutral-50 p-3">
                            <span className="text-sm text-neutral-500">ทั้งชุด · ฿{bundleTotal.toLocaleString()}</span>
                            <button onClick={() => bundle.forEach((b) => router.post(`/plan/items/${b.id}`, {}, { preserveScroll: true }))}
                                className="rounded-lg border border-neutral-300 px-3 py-1.5 text-sm">ใส่ทั้งชุด</button>
                        </div>
                    </div>
                </>
            )}

            <button onClick={() => router.post(`/plan/items/${product.id}`, {}, { preserveScroll: true })}
                className="mt-4 w-full rounded-lg bg-neutral-800 p-3 font-medium text-white">+ ใส่ลงแผน</button>
        </AppLayout>
    );
}
```

- [ ] **Step 3: Test** `tests/Feature/ProductDetailTest.php`:

```php
<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('shows the cheapest price and bundle', function () {
    $product = Product::factory()->for(Category::factory())->create(['ref_price' => 999]);
    ProductPrice::factory()->for($product)->create(['platform' => 'Shopee', 'price' => 590]);
    ProductPrice::factory()->for($product)->create(['platform' => 'Lazada', 'price' => 620]);
    $rice = Product::factory()->for(Category::factory())->create(['ref_price' => 180]);
    $product->pairedProducts()->attach($rice->id);

    $this->get("/products/{$product->id}")->assertInertia(fn (Assert $page) => $page
        ->component('ProductDetail')
        ->where('product.cheapest.price', 590)
        ->where('product.otherStoreCount', 1)
        ->has('bundle', 1));
});
```

- [ ] **Step 4: Build + test, then commit**

```bash
cd /Users/grapetnp/Herd/grocery-list-webapp && npm run build && ./vendor/bin/pest tests/Feature/ProductDetailTest.php
git add -A && git commit -m "feat: product detail page with price comparison and bundle"
```

---

## Task 6: My Plan page (in-budget + over-budget states)

The chosen basket (session `plan_ids`) is summarized by `PlanAdvisor`. The page renders the correct wireframe state from the summary.

**Files:**
- Modify: `app/Http/Controllers/PlanController.php` (add `show`)
- Create: `resources/js/Pages/MyPlan.jsx`
- Test: `tests/Feature/MyPlanTest.php`

- [ ] **Step 1: Add `show()` to PlanController**

```php
use App\Recommendation\PlanAdvisor;
use App\Recommendation\Spec;
use Inertia\Inertia;
use Inertia\Response;
```

```php
public function show(PlanSession $session, PlanAdvisor $advisor): Response|\Illuminate\Http\RedirectResponse
{
    $spec = $session->spec();
    if (! $spec) {
        return redirect()->route('wizard');
    }

    $products = Product::whereIn('id', $session->planIds())->with('prices')->get();

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
    ]);
}
```

(`Product` is already imported in the controller from Task 4; keep it.)

- [ ] **Step 2: My Plan page** `resources/js/Pages/MyPlan.jsx`:

```jsx
import { router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import BudgetMeter from '@/Components/BudgetMeter';

const tierLabel = { must: 'จำเป็น', recommended: 'แนะนำ', optional: 'ถ้ามีงบ' };

export default function MyPlan({ items, budget, total, overBudgetBy, mustExceedsBudget }) {
    const over = overBudgetBy > 0;
    return (
        <AppLayout>
            <h1 className="text-lg font-medium">แผนของฉัน</h1>
            <div className="my-3"><BudgetMeter total={total} budget={budget} /></div>

            {over && mustExceedsBudget && (
                <div className="mb-3 rounded-lg bg-neutral-100 p-3 text-sm text-neutral-700">
                    ของจำเป็นล้วน ๆ ก็เกินงบ ฿{overBudgetBy.toLocaleString()} — เราไม่ตัดของจำเป็นให้ ลองเปลี่ยนรุ่นถูกกว่า หรือแบ่งซื้อข้ามเดือน
                </div>
            )}
            {over && !mustExceedsBudget && (
                <div className="mb-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                    เกินงบ ฿{overBudgetBy.toLocaleString()} — ลองเลื่อนของที่ไฮไลต์ไว้ไปซื้อรอบหน้า
                </div>
            )}
            {!over && (
                <div className="mb-3 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-700">
                    อยู่ในงบ · เหลือ ฿{(budget - total).toLocaleString()}
                </div>
            )}

            <div className="divide-y divide-neutral-100">
                {items.map((it) => (
                    <div key={it.productId} className={`flex items-center gap-3 py-3 ${it.suggested ? 'rounded-lg bg-amber-50 px-2' : ''}`}>
                        <div className="flex-1">
                            <div className="text-sm">{it.name}</div>
                            <div className="text-xs text-neutral-400">{tierLabel[it.tier]}</div>
                        </div>
                        <span className="text-sm text-neutral-500">฿{it.lineTotal.toLocaleString()}</span>
                        {it.tier !== 'must' && (
                            <button onClick={() => router.delete(`/plan/items/${it.productId}`, { preserveScroll: true })}
                                className="rounded-lg border border-neutral-300 px-2 py-1 text-xs">เลื่อนออก</button>
                        )}
                    </div>
                ))}
            </div>
            {items.length === 0 && <p className="py-6 text-center text-sm text-neutral-400">ยังไม่มีของในแผน</p>}
        </AppLayout>
    );
}
```

- [ ] **Step 3: Test** `tests/Feature/MyPlanTest.php`:

```php
<?php

use App\Enums\ProductTier;
use App\Models\Category;
use App\Models\Product;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function planWith(array $ids): void
{
    session([
        'spec' => ['budget' => 1000, 'room_type' => 'studio', 'occupants' => 1, 'cooking' => 'sometimes', 'owned_product_ids' => []],
        'plan_ids' => $ids,
    ]);
}

it('reports in-budget when the chosen basket fits', function () {
    $p = Product::factory()->for(Category::factory())->create(['tier' => ProductTier::Must, 'ref_price' => 800]);
    planWith([$p->id]);

    $this->get('/plan')->assertInertia(fn (Assert $page) => $page
        ->component('MyPlan')->where('total', 800)->where('overBudgetBy', 0)->where('mustExceedsBudget', false));
});

it('flags over-budget and suggests deferring an optional item', function () {
    $must = Product::factory()->for(Category::factory())->create(['tier' => ProductTier::Must, 'ref_price' => 800]);
    $opt = Product::factory()->for(Category::factory())->create(['tier' => ProductTier::Optional, 'ref_price' => 300]);
    planWith([$must->id, $opt->id]);

    $this->get('/plan')->assertInertia(fn (Assert $page) => $page
        ->where('overBudgetBy', 100)
        ->where('mustExceedsBudget', false)
        ->has('items', 2));
});

it('flags must-exceeds-budget', function () {
    $a = Product::factory()->for(Category::factory())->create(['tier' => ProductTier::Must, 'ref_price' => 700]);
    $b = Product::factory()->for(Category::factory())->create(['tier' => ProductTier::Must, 'ref_price' => 500]);
    planWith([$a->id, $b->id]);

    $this->get('/plan')->assertInertia(fn (Assert $page) => $page->where('mustExceedsBudget', true)->where('overBudgetBy', 200));
});
```

- [ ] **Step 4: Build, full suite, Pint, commit**

```bash
cd /Users/grapetnp/Herd/grocery-list-webapp && npm run build && ./vendor/bin/pest && ./vendor/bin/pint
git add -A && git commit -m "feat: my plan page with budget states"
```
If Pint changed files, commit `style: pint`.

---

## Self-Review (Plan 3)

**Spec coverage:** Wizard (Task 3) ✅ · Recommendations wired to engine (Task 4) ✅ · Product Detail with cheapest price + bundle (Task 5) ✅ · My Plan with in-budget / manual-defer over-budget / must-exceeds states (Task 6) ✅ · guest-session plan add/remove (Tasks 3–4) ✅ · budget logic for a user-chosen basket that can exceed budget (`PlanAdvisor`, Task 2) ✅.

**Type consistency:** `PlanAdvisor::summarize()` consumes `['productId','tier'=>ProductTier,'lineTotal']`; PlanController builds exactly that shape. Inertia props use `tier->value` (string) consistently across pages; React `tierLabel`/`tierBadge` keys are `must`/`recommended`/`optional` matching the enum values.

**Verification limits:** subagents verify via `npm run build` (compiles) + Inertia feature tests (component + props). Pixel fidelity to `docs/wireframes/index.html` is verified by the human via the browser preview after execution — not asserted in tests.

**Deferred (follow-on):** Explore/Browse, Restock/calendar tab, per-store rollup, "why recommended" copy, and guest→account saving (Plan 4). Over-budget "change to cheaper SKU" and "split across months" actions are shown as guidance text in the must-exceeds state; their interactive flows are Plan 4+.

---

## Next
Plan 4 — guest→account lossless save (persist the session plan to a `Plan` model on sign-up), plus Explore and the Restock calendar.
