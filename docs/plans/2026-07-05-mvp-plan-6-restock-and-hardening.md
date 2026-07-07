# Grocery List Web App — MVP Plan 6: Restock Calendar + Production Hardening

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add the Restock (calendar) tab on My Plan, and harden the app for production with auth rate limiting and security response headers.

**Architecture:** My Plan gains a client-side tab toggle (list / calendar); the calendar view groups the plan's `Restock`-mode items by `restock_cadence`. Auth POST routes are throttled. A `SecurityHeaders` middleware adds standard headers to every web response.

**Tech Stack:** Laravel 11, Inertia v3 + React 19, Pest 3.

**Depends on:** Plans 1–5.

---

## File Structure (Plan 6)

- `app/Http/Controllers/PlanController.php` — add `restock` prop
- `resources/js/Pages/MyPlan.jsx` — tabs + calendar view (full replacement)
- `routes/web.php` — throttle login/register
- `app/Http/Middleware/SecurityHeaders.php` + `bootstrap/app.php`
- tests

---

## Task 1: Restock calendar tab

**Files:**
- Modify: `app/Http/Controllers/PlanController.php`, `resources/js/Pages/MyPlan.jsx`
- Test: `tests/Feature/MyPlanTest.php` (extend)

- [ ] **Step 1: Add restock grouping to `PlanController::show()`.** Add the import:

```php
use App\Enums\ProductMode;
```

After `$products = Product::whereIn('id', $session->planIds())->with('prices')->get();`, add:

```php
$restock = $products
    ->filter(fn (Product $p) => $p->mode === ProductMode::Restock && $p->restock_cadence)
    ->groupBy('restock_cadence')
    ->map(fn ($group, $cadence) => [
        'cadence' => $cadence,
        'items' => $group->map(fn (Product $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'price' => $p->cheapestPrice(),
        ])->values(),
    ])
    ->values();
```

Add to the `Inertia::render('MyPlan', [...])` props array:

```php
'restock' => $restock,
```

- [ ] **Step 2: Replace `resources/js/Pages/MyPlan.jsx` entirely with:**

```jsx
import { useState } from 'react';
import { router, usePage, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';
import BudgetMeter from '@/Components/BudgetMeter';

const tierLabel = { must: 'จำเป็น', recommended: 'แนะนำ', optional: 'ถ้ามีงบ' };
const cadenceLabel = { weekly: 'รายสัปดาห์', monthly: 'รายเดือน' };

export default function MyPlan({ items, budget, total, overBudgetBy, mustExceedsBudget, storeRollup, restock }) {
    const { auth } = usePage().props;
    const [tab, setTab] = useState('list');
    const over = overBudgetBy > 0;

    return (
        <AppLayout>
            <h1 className="text-lg font-medium">แผนของฉัน</h1>

            <div className="mt-3 flex gap-4 border-b border-neutral-100">
                <button onClick={() => setTab('list')}
                    className={`pb-2 text-sm ${tab === 'list' ? 'border-b-2 border-neutral-800 font-medium' : 'text-neutral-500'}`}>รายการ</button>
                <button onClick={() => setTab('calendar')}
                    className={`pb-2 text-sm ${tab === 'calendar' ? 'border-b-2 border-neutral-800 font-medium' : 'text-neutral-500'}`}>ปฏิทิน</button>
            </div>

            {tab === 'list' && (
                <>
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
                    {storeRollup?.length > 0 && (
                        <div className="mt-4 rounded-lg bg-neutral-50 p-3 text-xs text-neutral-500">
                            ถ้าซื้อทั้งหมดที่: {storeRollup.map((s) => `${s.platform} ฿${s.total.toLocaleString()}`).join(' · ')}
                        </div>
                    )}
                </>
            )}

            {tab === 'calendar' && (
                <div className="mt-4">
                    {restock?.length > 0 ? restock.map((group) => (
                        <div key={group.cadence} className="mb-4">
                            <div className="mb-2 text-sm font-medium">{cadenceLabel[group.cadence] ?? group.cadence}</div>
                            <div className="divide-y divide-neutral-100 rounded-xl border border-neutral-200">
                                {group.items.map((it) => (
                                    <div key={it.id} className="flex items-center justify-between p-3">
                                        <span className="text-sm">{it.name}</span>
                                        <span className="text-sm text-neutral-500">฿{it.price.toLocaleString()}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )) : <p className="py-6 text-center text-sm text-neutral-400">ยังไม่มีของสิ้นเปลืองในแผน — เพิ่มของหมวด Restock เพื่อวางแผนซื้อซ้ำ</p>}
                </div>
            )}

            <div className="mt-5 border-t border-neutral-100 pt-4">
                {auth?.user ? (
                    <button onClick={() => router.post('/plan/save', {}, { preserveScroll: true })}
                        className="w-full rounded-lg bg-neutral-800 p-3 font-medium text-white">
                        เซฟแผนไว้ในบัญชี ({auth.user.name})
                    </button>
                ) : (
                    <Link href="/register" className="block rounded-lg bg-neutral-800 p-3 text-center font-medium text-white">
                        เซฟแผน (สมัคร/เข้าสู่ระบบ)
                    </Link>
                )}
            </div>
        </AppLayout>
    );
}
```

- [ ] **Step 3: Extend the test** — add to `tests/Feature/MyPlanTest.php`. Ensure `use App\Enums\ProductMode;` is present at the top of the file (add it if missing), then add:

```php
it('groups restock items by cadence for the calendar tab', function () {
    $weekly = Product::factory()->for(Category::factory())->create(['mode' => ProductMode::Restock, 'restock_cadence' => 'weekly', 'ref_price' => 60]);
    $monthly = Product::factory()->for(Category::factory())->create(['mode' => ProductMode::Restock, 'restock_cadence' => 'monthly', 'ref_price' => 180]);
    $moveIn = Product::factory()->for(Category::factory())->create(['mode' => ProductMode::MoveIn, 'ref_price' => 500]);
    planWith([$weekly->id, $monthly->id, $moveIn->id]);

    $this->get('/plan')->assertInertia(fn (Assert $page) => $page->has('restock', 2));
});
```

- [ ] **Step 4: Build + test**

```bash
cd /Users/grapetnp/Herd/grocery-list-webapp && npm run build && ./vendor/bin/pest tests/Feature/MyPlanTest.php
```
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: restock calendar tab on my plan"
```

---

## Task 2: Rate-limit auth routes

**Files:**
- Modify: `routes/web.php`
- Test: `tests/Feature/Auth/LoginTest.php` (extend)

- [ ] **Step 1: Add throttle to the auth POST routes.** In `routes/web.php`, add `->middleware('throttle:6,1')` to the login and register POST routes:

```php
Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:6,1');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:6,1');
```

(Keep the GET routes and `/logout` as they are.)

- [ ] **Step 2: Add a limiter-isolating `beforeEach` and a throttle test** to `tests/Feature/Auth/LoginTest.php`. Add at the top (after the `uses(...)` line):

```php
beforeEach(fn () => cache()->flush());
```

And add this test:

```php
it('throttles repeated failed login attempts', function () {
    $user = User::factory()->create(['password' => bcrypt('password123')]);

    foreach (range(1, 6) as $ignored) {
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
    }

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])->assertStatus(429);
});
```

- [ ] **Step 3: Run — expect PASS**

Run: `./vendor/bin/pest tests/Feature/Auth/LoginTest.php`
Expected: PASS (existing login tests + the new throttle test; `cache()->flush()` keeps each test's limiter clean).

- [ ] **Step 4: Commit**

```bash
git add -A && git commit -m "feat: rate-limit login and register"
```

---

## Task 3: Security headers middleware

**Files:**
- Create: `app/Http/Middleware/SecurityHeaders.php`
- Modify: `bootstrap/app.php`
- Test: `tests/Feature/SecurityHeadersTest.php`

- [ ] **Step 1: Middleware** `app/Http/Middleware/SecurityHeaders.php`:

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        return $response;
    }
}
```

- [ ] **Step 2: Register it** in `bootstrap/app.php`. In the `->withMiddleware(function (Middleware $middleware) {` block, append it to the web group alongside the existing `HandleInertiaRequests`:

```php
$middleware->web(append: [
    \App\Http\Middleware\HandleInertiaRequests::class,
    \App\Http\Middleware\SecurityHeaders::class,
]);
```

(If `HandleInertiaRequests` is already appended there, just add the `SecurityHeaders::class` line to the same array — do not duplicate the append call.)

- [ ] **Step 3: Test** `tests/Feature/SecurityHeadersTest.php`:

```php
<?php

it('sends security headers on web responses', function () {
    $this->get('/')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});
```

- [ ] **Step 4: Run — expect PASS**

Run: `./vendor/bin/pest tests/Feature/SecurityHeadersTest.php`
Expected: PASS.

- [ ] **Step 5: Full suite + build + Pint + commit**

```bash
cd /Users/grapetnp/Herd/grocery-list-webapp && npm run build && ./vendor/bin/pest && ./vendor/bin/pint
git add -A && git commit -m "feat: security response headers"
```
If Pint changed files, commit `style: pint`.

---

## Self-Review (Plan 6)

**Spec coverage:** Restock/calendar tab — My Plan now toggles list/calendar, calendar groups Restock-mode items by cadence (Task 1, asserted via `restock` prop) ✅ · production hardening — auth POST routes throttled to 6/min (Task 2, asserted 429) and standard security headers on all web responses (Task 3, asserted) ✅.

**Type consistency:** `restock` prop is a list of `{cadence, items:[{id,name,price}]}`; `MyPlan.jsx` reads exactly that. `ProductMode::Restock` filter matches the enum used by the seeder's restock items (detergent/dish-soap/rice/cooking-oil/etc.).

**Production checklist (deploy-time `.env`/config — not code, do when hosting):**
- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://…`
- `SESSION_SECURE_COOKIE=true`, `SESSION_SAME_SITE=lax`
- Run `php artisan config:cache route:cache view:cache` and `php artisan filament:optimize`
- Serve a real DB (MySQL/Postgres) instead of SQLite; run migrations + seeders
- Put the app behind HTTPS; set `TrustProxies` if behind a load balancer
- Rotate `APP_KEY`; review `canAccessPanel` allowlist for real admins
- Resolve the `audit.block-insecure=false` note once a patched Laravel 11.x ships

**Deferred (follow-on):** "next restock date" reminders + notifications, password reset/email verification, empty/error/legal pages, i18n, basket optimizer with shipping.

---

## Next
Notifications for restock reminders · password reset · legal pages · i18n.
