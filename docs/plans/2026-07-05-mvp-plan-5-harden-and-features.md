# Grocery List Web App — MVP Plan 5: Harden Admin + Features

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Close the biggest security gap (any registered user can reach `/admin`) and add three deferred features: Explore/Browse (2nd entry door), per-store rollup on My Plan, and login-merge (don't overwrite a guest's in-progress plan on login).

**Architecture:** An `is_admin` flag on users gates Filament. A read-only `ExploreController` lists the catalog with filters. A pure `StoreRollup` service computes "buy-everything-here" totals per platform for the plan. Login merges the guest session plan with the saved plan instead of overwriting.

**Tech Stack:** Laravel 11, Filament 3, Inertia v3 + React 19, Pest 3.

**Depends on:** Plans 1–4.

---

## File Structure (Plan 5)

- migration `..._add_is_admin_to_users_table`, `app/Models/User.php`, `database/factories/UserFactory.php`, `database/seeders/AdminUserSeeder.php`
- `app/Http/Controllers/ExploreController.php`, `resources/js/Pages/Explore.jsx`, `resources/js/Layouts/AppLayout.jsx`
- `app/Recommendation/StoreRollup.php`, `app/Http/Controllers/PlanController.php`, `resources/js/Pages/MyPlan.jsx`
- `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- `routes/web.php`, tests

---

## Task 1: Harden Filament access with an is_admin flag

**Files:**
- Create: migration
- Modify: `app/Models/User.php`, `database/factories/UserFactory.php`, `database/seeders/AdminUserSeeder.php`, `tests/Feature/Filament/*` (use an admin user)
- Test: `tests/Feature/Filament/AdminAccessTest.php` (update)

- [ ] **Step 1: Migration**

```bash
cd /Users/grapetnp/Herd/grocery-list-webapp
php artisan make:migration add_is_admin_to_users_table --no-interaction
```

```php
public function up(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->boolean('is_admin')->default(false);
    });
}

public function down(): void
{
    Schema::table('users', function (Blueprint $table) {
        $table->dropColumn('is_admin');
    });
}
```

- [ ] **Step 2: User model** — add `is_admin` to `$fillable`, cast it, and gate the panel:

```php
protected $fillable = ['name', 'email', 'password', 'is_admin'];
```

In `casts()` add:

```php
'is_admin' => 'boolean',
```

Replace `canAccessPanel()`:

```php
public function canAccessPanel(Panel $panel): bool
{
    return $this->is_admin;
}
```

- [ ] **Step 3: UserFactory admin state** — add to `database/factories/UserFactory.php`:

```php
public function admin(): static
{
    return $this->state(fn () => ['is_admin' => true]);
}
```

- [ ] **Step 4: AdminUserSeeder** — set the flag. Change the create call to:

```php
User::firstOrCreate(
    ['email' => 'admin@grocery.test'],
    ['name' => 'Admin', 'password' => Hash::make('password'), 'is_admin' => true],
);
```

- [ ] **Step 5: Update the access test** `tests/Feature/Filament/AdminAccessTest.php`:

```php
<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('lets an admin reach the admin panel', function () {
    $this->actingAs(User::factory()->admin()->create())->get('/admin')->assertSuccessful();
});

it('forbids a non-admin from the admin panel', function () {
    $this->actingAs(User::factory()->create())->get('/admin')->assertForbidden();
});

it('redirects guests away from the admin panel', function () {
    $this->get('/admin')->assertRedirect();
});
```

- [ ] **Step 6: Make the other Filament tests use an admin.** In each of `tests/Feature/Filament/CategoryResourceTest.php`, `ProductResourceTest.php`, and `ProductPricesRelationManagerTest.php`, change the actor from `User::factory()->create()` to `User::factory()->admin()->create()` (the `beforeEach(...)` in the first two, and the inline `actingAs(...)` in the relation-manager test).

- [ ] **Step 7: Run the Filament suite — expect PASS**

Run: `./vendor/bin/pest tests/Feature/Filament`
Expected: PASS (admin allowed, non-admin forbidden).

- [ ] **Step 8: Commit**

```bash
git add -A && git commit -m "feat: gate Filament admin behind an is_admin flag"
```

---

## Task 2: Explore / Browse page

**Files:**
- Create: `app/Http/Controllers/ExploreController.php`, `resources/js/Pages/Explore.jsx`
- Modify: `routes/web.php`, `resources/js/Layouts/AppLayout.jsx`
- Test: `tests/Feature/ExplorePageTest.php`

- [ ] **Step 1: Controller** `app/Http/Controllers/ExploreController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\PlanSession;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExploreController extends Controller
{
    public function index(Request $request, PlanSession $session): Response
    {
        $categorySlug = $request->string('category')->toString();
        $query = $request->string('q')->toString();

        $products = Product::query()
            ->with('category')
            ->when($categorySlug !== '', fn ($q) => $q->whereHas('category', fn ($c) => $c->where('slug', $categorySlug)))
            ->when($query !== '', fn ($q) => $q->where('name', 'like', "%{$query}%"))
            ->orderBy('name')
            ->get();

        $planIds = $session->planIds();

        return Inertia::render('Explore', [
            'categories' => Category::orderBy('sort_order')->get(['slug', 'name']),
            'activeCategory' => $categorySlug,
            'query' => $query,
            'products' => $products->map(fn (Product $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category->name,
                'price' => $p->cheapestPrice(),
                'inPlan' => in_array($p->id, $planIds, true),
            ])->values(),
        ]);
    }
}
```

- [ ] **Step 2: Route** — add to `routes/web.php`:

```php
use App\Http\Controllers\ExploreController;

Route::get('/explore', [ExploreController::class, 'index'])->name('explore');
```

- [ ] **Step 3: Nav link.** In `resources/js/Layouts/AppLayout.jsx`, add an Explore link in the header next to the plan link:

```jsx
<Link href="/explore" className="text-sm text-neutral-600">เลือกดูของ</Link>
```

Place it just before the existing `กระเป๋า` link (wrap the two links in a `<nav className="flex gap-4">` if needed).

- [ ] **Step 4: Explore page** `resources/js/Pages/Explore.jsx`:

```jsx
import { Link, router } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Explore({ categories, activeCategory, query, products }) {
    const go = (params) => router.get('/explore', params, { preserveState: true, preserveScroll: true });

    return (
        <AppLayout>
            <h1 className="text-lg font-medium">เลือกดูของเอง</h1>
            <input
                defaultValue={query}
                placeholder="ค้นหาสินค้า"
                onKeyDown={(e) => { if (e.key === 'Enter') go({ q: e.target.value, category: activeCategory }); }}
                className="mt-3 w-full rounded-lg border border-neutral-200 p-2"
            />
            <div className="mt-3 flex flex-wrap gap-2">
                <button onClick={() => go({ q: query })}
                    className={`rounded-full px-3 py-1 text-xs ${activeCategory === '' ? 'bg-sky-50 text-sky-700' : 'border border-neutral-200 text-neutral-600'}`}>ทั้งหมด</button>
                {categories.map((c) => (
                    <button key={c.slug} onClick={() => go({ category: c.slug, q: query })}
                        className={`rounded-full px-3 py-1 text-xs ${activeCategory === c.slug ? 'bg-sky-50 text-sky-700' : 'border border-neutral-200 text-neutral-600'}`}>{c.name}</button>
                ))}
            </div>
            <div className="mt-4 space-y-2">
                {products.map((p) => (
                    <div key={p.id} className="flex items-center gap-3 rounded-xl border border-neutral-200 p-3">
                        <div className="flex-1">
                            <Link href={`/products/${p.id}`} className="text-sm font-medium">{p.name}</Link>
                            <div className="text-xs text-neutral-500">{p.category} · ฿{p.price.toLocaleString()}</div>
                        </div>
                        {p.inPlan ? (
                            <button onClick={() => router.delete(`/plan/items/${p.id}`, { preserveScroll: true })}
                                className="h-9 w-9 rounded-full bg-emerald-50 text-emerald-600">✓</button>
                        ) : (
                            <button onClick={() => router.post(`/plan/items/${p.id}`, {}, { preserveScroll: true })}
                                className="h-9 w-9 rounded-full border border-neutral-300">+</button>
                        )}
                    </div>
                ))}
                {products.length === 0 && <p className="py-6 text-center text-sm text-neutral-400">ไม่พบสินค้า</p>}
            </div>
        </AppLayout>
    );
}
```

- [ ] **Step 5: Test** `tests/Feature/ExplorePageTest.php`:

```php
<?php

use App\Models\Category;
use App\Models\Product;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('lists all products by default', function () {
    Product::factory()->for(Category::factory())->count(3)->create();

    $this->get('/explore')->assertInertia(fn (Assert $page) => $page->component('Explore')->has('products', 3));
});

it('filters by category', function () {
    $kitchen = Category::factory()->create(['slug' => 'kitchen']);
    Product::factory()->for($kitchen)->create();
    Product::factory()->for(Category::factory()->create(['slug' => 'bedroom']))->create();

    $this->get('/explore?category=kitchen')->assertInertia(fn (Assert $page) => $page->has('products', 1));
});

it('filters by search query', function () {
    Product::factory()->for(Category::factory())->create(['name' => 'หม้อหุงข้าว']);
    Product::factory()->for(Category::factory())->create(['name' => 'พัดลม']);

    $this->get('/explore?q=หม้อ')->assertInertia(fn (Assert $page) => $page->has('products', 1));
});
```

- [ ] **Step 6: Build + test, commit**

```bash
cd /Users/grapetnp/Herd/grocery-list-webapp && npm run build && ./vendor/bin/pest tests/Feature/ExplorePageTest.php
git add -A && git commit -m "feat: explore/browse page with category and search filters"
```

---

## Task 3: Per-store rollup on My Plan

**Files:**
- Create: `app/Recommendation/StoreRollup.php`
- Modify: `app/Http/Controllers/PlanController.php`, `resources/js/Pages/MyPlan.jsx`
- Test: `tests/Unit/StoreRollupTest.php`, `tests/Feature/MyPlanTest.php` (extend)

- [ ] **Step 1: Write the failing unit test** `tests/Unit/StoreRollupTest.php`:

```php
<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Recommendation\StoreRollup;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('totals each platform, falling back to cheapest where a store lacks the item', function () {
    $a = Product::factory()->for(Category::factory())->create(['ref_price' => 100]);
    ProductPrice::factory()->for($a)->create(['platform' => 'Shopee', 'price' => 100]);
    ProductPrice::factory()->for($a)->create(['platform' => 'Lazada', 'price' => 120]);

    $b = Product::factory()->for(Category::factory())->create(['ref_price' => 200]);
    ProductPrice::factory()->for($b)->create(['platform' => 'Shopee', 'price' => 200]);

    $rows = (new StoreRollup)->summarize([
        ['product' => $a->fresh('prices'), 'qty' => 1],
        ['product' => $b->fresh('prices'), 'qty' => 2],
    ]);

    // Shopee: 100*1 + 200*2 = 500 ; Lazada: 120*1 + cheapest(200)*2 = 520
    expect($rows[0])->toBe(['platform' => 'Shopee', 'total' => 500])
        ->and($rows[1])->toBe(['platform' => 'Lazada', 'total' => 520]);
});
```

- [ ] **Step 2: Run — expect FAIL**

Run: `./vendor/bin/pest tests/Unit/StoreRollupTest.php`
Expected: FAIL ("Class App\Recommendation\StoreRollup not found").

- [ ] **Step 3: Write `StoreRollup`** `app/Recommendation/StoreRollup.php`:

```php
<?php

namespace App\Recommendation;

use App\Models\Product;

class StoreRollup
{
    /**
     * @param  array<array{product:Product,qty:int}>  $lines
     * @return array<array{platform:string,total:int}>
     */
    public function summarize(array $lines): array
    {
        $platforms = [];
        foreach ($lines as $line) {
            foreach ($line['product']->prices as $price) {
                $platforms[$price->platform] = true;
            }
        }

        $rows = [];
        foreach (array_keys($platforms) as $platform) {
            $total = 0;
            foreach ($lines as $line) {
                $price = $line['product']->prices->firstWhere('platform', $platform);
                $unit = $price ? $price->price : $line['product']->cheapestPrice();
                $total += $unit * $line['qty'];
            }
            $rows[] = ['platform' => $platform, 'total' => $total];
        }

        usort($rows, fn ($a, $b) => $a['total'] <=> $b['total']);

        return array_slice($rows, 0, 3);
    }
}
```

- [ ] **Step 4: Run — expect PASS**

Run: `./vendor/bin/pest tests/Unit/StoreRollupTest.php`
Expected: PASS.

- [ ] **Step 5: Wire into `PlanController::show()`.** Add the import:

```php
use App\Recommendation\StoreRollup;
```

Change the signature to inject it and build the rollup from the already-loaded `$products`:

```php
public function show(PlanSession $session, PlanAdvisor $advisor, StoreRollup $rollup): Response|\Illuminate\Http\RedirectResponse
{
```

After `$products = Product::whereIn(...)->with('prices')->get();`, add:

```php
$rollupLines = $products->map(fn (Product $p) => [
    'product' => $p,
    'qty' => $p->qty_scales_by === 'occupants' ? $spec->occupants : 1,
])->all();
```

Add to the `Inertia::render('MyPlan', [...])` props:

```php
'storeRollup' => $rollup->summarize($rollupLines),
```

- [ ] **Step 6: Render on My Plan.** In `resources/js/Pages/MyPlan.jsx`, add a `storeRollup` prop and render it after the items list (before the save block from Plan 4). Add `storeRollup` to the destructured props, then:

```jsx
            {storeRollup?.length > 0 && (
                <div className="mt-4 rounded-lg bg-neutral-50 p-3 text-xs text-neutral-500">
                    ถ้าซื้อทั้งหมดที่: {storeRollup.map((s) => `${s.platform} ฿${s.total.toLocaleString()}`).join(' · ')}
                </div>
            )}
```

- [ ] **Step 7: Extend the My Plan feature test** — add to `tests/Feature/MyPlanTest.php`:

```php
it('includes a per-store rollup', function () {
    $product = Product::factory()->for(Category::factory())->create(['ref_price' => 300]);
    \App\Models\ProductPrice::factory()->for($product)->create(['platform' => 'Shopee', 'price' => 300]);
    planWith([$product->id]);

    $this->get('/plan')->assertInertia(fn (Assert $page) => $page
        ->has('storeRollup', 1, fn (Assert $row) => $row->where('platform', 'Shopee')->where('total', 300)));
});
```

- [ ] **Step 8: Run — expect PASS, then commit**

```bash
cd /Users/grapetnp/Herd/grocery-list-webapp && npm run build && ./vendor/bin/pest tests/Unit/StoreRollupTest.php tests/Feature/MyPlanTest.php
git add -A && git commit -m "feat: per-store rollup on my plan"
```

---

## Task 4: Login merges the guest plan instead of overwriting

**Files:**
- Modify: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- Test: `tests/Feature/Auth/LoginTest.php` (extend)

- [ ] **Step 1: Merge on login.** In `AuthenticatedSessionController::store()`, replace the block after `$request->session()->regenerate();`:

```php
        $plan = $plans->load(Auth::user());
        if ($plan) {
            $guestIds = $request->session()->get('plan_ids', []);
            $mergedIds = array_values(array_unique([...$plan->products->pluck('id')->all(), ...$guestIds]));

            $request->session()->put('plan_ids', $mergedIds);
            if (! $request->session()->has('spec')) {
                $request->session()->put('spec', $plan->spec);
            }
        }
```

(Guest's in-progress spec wins if present; otherwise the saved spec is restored. Product ids are unioned so nothing is lost either way.)

- [ ] **Step 2: Add the merge test** — add to `tests/Feature/Auth/LoginTest.php`:

```php
it('merges the guest plan with the saved plan on login', function () {
    $user = User::factory()->create(['password' => bcrypt('password123')]);
    $saved = Product::factory()->for(Category::factory())->create();
    $guest = Product::factory()->for(Category::factory())->create();
    (new PlanRepository)->save($user, ['budget' => 5000, 'room_type' => 'studio', 'occupants' => 1, 'cooking' => 'sometimes', 'owned_product_ids' => []], [$saved->id]);

    session(['plan_ids' => [$guest->id]]);

    $this->post('/login', ['email' => $user->email, 'password' => 'password123'])->assertRedirect(route('plan.show'));

    expect(session('plan_ids'))->toContain($saved->id)->toContain($guest->id);
});
```

- [ ] **Step 3: Run — expect PASS**

Run: `./vendor/bin/pest tests/Feature/Auth/LoginTest.php`
Expected: PASS (existing overwrite-from-empty-guest test still passes; new merge test passes).

- [ ] **Step 4: Full suite + Pint + commit**

```bash
cd /Users/grapetnp/Herd/grocery-list-webapp && npm run build && ./vendor/bin/pest && ./vendor/bin/pint
git add -A && git commit -m "feat: login merges guest plan with saved plan"
```
If Pint changed files, commit `style: pint`.

---

## Self-Review (Plan 5)

**Spec coverage:** admin hardening — `canAccessPanel` now returns `is_admin`, non-admin forbidden (Task 1, asserted) ✅ · Explore/Browse 2nd entry door with category + search filters and add-to-plan (Task 2) ✅ · per-store rollup "ถ้าซื้อทั้งหมดที่ร้านนี้" using curated prices with cheapest fallback (Task 3) ✅ · login-merge keeps both guest and saved product ids (Task 4, asserted) ✅.

**Cross-cutting:** Task 1 changes the actor in the existing Filament tests (Plan 2) to `->admin()`; those are updated in Step 6 so the suite stays green.

**Type consistency:** `StoreRollup::summarize(array $lines)` expects `['product'=>Product,'qty'=>int]`; `PlanController::show()` builds exactly that from the `with('prices')` products. Explore product prop shape (`id,name,category,price,inPlan`) matches `Explore.jsx`.

**Deferred (follow-on):** Restock calendar tab, "why recommended" copy polish, password reset/email verification, empty/error/legal pages, i18n. Rollup ignores per-store shipping (Phase 2 basket optimizer).

---

## Next
Restock calendar tab · password reset · production hardening (rate limiting, email verification).
