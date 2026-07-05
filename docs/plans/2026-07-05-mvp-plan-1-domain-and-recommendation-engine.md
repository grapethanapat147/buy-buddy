# Grocery List Web App — MVP Plan 1: Domain + Recommendation Engine Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build the Laravel foundation, catalog domain model, and the rule-based recommendation engine (with budget-fitting) for the Grocery List Web App — fully test-driven.

**Architecture:** Laravel 11 app. Catalog is `Category` → `Product` → `ProductPrice`, with self-referencing product pairings for Smart Bundle and JSON trigger rules per product. A pure `RecommendationService` takes a `Spec` value object and returns a `RecommendationResult` (grouped items, planned total, over-budget info) with zero framework coupling, so it is unit-testable in isolation.

**Tech Stack:** PHP 8.3, Laravel 11, Pest 3, Laravel Pint, SQLite (in-memory for tests).

**Source of truth:** `docs/2026-07-05-grocery-list-webapp-design.md` and `docs/2026-07-05-recommendation-logic-and-metrics.md` (this project; not ICW).

**Follow-on plans (separate files, not this one):**
- Plan 2 — Filament admin (manage Category / Product / ProductPrice / pairings)
- Plan 3 — Inertia React frontend (Wizard, Recommendations, Product Detail, My Plan + over-budget states)
- Plan 4 — Guest plan persistence + auth (lossless guest→account save)

---

## File Structure (Plan 1)

- `app/Enums/ProductTier.php` — Must / Recommended / Optional + `priority()`
- `app/Enums/ProductMode.php` — MoveIn / Restock
- `app/Models/Category.php` — catalog grouping
- `app/Models/Product.php` — catalog item + `cheapestPrice()`, `pairedProducts()`
- `app/Models/ProductPrice.php` — per-platform reference price
- `app/Recommendation/Spec.php` — readonly user-spec value object
- `app/Recommendation/TriggerEvaluator.php` — evaluates a product's JSON triggers against a Spec
- `app/Recommendation/RecommendationItem.php` — readonly line (product, qty, lineTotal, tier, status)
- `app/Recommendation/RecommendationResult.php` — readonly aggregate (items, totals, overBudgetBy, mustExceedsBudget)
- `app/Recommendation/RecommendationService.php` — orchestrates filter → scale → sort → budget-fit
- `database/factories/…` + `database/migrations/…` + `database/seeders/StudioStarterSeeder.php`
- `tests/Unit/…` + `tests/Feature/…`

---

## Task 1: Scaffold the Laravel project

**Files:**
- Create: whole Laravel skeleton in `~/Herd/grocery-list-webapp/` (git repo already exists with `docs/`)

- [ ] **Step 1: Create the app into a temp dir, then move files in (repo already has docs/ + .git)**

```bash
cd ~/Herd
composer create-project laravel/laravel grocery-tmp "11.*"
rsync -a --exclude='.git' grocery-tmp/ grocery-list-webapp/
rm -rf grocery-tmp
cd grocery-list-webapp
```

- [ ] **Step 2: Install Pest and Pint**

```bash
composer remove phpunit/phpunit --dev --no-interaction 2>/dev/null || true
composer require pestphp/pest pestphp/pest-plugin-laravel --dev --with-all-dependencies --no-interaction
php artisan pest:install --no-interaction
composer require laravel/pint --dev --no-interaction
```

- [ ] **Step 3: Configure in-memory SQLite for tests**

In `phpunit.xml`, ensure these env lines exist inside `<php>`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

- [ ] **Step 4: Verify the suite runs green**

Run: `./vendor/bin/pest`
Expected: PASS (default example test).

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "chore: scaffold Laravel 11 app with Pest and Pint"
```

---

## Task 2: Product enums

**Files:**
- Create: `app/Enums/ProductTier.php`, `app/Enums/ProductMode.php`
- Test: `tests/Unit/ProductTierTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Enums\ProductTier;

it('orders tiers must before recommended before optional', function () {
    expect(ProductTier::Must->priority())->toBeLessThan(ProductTier::Recommended->priority())
        ->and(ProductTier::Recommended->priority())->toBeLessThan(ProductTier::Optional->priority());
});
```

- [ ] **Step 2: Run test to verify it fails**

Run: `./vendor/bin/pest tests/Unit/ProductTierTest.php`
Expected: FAIL ("Class App\Enums\ProductTier not found").

- [ ] **Step 3: Write the enums**

```php
<?php

namespace App\Enums;

enum ProductTier: string
{
    case Must = 'must';
    case Recommended = 'recommended';
    case Optional = 'optional';

    public function priority(): int
    {
        return match ($this) {
            self::Must => 0,
            self::Recommended => 1,
            self::Optional => 2,
        };
    }
}
```

```php
<?php

namespace App\Enums;

enum ProductMode: string
{
    case MoveIn = 'move_in';
    case Restock = 'restock';
}
```

- [ ] **Step 4: Run test to verify it passes**

Run: `./vendor/bin/pest tests/Unit/ProductTierTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: add ProductTier and ProductMode enums"
```

---

## Task 3: Category model + migration + factory

**Files:**
- Create: migration `..._create_categories_table.php`, `app/Models/Category.php`, `database/factories/CategoryFactory.php`
- Test: `tests/Unit/CategoryTest.php`

- [ ] **Step 1: Generate skeleton**

```bash
php artisan make:model Category -mf --no-interaction
```

- [ ] **Step 2: Write the migration**

In the generated `..._create_categories_table.php` `up()`:

```php
Schema::create('categories', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->string('slug')->unique();
    $table->unsignedInteger('sort_order')->default(0);
    $table->timestamps();
});
```

- [ ] **Step 3: Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'sort_order'];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
```

- [ ] **Step 4: Write the factory**

```php
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'sort_order' => fake()->numberBetween(0, 10),
        ];
    }
}
```

- [ ] **Step 5: Write the test**

```php
<?php

use App\Models\Category;
use App\Models\Product;

it('has many products', function () {
    $category = Category::factory()->create();
    Product::factory()->count(2)->for($category)->create();

    expect($category->products)->toHaveCount(2);
});
```

- [ ] **Step 6: Run — expect FAIL (Product factory not ready yet)**

Run: `./vendor/bin/pest tests/Unit/CategoryTest.php`
Expected: FAIL. This is resolved by Task 4; leave the test and proceed.

- [ ] **Step 7: Commit**

```bash
git add -A && git commit -m "feat: add Category model, migration, factory"
```

---

## Task 4: Product model + migration + factory

**Files:**
- Create: migration, `app/Models/Product.php`, `database/factories/ProductFactory.php`
- Test: `tests/Unit/ProductTest.php` (and Task 3's CategoryTest now passes)

- [ ] **Step 1: Generate skeleton**

```bash
php artisan make:model Product -mf --no-interaction
```

- [ ] **Step 2: Write the migration**

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->foreignId('category_id')->constrained()->cascadeOnDelete();
    $table->string('name');
    $table->string('slug')->unique();
    $table->string('tier');
    $table->string('mode');
    $table->unsignedInteger('ref_price');
    $table->string('restock_cadence')->nullable();
    $table->string('qty_scales_by')->nullable();
    $table->json('triggers')->nullable();
    $table->timestamps();
});
```

- [ ] **Step 3: Write the model**

```php
<?php

namespace App\Models;

use App\Enums\ProductMode;
use App\Enums\ProductTier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'name', 'slug', 'tier', 'mode',
        'ref_price', 'restock_cadence', 'qty_scales_by', 'triggers',
    ];

    protected function casts(): array
    {
        return [
            'tier' => ProductTier::class,
            'mode' => ProductMode::class,
            'ref_price' => 'integer',
            'triggers' => 'array',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function prices(): HasMany
    {
        return $this->hasMany(ProductPrice::class);
    }

    public function pairedProducts(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'product_pairings',
            'product_id',
            'paired_product_id',
        );
    }

    public function cheapestPrice(): ?int
    {
        return $this->prices->min('price') ?? $this->ref_price;
    }
}
```

- [ ] **Step 4: Write the factory**

```php
<?php

namespace Database\Factories;

use App\Enums\ProductMode;
use App\Enums\ProductTier;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'tier' => fake()->randomElement(ProductTier::cases()),
            'mode' => ProductMode::MoveIn,
            'ref_price' => fake()->numberBetween(50, 2000),
            'restock_cadence' => null,
            'qty_scales_by' => null,
            'triggers' => [],
        ];
    }

    public function must(): static
    {
        return $this->state(['tier' => ProductTier::Must]);
    }

    public function optional(): static
    {
        return $this->state(['tier' => ProductTier::Optional]);
    }
}
```

- [ ] **Step 5: Write the test**

```php
<?php

use App\Enums\ProductTier;
use App\Models\Product;

it('casts tier to the enum', function () {
    $product = Product::factory()->must()->create();

    expect($product->tier)->toBe(ProductTier::Must);
});

it('falls back to ref_price when it has no platform prices', function () {
    $product = Product::factory()->create(['ref_price' => 300]);

    expect($product->cheapestPrice())->toBe(300);
});
```

- [ ] **Step 6: Run tests — expect PASS (and CategoryTest now passes too)**

Run: `./vendor/bin/pest tests/Unit/ProductTest.php tests/Unit/CategoryTest.php`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add -A && git commit -m "feat: add Product model, migration, factory"
```

---

## Task 5: ProductPrice + cheapestPrice from platforms

**Files:**
- Create: migration, `app/Models/ProductPrice.php`, `database/factories/ProductPriceFactory.php`
- Test: `tests/Unit/ProductPriceTest.php`

- [ ] **Step 1: Generate skeleton**

```bash
php artisan make:model ProductPrice -mf --no-interaction
```

- [ ] **Step 2: Write the migration**

```php
Schema::create('product_prices', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->string('platform');
    $table->unsignedInteger('price');
    $table->string('url')->nullable();
    $table->timestamps();
});
```

- [ ] **Step 3: Write the model**

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductPrice extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'platform', 'price', 'url'];

    protected function casts(): array
    {
        return ['price' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
```

- [ ] **Step 4: Write the factory**

```php
<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductPriceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'platform' => fake()->randomElement(['Shopee', 'Lazada', 'Makro']),
            'price' => fake()->numberBetween(50, 2000),
            'url' => fake()->url(),
        ];
    }
}
```

- [ ] **Step 5: Write the test**

```php
<?php

use App\Models\Product;
use App\Models\ProductPrice;

it('returns the lowest platform price as cheapest', function () {
    $product = Product::factory()->create(['ref_price' => 999]);
    ProductPrice::factory()->for($product)->create(['price' => 650]);
    ProductPrice::factory()->for($product)->create(['price' => 590]);
    ProductPrice::factory()->for($product)->create(['price' => 610]);

    expect($product->fresh()->cheapestPrice())->toBe(590);
});
```

- [ ] **Step 6: Run — expect PASS**

Run: `./vendor/bin/pest tests/Unit/ProductPriceTest.php`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add -A && git commit -m "feat: add ProductPrice with cheapest-price resolution"
```

---

## Task 6: Product pairings (Smart Bundle relation)

**Files:**
- Create: migration `..._create_product_pairings_table.php`
- Test: `tests/Unit/ProductPairingTest.php`

- [ ] **Step 1: Generate migration**

```bash
php artisan make:migration create_product_pairings_table --no-interaction
```

- [ ] **Step 2: Write the migration**

```php
Schema::create('product_pairings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->foreignId('paired_product_id')->constrained('products')->cascadeOnDelete();
    $table->timestamps();
    $table->unique(['product_id', 'paired_product_id']);
});
```

- [ ] **Step 3: Write the test**

```php
<?php

use App\Models\Product;

it('exposes paired products for a bundle', function () {
    $riceCooker = Product::factory()->create();
    $rice = Product::factory()->create();
    $spoon = Product::factory()->create();

    $riceCooker->pairedProducts()->attach([$rice->id, $spoon->id]);

    expect($riceCooker->pairedProducts->pluck('id'))
        ->toContain($rice->id, $spoon->id)
        ->toHaveCount(2);
});
```

- [ ] **Step 4: Run — expect PASS (relation already defined in Task 4)**

Run: `./vendor/bin/pest tests/Unit/ProductPairingTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: add product pairings for Smart Bundle"
```

---

## Task 7: Spec value object

**Files:**
- Create: `app/Recommendation/Spec.php`
- Test: `tests/Unit/SpecTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Recommendation\Spec;

it('reports whether a product id is already owned', function () {
    $spec = new Spec(budget: 5000, roomType: 'studio', occupants: 1, cooking: 'sometimes', ownedProductIds: [4, 7]);

    expect($spec->owns(4))->toBeTrue()
        ->and($spec->owns(9))->toBeFalse();
});
```

- [ ] **Step 2: Run — expect FAIL**

Run: `./vendor/bin/pest tests/Unit/SpecTest.php`
Expected: FAIL ("Class App\Recommendation\Spec not found").

- [ ] **Step 3: Write the value object**

```php
<?php

namespace App\Recommendation;

readonly class Spec
{
    /**
     * @param  array<int>  $ownedProductIds
     */
    public function __construct(
        public int $budget,
        public string $roomType,
        public int $occupants,
        public string $cooking,
        public array $ownedProductIds = [],
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
        };
    }
}
```

- [ ] **Step 4: Run — expect PASS**

Run: `./vendor/bin/pest tests/Unit/SpecTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: add Spec value object"
```

---

## Task 8: TriggerEvaluator

**Files:**
- Create: `app/Recommendation/TriggerEvaluator.php`
- Test: `tests/Unit/TriggerEvaluatorTest.php`

Trigger rule shape: `['field' => 'cooking', 'op' => 'in', 'value' => ['sometimes','often']]`. Supported ops: `=`, `>=`, `in`. A product with no triggers always passes. All rules must pass (AND).

- [ ] **Step 1: Write the failing tests**

```php
<?php

use App\Recommendation\Spec;
use App\Recommendation\TriggerEvaluator;

function spec(array $overrides = []): Spec
{
    return new Spec(
        budget: $overrides['budget'] ?? 5000,
        roomType: $overrides['roomType'] ?? 'studio',
        occupants: $overrides['occupants'] ?? 1,
        cooking: $overrides['cooking'] ?? 'sometimes',
        ownedProductIds: $overrides['ownedProductIds'] ?? [],
    );
}

it('passes when there are no triggers', function () {
    expect((new TriggerEvaluator)->passes([], spec()))->toBeTrue();
});

it('matches an "in" rule', function () {
    $rules = [['field' => 'cooking', 'op' => 'in', 'value' => ['sometimes', 'often']]];
    expect((new TriggerEvaluator)->passes($rules, spec(['cooking' => 'sometimes'])))->toBeTrue()
        ->and((new TriggerEvaluator)->passes($rules, spec(['cooking' => 'never'])))->toBeFalse();
});

it('matches a ">=" rule on occupants', function () {
    $rules = [['field' => 'occupants', 'op' => '>=', 'value' => 2]];
    expect((new TriggerEvaluator)->passes($rules, spec(['occupants' => 2])))->toBeTrue()
        ->and((new TriggerEvaluator)->passes($rules, spec(['occupants' => 1])))->toBeFalse();
});

it('requires all rules to pass (AND)', function () {
    $rules = [
        ['field' => 'cooking', 'op' => '=', 'value' => 'often'],
        ['field' => 'occupants', 'op' => '>=', 'value' => 2],
    ];
    expect((new TriggerEvaluator)->passes($rules, spec(['cooking' => 'often', 'occupants' => 1])))->toBeFalse();
});
```

- [ ] **Step 2: Run — expect FAIL**

Run: `./vendor/bin/pest tests/Unit/TriggerEvaluatorTest.php`
Expected: FAIL ("Class App\Recommendation\TriggerEvaluator not found").

- [ ] **Step 3: Write the evaluator**

```php
<?php

namespace App\Recommendation;

class TriggerEvaluator
{
    /**
     * @param  array<array{field:string,op:string,value:mixed}>  $triggers
     */
    public function passes(array $triggers, Spec $spec): bool
    {
        foreach ($triggers as $rule) {
            if (! $this->ruleMatches($rule, $spec)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array{field:string,op:string,value:mixed}  $rule
     */
    private function ruleMatches(array $rule, Spec $spec): bool
    {
        $actual = $spec->value($rule['field']);

        return match ($rule['op']) {
            '=' => $actual === $rule['value'],
            '>=' => $actual >= $rule['value'],
            'in' => in_array($actual, $rule['value'], true),
            default => false,
        };
    }
}
```

- [ ] **Step 4: Run — expect PASS**

Run: `./vendor/bin/pest tests/Unit/TriggerEvaluatorTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: add TriggerEvaluator for spec-based filtering"
```

---

## Task 9: RecommendationItem + RecommendationResult DTOs

**Files:**
- Create: `app/Recommendation/RecommendationItem.php`, `app/Recommendation/RecommendationResult.php`
- Test: `tests/Unit/RecommendationResultTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Enums\ProductTier;
use App\Recommendation\RecommendationItem;
use App\Recommendation\RecommendationResult;

it('separates in-plan and deferred items and computes the planned total', function () {
    $inPlan = new RecommendationItem(productId: 1, name: 'A', tier: ProductTier::Must, quantity: 1, lineTotal: 500, status: 'in_plan');
    $deferred = new RecommendationItem(productId: 2, name: 'B', tier: ProductTier::Optional, quantity: 1, lineTotal: 300, status: 'deferred');

    $result = new RecommendationResult(items: [$inPlan, $deferred], budget: 1000, mustExceedsBudget: false);

    expect($result->plannedTotal())->toBe(500)
        ->and($result->overBudgetBy())->toBe(0)
        ->and($result->inPlan())->toHaveCount(1)
        ->and($result->deferred())->toHaveCount(1);
});
```

- [ ] **Step 2: Run — expect FAIL**

Run: `./vendor/bin/pest tests/Unit/RecommendationResultTest.php`
Expected: FAIL.

- [ ] **Step 3: Write the DTOs**

```php
<?php

namespace App\Recommendation;

use App\Enums\ProductTier;

readonly class RecommendationItem
{
    public function __construct(
        public int $productId,
        public string $name,
        public ProductTier $tier,
        public int $quantity,
        public int $lineTotal,
        public string $status,
    ) {}
}
```

```php
<?php

namespace App\Recommendation;

readonly class RecommendationResult
{
    /**
     * @param  array<RecommendationItem>  $items
     */
    public function __construct(
        public array $items,
        public int $budget,
        public bool $mustExceedsBudget,
    ) {}

    /**
     * @return array<RecommendationItem>
     */
    public function inPlan(): array
    {
        return array_values(array_filter($this->items, fn (RecommendationItem $i) => $i->status === 'in_plan'));
    }

    /**
     * @return array<RecommendationItem>
     */
    public function deferred(): array
    {
        return array_values(array_filter($this->items, fn (RecommendationItem $i) => $i->status === 'deferred'));
    }

    public function plannedTotal(): int
    {
        return array_sum(array_map(fn (RecommendationItem $i) => $i->lineTotal, $this->inPlan()));
    }

    public function overBudgetBy(): int
    {
        return max(0, $this->plannedTotal() - $this->budget);
    }
}
```

- [ ] **Step 4: Run — expect PASS**

Run: `./vendor/bin/pest tests/Unit/RecommendationResultTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: add recommendation result DTOs"
```

---

## Task 10: RecommendationService — filter, exclude owned, quantity scaling, tier sort

**Files:**
- Create: `app/Recommendation/RecommendationService.php`
- Test: `tests/Feature/RecommendationServiceTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php

use App\Enums\ProductTier;
use App\Models\Category;
use App\Models\Product;
use App\Recommendation\RecommendationService;
use App\Recommendation\Spec;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function makeProduct(array $attrs): Product
{
    return Product::factory()->for(Category::factory())->create($attrs);
}

it('excludes products the user already owns', function () {
    $keep = makeProduct(['ref_price' => 100, 'tier' => ProductTier::Must, 'triggers' => []]);
    $owned = makeProduct(['ref_price' => 100, 'tier' => ProductTier::Must, 'triggers' => []]);

    $spec = new Spec(budget: 5000, roomType: 'studio', occupants: 1, cooking: 'sometimes', ownedProductIds: [$owned->id]);
    $result = app(RecommendationService::class)->recommend($spec);

    $ids = collect($result->items)->pluck('productId');
    expect($ids)->toContain($keep->id)->not->toContain($owned->id);
});

it('excludes products whose triggers do not match', function () {
    $cook = makeProduct(['tier' => ProductTier::Must, 'triggers' => [['field' => 'cooking', 'op' => 'in', 'value' => ['often']]]]);

    $spec = new Spec(budget: 5000, roomType: 'studio', occupants: 1, cooking: 'never');
    $result = app(RecommendationService::class)->recommend($spec);

    expect(collect($result->items)->pluck('productId'))->not->toContain($cook->id);
});

it('scales quantity for consumables by occupants', function () {
    $detergent = makeProduct(['ref_price' => 60, 'tier' => ProductTier::Must, 'qty_scales_by' => 'occupants', 'triggers' => []]);

    $spec = new Spec(budget: 5000, roomType: 'studio', occupants: 3, cooking: 'sometimes');
    $result = app(RecommendationService::class)->recommend($spec);

    $item = collect($result->items)->firstWhere('productId', $detergent->id);
    expect($item->quantity)->toBe(3)->and($item->lineTotal)->toBe(180);
});

it('orders must-have items before optional ones', function () {
    $optional = makeProduct(['ref_price' => 100, 'tier' => ProductTier::Optional, 'triggers' => []]);
    $must = makeProduct(['ref_price' => 100, 'tier' => ProductTier::Must, 'triggers' => []]);

    $spec = new Spec(budget: 5000, roomType: 'studio', occupants: 1, cooking: 'sometimes');
    $result = app(RecommendationService::class)->recommend($spec);

    expect($result->items[0]->productId)->toBe($must->id);
});
```

- [ ] **Step 2: Run — expect FAIL**

Run: `./vendor/bin/pest tests/Feature/RecommendationServiceTest.php`
Expected: FAIL ("Class App\Recommendation\RecommendationService not found").

- [ ] **Step 3: Write the service (budget-fit added in Task 11; here mark everything in_plan)**

```php
<?php

namespace App\Recommendation;

use App\Enums\ProductTier;
use App\Models\Product;
use Illuminate\Support\Collection;

class RecommendationService
{
    public function __construct(private TriggerEvaluator $triggers) {}

    public function recommend(Spec $spec): RecommendationResult
    {
        $items = $this->eligible($spec)
            ->map(fn (Product $p) => $this->toItem($p, $spec))
            ->sort(function (RecommendationItem $a, RecommendationItem $b) {
                return [$a->tier->priority(), $a->lineTotal] <=> [$b->tier->priority(), $b->lineTotal];
            })
            ->values();

        return $this->fitToBudget($items->all(), $spec->budget);
    }

    /**
     * @return Collection<int, Product>
     */
    private function eligible(Spec $spec): Collection
    {
        return Product::with('prices')
            ->get()
            ->reject(fn (Product $p) => $spec->owns($p->id))
            ->filter(fn (Product $p) => $this->triggers->passes($p->triggers ?? [], $spec));
    }

    private function toItem(Product $product, Spec $spec): RecommendationItem
    {
        $quantity = $product->qty_scales_by === 'occupants' ? $spec->occupants : 1;

        return new RecommendationItem(
            productId: $product->id,
            name: $product->name,
            tier: $product->tier,
            quantity: $quantity,
            lineTotal: $product->cheapestPrice() * $quantity,
            status: 'in_plan',
        );
    }

    /**
     * @param  array<RecommendationItem>  $items
     */
    private function fitToBudget(array $items, int $budget): RecommendationResult
    {
        return new RecommendationResult(items: $items, budget: $budget, mustExceedsBudget: false);
    }
}
```

- [ ] **Step 4: Run — expect PASS**

Run: `./vendor/bin/pest tests/Feature/RecommendationServiceTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: recommendation filtering, scaling, and tier ordering"
```

---

## Task 11: Budget fitting + must-exceeds-budget flag

**Files:**
- Modify: `app/Recommendation/RecommendationService.php` (replace `fitToBudget`)
- Test: `tests/Feature/RecommendationBudgetTest.php`

Rules (from spec §1.4): keep every `Must` item even if it pushes over budget. If musts alone exceed budget → `mustExceedsBudget = true` and no optional/recommended are added. Otherwise add recommended/optional greedily; items that do not fit become `deferred`.

- [ ] **Step 1: Write the failing tests**

```php
<?php

use App\Enums\ProductTier;
use App\Models\Category;
use App\Models\Product;
use App\Recommendation\RecommendationService;
use App\Recommendation\Spec;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

function product(ProductTier $tier, int $price): Product
{
    return Product::factory()->for(Category::factory())->create(['tier' => $tier, 'ref_price' => $price, 'triggers' => []]);
}

it('defers optional items that do not fit the budget', function () {
    product(ProductTier::Must, 800);
    product(ProductTier::Optional, 300);

    $spec = new Spec(budget: 1000, roomType: 'studio', occupants: 1, cooking: 'sometimes');
    $result = app(RecommendationService::class)->recommend($spec);

    expect($result->plannedTotal())->toBe(800)
        ->and($result->deferred())->toHaveCount(1)
        ->and($result->mustExceedsBudget)->toBeFalse();
});

it('keeps all musts and flags when musts alone exceed the budget', function () {
    product(ProductTier::Must, 2000);
    product(ProductTier::Must, 1500);
    product(ProductTier::Optional, 100);

    $spec = new Spec(budget: 3000, roomType: 'studio', occupants: 1, cooking: 'sometimes');
    $result = app(RecommendationService::class)->recommend($spec);

    expect($result->mustExceedsBudget)->toBeTrue()
        ->and($result->plannedTotal())->toBe(3500)
        ->and($result->overBudgetBy())->toBe(500)
        ->and($result->deferred())->toHaveCount(1);
});
```

- [ ] **Step 2: Run — expect FAIL**

Run: `./vendor/bin/pest tests/Feature/RecommendationBudgetTest.php`
Expected: FAIL (optional currently marked in_plan, no deferral).

- [ ] **Step 3: Replace `fitToBudget`**

```php
    /**
     * @param  array<RecommendationItem>  $items
     */
    private function fitToBudget(array $items, int $budget): RecommendationResult
    {
        $musts = array_filter($items, fn (RecommendationItem $i) => $i->tier === ProductTier::Must);
        $rest = array_filter($items, fn (RecommendationItem $i) => $i->tier !== ProductTier::Must);

        $mustTotal = array_sum(array_map(fn (RecommendationItem $i) => $i->lineTotal, $musts));

        $fitted = [];
        foreach ($musts as $item) {
            $fitted[] = $this->withStatus($item, 'in_plan');
        }

        if ($mustTotal > $budget) {
            foreach ($rest as $item) {
                $fitted[] = $this->withStatus($item, 'deferred');
            }

            return new RecommendationResult(items: $fitted, budget: $budget, mustExceedsBudget: true);
        }

        $running = $mustTotal;
        foreach ($rest as $item) {
            if ($running + $item->lineTotal <= $budget) {
                $running += $item->lineTotal;
                $fitted[] = $this->withStatus($item, 'in_plan');
            } else {
                $fitted[] = $this->withStatus($item, 'deferred');
            }
        }

        return new RecommendationResult(items: $fitted, budget: $budget, mustExceedsBudget: false);
    }

    private function withStatus(RecommendationItem $item, string $status): RecommendationItem
    {
        return new RecommendationItem(
            productId: $item->productId,
            name: $item->name,
            tier: $item->tier,
            quantity: $item->quantity,
            lineTotal: $item->lineTotal,
            status: $status,
        );
    }
```

- [ ] **Step 4: Run — expect PASS (whole suite)**

Run: `./vendor/bin/pest`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add -A && git commit -m "feat: budget fitting with must-exceeds-budget handling"
```

---

## Task 12: StudioStarterSeeder (realistic catalog for later phases)

**Files:**
- Create: `database/seeders/StudioStarterSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`
- Test: `tests/Feature/StudioStarterSeederTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

use App\Models\Product;
use Database\Seeders\StudioStarterSeeder;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('seeds a starter catalog with a rice cooker bundle', function () {
    $this->seed(StudioStarterSeeder::class);

    $riceCooker = Product::where('slug', 'rice-cooker')->firstOrFail();

    expect(Product::count())->toBeGreaterThanOrEqual(6)
        ->and($riceCooker->pairedProducts)->not->toBeEmpty()
        ->and($riceCooker->prices)->not->toBeEmpty();
});
```

- [ ] **Step 2: Run — expect FAIL**

Run: `./vendor/bin/pest tests/Feature/StudioStarterSeederTest.php`
Expected: FAIL ("Class Database\Seeders\StudioStarterSeeder not found").

- [ ] **Step 3: Write the seeder**

```php
<?php

namespace Database\Seeders;

use App\Enums\ProductMode;
use App\Enums\ProductTier;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Database\Seeder;

class StudioStarterSeeder extends Seeder
{
    public function run(): void
    {
        $kitchen = Category::create(['name' => 'ครัว', 'slug' => 'kitchen', 'sort_order' => 1]);
        $bedroom = Category::create(['name' => 'เครื่องนอน', 'slug' => 'bedroom', 'sort_order' => 2]);

        $riceCooker = $this->product($kitchen, 'rice-cooker', 'หม้อหุงข้าว 1.8 ลิตร', ProductTier::Must, 590, [
            ['field' => 'cooking', 'op' => 'in', 'value' => ['sometimes', 'often']],
        ]);
        ProductPrice::create(['product_id' => $riceCooker->id, 'platform' => 'Shopee', 'price' => 590]);
        ProductPrice::create(['product_id' => $riceCooker->id, 'platform' => 'Lazada', 'price' => 620]);

        $rice = $this->product($kitchen, 'rice-5kg', 'ข้าวสาร 5 กก.', ProductTier::Recommended, 180, [], ProductMode::Restock, 'monthly');
        $spoon = $this->product($kitchen, 'rice-spoon', 'ทัพพีตักข้าว', ProductTier::Optional, 45, []);
        $riceCooker->pairedProducts()->attach([$rice->id, $spoon->id]);

        $this->product($bedroom, 'mattress-3-5ft', 'ที่นอน 3.5 ฟุต', ProductTier::Must, 1890, []);
        $this->product($bedroom, 'stand-fan', 'พัดลมตั้งพื้น', ProductTier::Must, 690, []);
        $this->product($kitchen, 'detergent', 'ผงซักฟอก', ProductTier::Must, 60, [], ProductMode::Restock, 'weekly', 'occupants');
    }

    /**
     * @param  array<array{field:string,op:string,value:mixed}>  $triggers
     */
    private function product(
        Category $category,
        string $slug,
        string $name,
        ProductTier $tier,
        int $price,
        array $triggers,
        ProductMode $mode = ProductMode::MoveIn,
        ?string $cadence = null,
        ?string $scalesBy = null,
    ): Product {
        return Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => $slug,
            'tier' => $tier,
            'mode' => $mode,
            'ref_price' => $price,
            'restock_cadence' => $cadence,
            'qty_scales_by' => $scalesBy,
            'triggers' => $triggers,
        ]);
    }
}
```

- [ ] **Step 4: Register in DatabaseSeeder `run()`**

```php
$this->call(StudioStarterSeeder::class);
```

- [ ] **Step 5: Run — expect PASS, then format**

Run: `./vendor/bin/pest tests/Feature/StudioStarterSeederTest.php && ./vendor/bin/pint`
Expected: PASS, Pint clean.

- [ ] **Step 6: Commit**

```bash
git add -A && git commit -m "feat: add studio starter catalog seeder"
```

---

## Self-Review (Plan 1)

**Spec coverage** (against `recommendation-logic-and-metrics.md` §1):
- Input Spec (budget/room/occupants/cooking/owned) → Task 7 ✅
- Product data model (category, tier, mode, triggers, pairs_with, qty_scales_by, ref_price, platform_prices) → Tasks 3–6 ✅
- Algorithm: filter (Task 10) ✅ · prioritize by tier (Task 10) ✅ · budget fitting greedy (Task 11) ✅ · cascading bundle relation (Task 6; surfaced in frontend Plan 3) ✅ · quantity scaling (Task 10) ✅
- Over-budget rules: musts kept, must-exceeds flagged, optional deferred (Task 11) ✅
- Cheapest per-item price (Task 5) ✅

**Deferred to later plans (intentional, not placeholders):** Smart Bundle UI, per-store rollup, "why recommended" copy, success-metric instrumentation → Plan 3. Curated-price admin → Plan 2. These are separate subsystems, each its own plan.

**Type consistency:** `RecommendationItem` fields (productId, name, tier, quantity, lineTotal, status) are identical across Tasks 9–11. `Spec::value()` fields match `TriggerEvaluator` rule fields (cooking/occupants/room_type). `cheapestPrice()` defined once (Task 4), exercised in Task 5.

---

## Next
Plans 2–4 (Filament admin, Inertia frontend, guest→account persistence) are written as their own plan files when Plan 1 is green. Each references the wireframes in `docs/wireframes/index.html` and the design spec.
