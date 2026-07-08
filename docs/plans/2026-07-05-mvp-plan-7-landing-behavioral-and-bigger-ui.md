# BuyBuddy — MVP Plan 7: Landing Page + Behavioral Onboarding + Bigger UI

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** (1) Add a proper landing/cover page so it feels like a real app, (2) add short behavioral onboarding questions that personalise recommendations, and (3) bump button/text sizes 1–2 steps for friendliness.

**Architecture:** `/` becomes a `Landing` page; the wizard moves to `/wizard`. The `Spec` gains behavioral fields (`laundry`, `work_style`, `spending_style`) that feed the existing `TriggerEvaluator`, plus a `spending_style` rule in `RecommendationService` (essentials spenders don't see optional items). Sizing is bumped globally via the root font-size plus larger primary CTAs. Follow the BuyBuddy design skills in `.claude/skills/`.

**Tech Stack:** Laravel 11, Inertia v3 + React 19, Tailwind 3, Pest 3.

**Depends on:** Plans 1–6.

---

## Task 1: Behavioral spec fields + personalised recommendations

**Files:**
- Modify: `app/Recommendation/Spec.php`, `app/Recommendation/RecommendationService.php`, `app/Support/PlanSession.php`, `app/Http/Requests/StoreSpecRequest.php`, `app/Http/Controllers/WizardController.php`, `database/seeders/StudioStarterSeeder.php`
- Test: `tests/Unit/SpecTest.php`, `tests/Feature/RecommendationServiceTest.php` (extend)

- [ ] **Step 1: Add behavioral fields to `Spec`** (new params have defaults so existing callers keep working):

```php
readonly class Spec
{
    /** @param  array<int>  $ownedProductIds */
    public function __construct(
        public int $budget,
        public string $roomType,
        public int $occupants,
        public string $cooking,
        public array $ownedProductIds = [],
        public string $laundry = 'own_machine',
        public string $workStyle = 'office',
        public string $spendingStyle = 'balanced',
    ) {}

    public function owns(int $productId): bool
    {
        return in_array($productId, $this->ownedProductIds, true);
    }

    public function value(string $field): int|string
    {
        return match ($field) {
            'budget' => $this->budget,
            'room_type' => $this->roomType,
            'occupants' => $this->occupants,
            'cooking' => $this->cooking,
            'laundry' => $this->laundry,
            'work_style' => $this->workStyle,
            'spending_style' => $this->spendingStyle,
        };
    }
}
```

- [ ] **Step 2: Test the new value() cases** — add to `tests/Unit/SpecTest.php`:

```php
it('exposes behavioral fields for triggers', function () {
    $spec = new Spec(budget: 5000, roomType: 'studio', occupants: 1, cooking: 'often', laundry: 'hand', workStyle: 'home', spendingStyle: 'comfort');

    expect($spec->value('laundry'))->toBe('hand')
        ->and($spec->value('work_style'))->toBe('home')
        ->and($spec->value('spending_style'))->toBe('comfort');
});
```

Run `./vendor/bin/pest tests/Unit/SpecTest.php` → PASS.

- [ ] **Step 3: PlanSession reads the new keys** — in `app/Support/PlanSession.php` `spec()`:

```php
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
```

- [ ] **Step 4: `spending_style` personalisation in `RecommendationService`** — in `eligible()`, after the trigger filter, add a reject for essentials spenders:

```php
return Product::with('prices')
    ->get()
    ->reject(fn (Product $p) => $spec->owns($p->id))
    ->filter(fn (Product $p) => $this->triggers->passes($p->triggers ?? [], $spec))
    ->reject(fn (Product $p) => $spec->spendingStyle === 'essentials' && $p->tier === ProductTier::Optional);
```

(`ProductTier` is already imported in the service.)

- [ ] **Step 5: Test personalisation** — add to `tests/Feature/RecommendationServiceTest.php`:

```php
it('hides optional items for essentials spenders but shows them for comfort spenders', function () {
    $optional = makeProduct(['tier' => ProductTier::Optional, 'triggers' => []]);

    $essentials = new Spec(budget: 5000, roomType: 'studio', occupants: 1, cooking: 'sometimes', spendingStyle: 'essentials');
    $comfort = new Spec(budget: 5000, roomType: 'studio', occupants: 1, cooking: 'sometimes', spendingStyle: 'comfort');

    expect(collect(app(RecommendationService::class)->recommend($essentials)->items)->pluck('productId'))->not->toContain($optional->id)
        ->and(collect(app(RecommendationService::class)->recommend($comfort)->items)->pluck('productId'))->toContain($optional->id);
});
```

Run `./vendor/bin/pest tests/Feature/RecommendationServiceTest.php` → PASS.

- [ ] **Step 6: Validate + store the new fields.** In `app/Http/Requests/StoreSpecRequest.php` `rules()` add:

```php
'laundry' => ['required', 'in:own_machine,hand,service'],
'work_style' => ['required', 'in:home,office,hybrid'],
'spending_style' => ['required', 'in:essentials,balanced,comfort'],
```

In `app/Http/Controllers/WizardController.php` `store()`, extend the `setSpec([...])` array:

```php
'laundry' => $request->string('laundry')->toString(),
'work_style' => $request->string('work_style')->toString(),
'spending_style' => $request->string('spending_style')->toString(),
```

- [ ] **Step 7: Seed a couple of trigger-driven products** so personalisation is visible. In `StudioStarterSeeder::run()` add (reuse the `product()` helper; a new category is fine):

```php
$work = Category::create(['name' => 'ทำงาน', 'slug' => 'work', 'sort_order' => 6]);
$this->product($work, 'desk-lamp', 'โคมไฟตั้งโต๊ะ', ProductTier::Recommended, 290, [
    ['field' => 'work_style', 'op' => 'in', 'value' => ['home', 'hybrid']],
]);
$this->product($work, 'laundry-rack', 'ราวตากผ้าในห้อง', ProductTier::Recommended, 350, [
    ['field' => 'laundry', 'op' => 'in', 'value' => ['hand', 'service']],
]);
```

- [ ] **Step 8: Run the full suite + Pint, commit**

```bash
cd /Users/grapetnp/Herd/buy-buddy && ./vendor/bin/pest && ./vendor/bin/pint
git add -A && git commit -m "feat: behavioral spec fields personalise recommendations"
```

---

## Task 2: Landing page + route restructure

**Files:**
- Create: `app/Http/Controllers/LandingController.php`, `resources/js/Pages/Landing.jsx`
- Modify: `routes/web.php`
- Test: `tests/Feature/LandingTest.php`, and update `tests/Feature/WizardTest.php`

- [ ] **Step 1: LandingController** `app/Http/Controllers/LandingController.php`:

```php
<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class LandingController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Landing');
    }
}
```

- [ ] **Step 2: Routes** — in `routes/web.php`, change the first route and add the wizard GET at `/wizard`:

```php
use App\Http\Controllers\LandingController;

Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/wizard', [WizardController::class, 'show'])->name('wizard');
Route::post('/wizard', [WizardController::class, 'store'])->name('wizard.store');
```

(Keep every other route. `route('wizard')` now resolves to `/wizard`, so the no-spec redirects in `RecommendationController`/`PlanController` still work unchanged.)

- [ ] **Step 3: Landing page** `resources/js/Pages/Landing.jsx`:

```jsx
import { Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

const perks = [['🎯', 'แนะนำตามคุณ'], ['💸', 'คุ้มทุกบาท'], ['📅', 'วางแผนซื้อซ้ำ']];

export default function Landing() {
    return (
        <AppLayout>
            <div className="py-6 text-center">
                <div className="mx-auto mb-5 flex h-28 w-28 items-center justify-center rounded-full bg-brand-50 text-6xl" aria-hidden="true">🛍️</div>
                <h1 className="text-3xl font-bold leading-tight text-ink">จัดของเข้าห้อง<br />ง่าย ๆ ตามงบ</h1>
                <p className="mx-auto mt-3 max-w-sm text-lg text-ink-soft">BuyBuddy ช่วยแนะนำว่าต้องซื้ออะไร คุ้มสุดที่ร้านไหน และวางแผนไม่ให้เกินงบ</p>
                <div className="mt-8 space-y-3">
                    <Link href="/wizard" className="block rounded-full bg-brand p-4 text-center text-lg font-semibold text-white shadow-soft transition hover:bg-brand-500 active:scale-[0.98]">
                        เริ่มเลย — ตอบ 4 ข้อ
                    </Link>
                    <Link href="/explore" className="block rounded-full border border-ink/15 p-4 text-center text-lg font-semibold text-ink transition hover:bg-cream-sunk active:scale-95">
                        เลือกดูของเอง
                    </Link>
                </div>
                <div className="mt-10 grid grid-cols-3 gap-3">
                    {perks.map(([emoji, label]) => (
                        <div key={label} className="rounded-2xl bg-cream-sunk p-4">
                            <div className="text-3xl" aria-hidden="true">{emoji}</div>
                            <div className="mt-2 text-sm font-medium text-ink-soft">{label}</div>
                        </div>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
```

- [ ] **Step 4: LandingTest** `tests/Feature/LandingTest.php`:

```php
<?php

use Inertia\Testing\AssertableInertia as Assert;

it('shows the landing page at the root', function () {
    $this->get('/')->assertInertia(fn (Assert $page) => $page->component('Landing'));
});

it('serves the wizard at /wizard', function () {
    $this->get('/wizard')->assertInertia(fn (Assert $page) => $page->component('Wizard'));
});
```

- [ ] **Step 5: Fix WizardTest** — in `tests/Feature/WizardTest.php`, the "shows the wizard" test must hit `/wizard` (not `/`), and the store/validation posts must include the new required fields. Replace its body with:

```php
<?php

use Inertia\Testing\AssertableInertia as Assert;

it('shows the wizard', function () {
    $this->get('/wizard')->assertInertia(fn (Assert $page) => $page->component('Wizard'));
});

it('stores the spec and redirects to recommendations', function () {
    $this->post('/wizard', [
        'budget' => 5000, 'room_type' => 'studio', 'occupants' => 1, 'cooking' => 'sometimes',
        'laundry' => 'own_machine', 'work_style' => 'office', 'spending_style' => 'balanced',
    ])->assertRedirect(route('recommendations'));

    expect(session('spec'))->toMatchArray(['budget' => 5000, 'cooking' => 'sometimes', 'work_style' => 'office']);
});

it('validates the spec', function () {
    $this->post('/wizard', [
        'budget' => 5000, 'room_type' => 'studio', 'occupants' => 1, 'cooking' => 'sometimes',
        'laundry' => 'own_machine', 'work_style' => 'office', 'spending_style' => 'nope',
    ])->assertSessionHasErrors('spending_style');
});
```

- [ ] **Step 6: Build + test, commit**

```bash
cd /Users/grapetnp/Herd/buy-buddy && npm run build && ./vendor/bin/pest tests/Feature/LandingTest.php tests/Feature/WizardTest.php
git add -A && git commit -m "feat: landing page, wizard moves to /wizard"
```

---

## Task 3: Behavioral wizard questions + bigger UI

**Files:**
- Modify: `resources/js/Pages/Wizard.jsx`, `resources/css/app.css`
- Test: none new (covered by WizardTest); build must pass

- [ ] **Step 1: Bump global sizing.** In `resources/css/app.css`, after the `@tailwind` directives add:

```css
@layer base {
    html {
        font-size: 18px;
    }
}
```

This scales every rem-based size up ~1 step. (Do NOT change viewport meta.)

- [ ] **Step 2: Rewrite `resources/js/Pages/Wizard.jsx`** with the new behavioral questions and bigger controls. It must post `budget, room_type, occupants, cooking, laundry, work_style, spending_style`:

```jsx
import { useForm } from '@inertiajs/react';
import { motion } from 'motion/react';
import AppLayout from '@/Layouts/AppLayout';

const questions = [
    { key: 'cooking', label: 'ทำอาหารเองบ่อยแค่ไหน', options: [['never', 'ไม่ทำเลย'], ['sometimes', 'ทำบ้าง'], ['often', 'ทำบ่อย']] },
    { key: 'laundry', label: 'ซักผ้ายังไง', options: [['own_machine', 'มีเครื่องซัก'], ['hand', 'ซักมือ'], ['service', 'ส่งร้าน']] },
    { key: 'work_style', label: 'ทำงานที่ไหนเป็นหลัก', options: [['office', 'ออฟฟิศ'], ['home', 'ที่ห้อง'], ['hybrid', 'ผสม']] },
    { key: 'spending_style', label: 'สไตล์การซื้อของ', options: [['essentials', 'เอาที่จำเป็น'], ['balanced', 'พอดี ๆ'], ['comfort', 'อยากได้ครบ']] },
];

export default function Wizard() {
    const { data, setData, post, processing } = useForm({
        budget: 5000, room_type: 'studio', occupants: 1,
        cooking: 'sometimes', laundry: 'own_machine', work_style: 'office', spending_style: 'balanced',
    });

    const submit = (e) => { e.preventDefault(); post('/wizard'); };
    const input = 'mt-1.5 w-full rounded-xl border border-ink/10 bg-cream-card p-3 text-base text-ink focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none';

    return (
        <AppLayout>
            <form onSubmit={submit}>
                <h1 className="text-2xl font-bold text-ink">ตั้งค่าห้องของคุณ</h1>
                <p className="mt-1 text-base text-ink-soft">ตอบสั้น ๆ เพื่อให้เราแนะนำได้ตรงใจ</p>

                <label className="mt-6 block text-base font-medium text-ink">งบประมาณ (฿)</label>
                <input type="number" value={data.budget} onChange={(e) => setData('budget', Number(e.target.value))} className={input} />

                <label className="mt-5 block text-base font-medium text-ink">อยู่กี่คน</label>
                <input type="number" min="1" value={data.occupants} onChange={(e) => setData('occupants', Number(e.target.value))} className={input} />

                {questions.map((q) => (
                    <div key={q.key} className="mt-6">
                        <p className="text-base font-medium text-ink">{q.label}</p>
                        <div className="mt-2 grid grid-cols-3 gap-2">
                            {q.options.map(([val, label]) => {
                                const active = data[q.key] === val;
                                return (
                                    <button
                                        key={val}
                                        type="button"
                                        aria-pressed={active}
                                        onClick={() => setData(q.key, val)}
                                        className={`rounded-2xl border p-3 text-base transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40 ${
                                            active ? 'border-2 border-brand bg-brand-50 font-semibold text-brand-700' : 'border-ink/10 text-ink-soft hover:bg-cream-sunk'
                                        }`}
                                    >
                                        {label}
                                    </button>
                                );
                            })}
                        </div>
                    </div>
                ))}

                <motion.button whileTap={{ scale: 0.98 }} type="submit" disabled={processing}
                    className="mt-8 w-full rounded-full bg-brand p-4 text-lg font-semibold text-white shadow-soft transition hover:bg-brand-500 disabled:opacity-60">
                    ดูชุดของแนะนำ
                </motion.button>
            </form>
        </AppLayout>
    );
}
```

- [ ] **Step 3: Enlarge the primary CTAs on the core pages** (one step bigger, more tappable). In `resources/js/Pages/MyPlan.jsx`, `Recommendations.jsx`, `ProductDetail.jsx`, `Explore.jsx`, `Auth/Register.jsx`, `Auth/Login.jsx`, and `Layouts/AppLayout.jsx`: change the primary/brand buttons and page headings up one step where they read small — e.g. primary buttons `p-3 → p-4` and `font-semibold text-base`, main page `<h1>` to `text-2xl`. Keep everything else. (The root font-size bump already enlarges body text globally; this makes the key actions clearly bigger.)

- [ ] **Step 4: Build + full suite + Pint, commit**

```bash
cd /Users/grapetnp/Herd/buy-buddy && npm run build && ./vendor/bin/pest && ./vendor/bin/pint
git add -A && git commit -m "feat: behavioral wizard questions and larger, friendlier UI"
```
If Pint changed files, commit `style: pint`.

---

## Self-Review (Plan 7)

**Spec coverage:** landing/cover page at `/` with clear CTAs (Task 2) ✅ · short behavioral questions that personalise recommendations — new `Spec` fields drive `TriggerEvaluator` (desk-lamp/laundry-rack) and `spending_style` gates optional items (Task 1, asserted) ✅ · bigger buttons/text via +root font-size and enlarged CTAs/headings (Task 3) ✅.

**Type consistency:** new `Spec` params default so existing constructors keep compiling; `value()` field names (`laundry`/`work_style`/`spending_style`) match the seeder triggers, `StoreSpecRequest` rules, `WizardController` stored keys, `PlanSession` reads, and the Wizard form field names. Route name `wizard` is preserved (now `/wizard`) so `route('wizard')` redirects elsewhere stay valid.

**Deferred:** multi-step wizard with per-question progress, "owned items" checklist, and richer landing (sample-kit preview, mascot art once brand assets arrive).

---

## Next
Wire the real mascot into Landing + emotional moments once `brand/` assets arrive; sample-kit preview on Landing.
