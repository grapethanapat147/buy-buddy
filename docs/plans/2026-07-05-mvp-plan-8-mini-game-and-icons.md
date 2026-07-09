# BuyBuddy — MVP Plan 8: Mini-Game Feel + Product Icons

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make choosing items feel like a cozy mini-game — a "Room-Setup Quest" — while keeping the warm BuyBuddy mood. Add emoji **icons** to every product for visual richness (placeholder for real images later).

**Concept:** Two game bars — **readiness** ("ห้องพร้อม X%", the goal = essentials collected) and **budget-as-coins** (the resource). Categories are mini-quests with progress ("ครัว 2/4"); completing one celebrates. Adding an item is a satisfying "collect". Cozy, encouraging, never punishing. Follow `.claude/skills/` (design-taste, motion-design).

**Tech Stack:** Laravel 11, Inertia v3 + React 19, Tailwind 3, `motion`, Pest 3. New dep: `canvas-confetti`.

**Depends on:** Plans 1–7.

---

## Task 1: Product icons + game metrics (data + engine)

**Files:**
- Create: migration `..._add_icon_to_products_table`
- Modify: `app/Models/Product.php`, `database/factories/ProductFactory.php`, `database/seeders/StudioStarterSeeder.php`, `app/Recommendation/RecommendationItem.php`, `app/Recommendation/RecommendationService.php`, `app/Http/Controllers/RecommendationController.php`, `app/Http/Controllers/ProductController.php`, `app/Http/Controllers/ExploreController.php`, `app/Http/Controllers/PlanController.php`
- Test: `tests/Feature/StudioStarterSeederTest.php`, `tests/Feature/RecommendationsPageTest.php` (extend)

- [ ] **Step 1: Migration** — `php artisan make:migration add_icon_to_products_table --no-interaction`:

```php
public function up(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->string('icon')->default('📦');
    });
}
public function down(): void
{
    Schema::table('products', function (Blueprint $table) {
        $table->dropColumn('icon');
    });
}
```

- [ ] **Step 2: Model + factory** — add `'icon'` to `Product::$fillable`; in `ProductFactory::definition()` add `'icon' => '📦'`.

- [ ] **Step 3: Seed emoji per product + per category.** In `StudioStarterSeeder`, give the `product()` helper an `$icon` param (default `'📦'`) that sets `'icon' => $icon`, add an `'icon'` to each `Category::create([...])` (add an `icon` column to categories too — migration + fillable, default `'📦'`), and pass an emoji for each product using this map:

Categories: kitchen `🍳`, bedroom `🛏️`, bathroom `🚿`, cleaning `🧹`, pantry `🥫`, work `💼`.

Products (by slug): rice-cooker `🍚`, rice-5kg `🌾`, rice-spoon `🥄`, frying-pan `🍳`, mini-fridge `🧊`, kettle `🫖`, plates `🍽️`, glasses `🥤`, microwave `🍲`, mattress-3-5ft `🛏️`, stand-fan `🌀`, bedding-set `🛌`, clothes-rack `🧥`, toiletries-set `🧴`, towels `🧻`, drying-rack `🧺`, detergent `🧼`, dish-soap `🧽`, trash-bin `🗑️`, broom-set `🧹`, cleaning-cloth `🧽`, cooking-oil `🛢️`, seasoning-set `🧂`, instant-noodles `🍜`, desk-lamp `💡`, laundry-rack `🧺`.

(Add a `categories.icon` migration `..._add_icon_to_categories_table` with default `'📦'`, and `'icon'` to `Category::$fillable`.)

- [ ] **Step 4: Carry icon + category through `RecommendationItem`.** Add two defaulted fields so existing constructors keep working:

```php
readonly class RecommendationItem
{
    public function __construct(
        public int $productId,
        public string $name,
        public ProductTier $tier,
        public int $quantity,
        public int $lineTotal,
        public string $status,
        public string $icon = '📦',
        public string $category = '',
    ) {}
}
```

In `RecommendationService::toItem()` set `icon: $product->icon, category: $product->category->name` (eager-load `category`: change `Product::with('prices')` → `Product::with(['prices', 'category'])` in `eligible()`). In `withStatus()` pass `icon: $item->icon, category: $item->category` through.

- [ ] **Step 5: Group recommendations by category + readiness in `RecommendationController@index`.** Replace the returned payload so the page can render the quest. Build from `$result->items` (all items, in-plan and deferred):

```php
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

$musts = $items->where('tier', \App\Enums\ProductTier::Must);
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
```

- [ ] **Step 6: Add `icon` to the other product payloads.** In `ProductController@show` add `'icon' => $product->icon` to the `product` array and `'icon' => $p->icon` to each bundle row; in `ExploreController@index` add `'icon' => $p->icon` to each product row; in `PlanController@show` add `'icon' => $l['icon']` to each `items` row (the lines already carry the RecommendationItem — expose `icon`). Keep all existing fields.

- [ ] **Step 7: Tests.** In `StudioStarterSeederTest` assert the rice cooker has an icon:

```php
expect(\App\Models\Product::where('slug', 'rice-cooker')->value('icon'))->toBe('🍚');
```

In `RecommendationsPageTest`, the "renders recommended items" test now reads `categories`/`readiness` — update it to:

```php
$this->get('/recommendations')->assertInertia(fn (Assert $page) => $page
    ->component('Recommendations')
    ->where('budget', 5000)
    ->has('readiness')
    ->has('categories'));
```

Run `./vendor/bin/pest` and fix any other assertion that read the old flat `items` prop.

- [ ] **Step 8: Pint + commit**

```bash
cd /Users/grapetnp/Herd/buy-buddy && ./vendor/bin/pest && ./vendor/bin/pint
git add -A && git commit -m "feat: product icons and category/readiness game metrics"
```

---

## Task 2: Icon tiles + quest UI (Recommendations) + icons everywhere

**Files:**
- Create: `resources/js/Components/IconTile.jsx`, `resources/js/Components/ReadinessMeter.jsx`, `resources/js/Components/Mascot.jsx`
- Modify: `resources/js/Pages/Recommendations.jsx`, `Explore.jsx`, `ProductDetail.jsx`, `MyPlan.jsx`, `Landing.jsx`
- Test: none new (Inertia tests already cover props); `npm run build` must pass

- [ ] **Step 1: `IconTile.jsx`** — colourful emoji sticker in a soft tile:

```jsx
export default function IconTile({ icon, size = 'md' }) {
    const s = size === 'lg' ? 'h-16 w-16 text-3xl' : 'h-11 w-11 text-2xl';
    return (
        <div className={`flex ${s} shrink-0 items-center justify-center rounded-2xl bg-cream-sunk`} aria-hidden="true">
            {icon}
        </div>
    );
}
```

- [ ] **Step 2: `Mascot.jsx`** — emoji placeholder with a `mood` prop (swap for real art later):

```jsx
const faces = { happy: '🛍️', celebrate: '🎉', thinking: '🤔', caring: '🤗' };
export default function Mascot({ mood = 'happy', className = '' }) {
    return <span className={className} role="img" aria-label={`mascot ${mood}`}>{faces[mood] ?? faces.happy}</span>;
}
```

- [ ] **Step 3: `ReadinessMeter.jsx`** — the "ห้องพร้อม X%" goal bar (animated fill + playful copy):

```jsx
import { motion } from 'motion/react';

export default function ReadinessMeter({ percent }) {
    const label = percent >= 100 ? 'ห้องพร้อมอยู่แล้ว! 🎉' : percent >= 50 ? 'ใกล้แล้ว จัดต่อเลย' : 'เริ่มจัดห้องกันเลย';
    return (
        <div className="rounded-2xl bg-brand-50 p-4">
            <div className="flex items-baseline justify-between">
                <span className="text-base font-semibold text-ink">ห้องพร้อม {percent}%</span>
                <span className="text-sm text-brand-700">{label}</span>
            </div>
            <div className="mt-2 h-3 overflow-hidden rounded-full bg-white/70">
                <motion.div className="h-full rounded-full bg-brand" initial={false}
                    animate={{ width: `${percent}%` }} transition={{ duration: 0.5, ease: [0.22, 1, 0.36, 1] }} />
            </div>
        </div>
    );
}
```

- [ ] **Step 4: Recommendations quest layout.** Rewrite `Recommendations.jsx` to read `{ categories, budget, plannedTotal, readiness }`:
  - Top: `<ReadinessMeter percent={readiness.percent} />` then `<BudgetMeter total={plannedTotal} budget={budget} />` with a small `🪙 งบ` coin label.
  - For each category: a header row showing the category name + a progress pill `collected/total` (e.g. `2/4`), turning into a `✓ ครบ!` brand badge when `collected === total && total > 0`.
  - Each item row: `<IconTile icon={item.icon} />` + name + tier badge + price + the existing `+`/`✓` collect button (keep `router.post`/`router.delete`; keep `animate-pop` on `✓`).
  - Keep the "ดูแผนของฉัน" CTA. Use playful microcopy ("เก็บของเข้ากระเป๋า", quest tone).

- [ ] **Step 5: Icons on the other pages.**
  - `Explore.jsx`: add `<IconTile icon={p.icon} />` to each product card (left of the name).
  - `ProductDetail.jsx`: show `<IconTile icon={product.icon} size="lg" />` in the header; small icon on each bundle row.
  - `MyPlan.jsx`: add a small icon before each item name (list + calendar rows) using `it.icon`.
  - `Landing.jsx`: replace the big `🛍️` hero with `<Mascot mood="happy" className="text-6xl" />`.

- [ ] **Step 6: Build, then commit**

```bash
cd /Users/grapetnp/Herd/buy-buddy && npm run build
git add -A && git commit -m "feat: icon tiles and room-setup quest UI"
```

---

## Task 3: Delight — collect feedback, celebrations, playful voice

**Files:**
- Modify: `resources/js/Pages/Recommendations.jsx`, `MyPlan.jsx`; add `resources/js/lib/celebrate.js`
- Dep: `canvas-confetti`

- [ ] **Step 1: Install confetti**

```bash
cd /Users/grapetnp/Herd/buy-buddy && npm i canvas-confetti
```

- [ ] **Step 2: `resources/js/lib/celebrate.js`** — a reduced-motion-aware confetti burst in the warm palette:

```js
import confetti from 'canvas-confetti';

export function celebrate(origin = { x: 0.5, y: 0.4 }) {
    if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) return;
    confetti({
        particleCount: 70,
        spread: 65,
        startVelocity: 32,
        origin,
        colors: ['#FF6B5E', '#FF8A6E', '#FFC7A6', '#10B981', '#FFF1EE'],
        scalar: 0.9,
    });
}
```

- [ ] **Step 3: Fire celebrations on Recommendations.** After a successful add (`router.post(..., { onSuccess })`), if a category just became complete (`collected === total`) or `readiness.percent` reached 100, call `celebrate()` and briefly show a `<Mascot mood="celebrate" />` toast with encouraging copy ("ครัวครบแล้ว! 🎉" / "ห้องพร้อมอยู่แล้ว!"). Compare against the previous props (keep a ref of the prior readiness/category state, or detect from the response). Keep the pop on the collect button.

- [ ] **Step 4: Celebrate back-in-budget on My Plan.** When My Plan renders and `overBudgetBy === 0` after having been over (detect via a small client flag, or simply celebrate once when a removal brings `overBudgetBy` to 0 in an `onSuccess`), call `celebrate()`.

- [ ] **Step 5: Playful microcopy pass.** Across Recommendations/My Plan, warm up the copy toward the quest theme without changing meaning (e.g. add-button `aria-label` "เก็บลงกระเป๋า"; empty state "ยังไม่มีของ เริ่มเก็บกันเลย!"). Keep budget/over-budget copy reassuring (unchanged tone).

- [ ] **Step 6: Build, full suite, Pint, commit**

```bash
cd /Users/grapetnp/Herd/buy-buddy && npm run build && ./vendor/bin/pest && ./vendor/bin/pint
git add -A && git commit -m "feat: collect feedback and cozy celebrations"
```
If Pint changed files, commit `style: pint`.

---

## Self-Review (Plan 8)

**Spec coverage:** product icons everywhere (Task 1 data + Task 2 UI) ✅ · mini-game feel — readiness goal meter + budget-as-coins + per-category quests with completion badges + collect/celebrate delight, cozy and on-brand (Tasks 2–3) ✅ · mood & tone preserved (warm palette, encouraging copy, reduced-motion respected) ✅.

**Type consistency:** `RecommendationItem` gains defaulted `icon`/`category`; `RecommendationController` emits `categories`/`readiness`/`plannedTotal`/`budget` and `Recommendations.jsx` reads exactly those; `icon` added to every product payload matches the `IconTile` prop. New `Spec`-independent — no engine-rule changes.

**Deferred:** sounds, streaks/daily quests, XP/levels across sessions, real mascot art + product photos (swap `Mascot`/`IconTile` when brand assets land), and a full progress screen.

---

## Next
Swap emoji icons for real product images and the mascot for brand art once assets arrive; optional sound + streak mechanics.
