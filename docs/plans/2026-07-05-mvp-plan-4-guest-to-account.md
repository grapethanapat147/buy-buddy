# Grocery List Web App — MVP Plan 4: Guest → Account Lossless Save

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Let a guest keep their in-progress plan when they create an account or log in — the session plan is persisted to the account and restored on return, with nothing lost.

**Architecture:** A `Plan` model (one per user) stores the user's `spec` (JSON) and a `plan_product` pivot of chosen products. A `PlanRepository` moves data between the guest session and the DB: `save()` on register/save, `load()` on login. Minimal hand-rolled Inertia auth (register/login/logout) using Laravel's `Auth` facade — no Breeze, to avoid disturbing the existing manual Inertia setup. "Lossless" = registering never clears the session plan; it copies it to the account.

**Tech Stack:** Laravel 11 auth, Inertia v3 + React 19, Pest 3.

**Depends on:** Plans 1–3 (models, `PlanSession`, My Plan page, `HandleInertiaRequests`).

**Security note (MVP):** `User::canAccessPanel()` returns true, so any registered user can reach Filament `/admin`. Harden before production (Plan 5). Out of scope here.

---

## File Structure (Plan 4)

- `app/Models/Plan.php` + migrations `..._create_plans_table`, `..._create_plan_product_table` + `database/factories/PlanFactory.php`
- `app/Repositories/PlanRepository.php`
- `app/Support/PlanSession.php` — add `setPlanIds()` + `specArray()`
- `app/Http/Controllers/Auth/RegisteredUserController.php`, `AuthenticatedSessionController.php`
- `app/Http/Requests/{RegisterRequest,LoginRequest}.php`
- `resources/js/Pages/Auth/{Register,Login}.jsx`
- `app/Http/Middleware/HandleInertiaRequests.php` — share `auth.user`
- `resources/js/Pages/MyPlan.jsx` — save button + auth state
- `routes/web.php`, `tests/Feature/*`

---

## Task 1: Plan model + PlanRepository

**Files:**
- Create: `app/Models/Plan.php`, migrations, `database/factories/PlanFactory.php`, `app/Repositories/PlanRepository.php`
- Modify: `app/Support/PlanSession.php`
- Test: `tests/Feature/PlanRepositoryTest.php`

- [ ] **Step 1: Generate model + migrations**

```bash
cd /Users/grapetnp/Herd/grocery-list-webapp
php artisan make:model Plan -mf --no-interaction
php artisan make:migration create_plan_product_table --no-interaction
```

- [ ] **Step 2: plans migration**

```php
Schema::create('plans', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
    $table->json('spec');
    $table->timestamps();
});
```

- [ ] **Step 3: plan_product migration**

```php
Schema::create('plan_product', function (Blueprint $table) {
    $table->id();
    $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
    $table->foreignId('product_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
    $table->unique(['plan_id', 'product_id']);
});
```

- [ ] **Step 4: Plan model** `app/Models/Plan.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'spec'];

    protected function casts(): array
    {
        return ['spec' => 'array'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'plan_product');
    }
}
```

- [ ] **Step 5: PlanFactory** `database/factories/PlanFactory.php`

```php
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'spec' => ['budget' => 5000, 'room_type' => 'studio', 'occupants' => 1, 'cooking' => 'sometimes', 'owned_product_ids' => []],
        ];
    }
}
```

- [ ] **Step 6: PlanSession additions.** Add to `app/Support/PlanSession.php`:

```php
/**
 * @return array<string, mixed>|null
 */
public function specArray(): ?array
{
    return $this->request->session()->get('spec');
}

/**
 * @param  array<int>  $ids
 */
public function setPlanIds(array $ids): void
{
    $this->request->session()->put('plan_ids', array_values(array_unique($ids)));
}
```

- [ ] **Step 7: PlanRepository** `app/Repositories/PlanRepository.php`

```php
<?php

namespace App\Repositories;

use App\Models\Plan;
use App\Models\User;

class PlanRepository
{
    /**
     * @param  array<string, mixed>  $spec
     * @param  array<int>  $productIds
     */
    public function save(User $user, array $spec, array $productIds): Plan
    {
        $plan = Plan::updateOrCreate(['user_id' => $user->id], ['spec' => $spec]);
        $plan->products()->sync($productIds);

        return $plan;
    }

    public function load(User $user): ?Plan
    {
        return Plan::where('user_id', $user->id)->with('products')->first();
    }
}
```

- [ ] **Step 8: Test** `tests/Feature/PlanRepositoryTest.php`

```php
<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Repositories\PlanRepository;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('saves and updates a single plan per user with synced products', function () {
    $user = User::factory()->create();
    $a = Product::factory()->for(Category::factory())->create();
    $b = Product::factory()->for(Category::factory())->create();
    $repo = new PlanRepository;

    $spec = ['budget' => 5000, 'room_type' => 'studio', 'occupants' => 1, 'cooking' => 'sometimes', 'owned_product_ids' => []];
    $repo->save($user, $spec, [$a->id, $b->id]);
    $repo->save($user, $spec, [$a->id]); // update, not duplicate

    expect(\App\Models\Plan::where('user_id', $user->id)->count())->toBe(1);
    expect($repo->load($user)->products->pluck('id')->all())->toBe([$a->id]);
});
```

- [ ] **Step 9: Run — expect PASS**

Run: `./vendor/bin/pest tests/Feature/PlanRepositoryTest.php`
Expected: PASS.

- [ ] **Step 10: Commit**

```bash
git add -A && git commit -m "feat: Plan model and repository for saved plans"
```

---

## Task 2: Registration with lossless save

**Files:**
- Create: `app/Http/Requests/RegisterRequest.php`, `app/Http/Controllers/Auth/RegisteredUserController.php`, `resources/js/Pages/Auth/Register.jsx`
- Modify: `routes/web.php`
- Test: `tests/Feature/Auth/RegisterTest.php`

- [ ] **Step 1: RegisterRequest** `app/Http/Requests/RegisterRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }
}
```

- [ ] **Step 2: Controller** `app/Http/Controllers/Auth/RegisteredUserController.php`

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use App\Repositories\PlanRepository;
use App\Support\PlanSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(RegisterRequest $request, PlanSession $session, PlanRepository $plans): RedirectResponse
    {
        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'password' => Hash::make($request->string('password')->toString()),
        ]);

        Auth::login($user);

        $spec = $session->specArray();
        if ($spec) {
            $plans->save($user, $spec, $session->planIds());
        }

        return redirect()->route('plan.show');
    }
}
```

- [ ] **Step 3: Register page** `resources/js/Pages/Auth/Register.jsx`

```jsx
import { useForm } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({ name: '', email: '', password: '', password_confirmation: '' });
    const submit = (e) => { e.preventDefault(); post('/register'); };
    const field = 'mt-1 w-full rounded-lg border border-neutral-200 p-2';

    return (
        <AppLayout>
            <form onSubmit={submit}>
                <h1 className="text-lg font-medium">สมัครเพื่อเซฟแผน</h1>
                <p className="mt-1 text-sm text-neutral-500">แผนที่จัดไว้จะถูกเก็บให้อัตโนมัติ ไม่หาย</p>
                <label className="mt-4 block text-sm text-neutral-600">ชื่อ</label>
                <input value={data.name} onChange={(e) => setData('name', e.target.value)} className={field} />
                {errors.name && <p className="text-sm text-rose-600">{errors.name}</p>}
                <label className="mt-3 block text-sm text-neutral-600">อีเมล</label>
                <input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} className={field} />
                {errors.email && <p className="text-sm text-rose-600">{errors.email}</p>}
                <label className="mt-3 block text-sm text-neutral-600">รหัสผ่าน</label>
                <input type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} className={field} />
                {errors.password && <p className="text-sm text-rose-600">{errors.password}</p>}
                <label className="mt-3 block text-sm text-neutral-600">ยืนยันรหัสผ่าน</label>
                <input type="password" value={data.password_confirmation} onChange={(e) => setData('password_confirmation', e.target.value)} className={field} />
                <button type="submit" disabled={processing} className="mt-6 w-full rounded-lg bg-neutral-800 p-3 font-medium text-white">สมัครและเซฟแผน</button>
            </form>
        </AppLayout>
    );
}
```

- [ ] **Step 4: Routes** — add to `routes/web.php`:

```php
use App\Http\Controllers\Auth\RegisteredUserController;

Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
Route::post('/register', [RegisteredUserController::class, 'store']);
```

- [ ] **Step 5: Test** `tests/Feature/Auth/RegisterTest.php`

```php
<?php

use App\Models\Category;
use App\Models\Product;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('registers a guest and saves their session plan without losing it', function () {
    $product = Product::factory()->for(Category::factory())->create();
    session([
        'spec' => ['budget' => 5000, 'room_type' => 'studio', 'occupants' => 1, 'cooking' => 'sometimes', 'owned_product_ids' => []],
        'plan_ids' => [$product->id],
    ]);

    $this->post('/register', [
        'name' => 'Fah', 'email' => 'fah@example.com',
        'password' => 'password123', 'password_confirmation' => 'password123',
    ])->assertRedirect(route('plan.show'));

    $this->assertAuthenticated();
    $user = \App\Models\User::where('email', 'fah@example.com')->firstOrFail();
    $plan = \App\Models\Plan::where('user_id', $user->id)->first();
    expect($plan)->not->toBeNull();
    expect($plan->products->pluck('id')->all())->toBe([$product->id]);
    // lossless: session plan still present
    expect(session('plan_ids'))->toBe([$product->id]);
});
```

- [ ] **Step 6: Build + test**

```bash
cd /Users/grapetnp/Herd/grocery-list-webapp && npm run build && ./vendor/bin/pest tests/Feature/Auth/RegisterTest.php
```
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add -A && git commit -m "feat: registration saves the guest plan losslessly"
```

---

## Task 3: Login (restore plan) + logout

**Files:**
- Create: `app/Http/Requests/LoginRequest.php`, `app/Http/Controllers/Auth/AuthenticatedSessionController.php`, `resources/js/Pages/Auth/Login.jsx`
- Modify: `routes/web.php`
- Test: `tests/Feature/Auth/LoginTest.php`

- [ ] **Step 1: LoginRequest** `app/Http/Requests/LoginRequest.php`

```php
<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }
}
```

- [ ] **Step 2: Controller** `app/Http/Controllers/Auth/AuthenticatedSessionController.php`

```php
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
            $request->session()->put('spec', $plan->spec);
            $request->session()->put('plan_ids', $plan->products->pluck('id')->all());
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
```

- [ ] **Step 3: Login page** `resources/js/Pages/Auth/Login.jsx`

```jsx
import { useForm, Link } from '@inertiajs/react';
import AppLayout from '@/Layouts/AppLayout';

export default function Login() {
    const { data, setData, post, processing, errors } = useForm({ email: '', password: '' });
    const submit = (e) => { e.preventDefault(); post('/login'); };
    const field = 'mt-1 w-full rounded-lg border border-neutral-200 p-2';

    return (
        <AppLayout>
            <form onSubmit={submit}>
                <h1 className="text-lg font-medium">เข้าสู่ระบบ</h1>
                <label className="mt-4 block text-sm text-neutral-600">อีเมล</label>
                <input type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} className={field} />
                {errors.email && <p className="text-sm text-rose-600">{errors.email}</p>}
                <label className="mt-3 block text-sm text-neutral-600">รหัสผ่าน</label>
                <input type="password" value={data.password} onChange={(e) => setData('password', e.target.value)} className={field} />
                <button type="submit" disabled={processing} className="mt-6 w-full rounded-lg bg-neutral-800 p-3 font-medium text-white">เข้าสู่ระบบ</button>
                <Link href="/register" className="mt-3 block text-center text-sm text-neutral-500">ยังไม่มีบัญชี? สมัคร</Link>
            </form>
        </AppLayout>
    );
}
```

- [ ] **Step 4: Routes** — add to `routes/web.php`:

```php
use App\Http\Controllers\Auth\AuthenticatedSessionController;

Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
```

- [ ] **Step 5: Test** `tests/Feature/Auth/LoginTest.php`

```php
<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Repositories\PlanRepository;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('logs in and restores the saved plan into the session', function () {
    $user = User::factory()->create(['password' => bcrypt('password123')]);
    $product = Product::factory()->for(Category::factory())->create();
    (new PlanRepository)->save($user, ['budget' => 7000, 'room_type' => 'studio', 'occupants' => 2, 'cooking' => 'often', 'owned_product_ids' => []], [$product->id]);

    $this->post('/login', ['email' => $user->email, 'password' => 'password123'])
        ->assertRedirect(route('plan.show'));

    $this->assertAuthenticatedAs($user);
    expect(session('plan_ids'))->toBe([$product->id]);
    expect(session('spec')['budget'])->toBe(7000);
});

it('rejects bad credentials', function () {
    $user = User::factory()->create(['password' => bcrypt('password123')]);

    $this->post('/login', ['email' => $user->email, 'password' => 'wrong'])
        ->assertSessionHasErrors('email');
});
```

- [ ] **Step 6: Build + test**

```bash
cd /Users/grapetnp/Herd/grocery-list-webapp && npm run build && ./vendor/bin/pest tests/Feature/Auth/LoginTest.php
```
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add -A && git commit -m "feat: login restores the saved plan, plus logout"
```

---

## Task 4: My Plan save button + auth state

**Files:**
- Modify: `app/Http/Middleware/HandleInertiaRequests.php`, `app/Http/Controllers/PlanController.php`, `routes/web.php`, `resources/js/Pages/MyPlan.jsx`
- Test: `tests/Feature/PlanSaveTest.php`

- [ ] **Step 1: Share auth user.** In `HandleInertiaRequests::share()` add to the returned array:

```php
'auth' => [
    'user' => $request->user() ? ['id' => $request->user()->id, 'name' => $request->user()->name] : null,
],
```

- [ ] **Step 2: Save action.** Add to `app/Http/Controllers/PlanController.php`:

```php
use App\Repositories\PlanRepository;
use Illuminate\Http\RedirectResponse;
```

```php
public function save(PlanSession $session, PlanRepository $plans): RedirectResponse
{
    $spec = $session->specArray();
    abort_if($spec === null, 400);

    $plans->save(request()->user(), $spec, $session->planIds());

    return back();
}
```

- [ ] **Step 3: Route (auth-only)** — add to `routes/web.php`:

```php
Route::post('/plan/save', [PlanController::class, 'save'])->middleware('auth')->name('plan.save');
```

- [ ] **Step 4: My Plan footer.** In `resources/js/Pages/MyPlan.jsx`, import `usePage`, `Link`, and add a save area before the closing `</AppLayout>`:

Update the import line:

```jsx
import { router, usePage, Link } from '@inertiajs/react';
```

Add inside the component, before `return`:

```jsx
    const { auth } = usePage().props;
```

Add just before `</AppLayout>` (after the items block):

```jsx
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
```

- [ ] **Step 5: Test** `tests/Feature/PlanSaveTest.php`

```php
<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('shows no auth user to guests on my plan', function () {
    session(['spec' => ['budget' => 5000, 'room_type' => 'studio', 'occupants' => 1, 'cooking' => 'sometimes', 'owned_product_ids' => []], 'plan_ids' => []]);

    $this->get('/plan')->assertInertia(fn (Assert $page) => $page->where('auth.user', null));
});

it('lets an authenticated user save the current plan', function () {
    $user = User::factory()->create();
    $product = Product::factory()->for(Category::factory())->create();
    session([
        'spec' => ['budget' => 5000, 'room_type' => 'studio', 'occupants' => 1, 'cooking' => 'sometimes', 'owned_product_ids' => []],
        'plan_ids' => [$product->id],
    ]);

    $this->actingAs($user)->post('/plan/save')->assertRedirect();

    expect(\App\Models\Plan::where('user_id', $user->id)->first()->products->pluck('id')->all())->toBe([$product->id]);
});

it('blocks guests from the save endpoint', function () {
    $this->post('/plan/save')->assertRedirect(route('login'));
});
```

- [ ] **Step 6: Build, full suite, Pint, commit**

```bash
cd /Users/grapetnp/Herd/grocery-list-webapp && npm run build && ./vendor/bin/pest && ./vendor/bin/pint
git add -A && git commit -m "feat: my plan save button with guest and authed states"
```
If Pint changed files, commit `style: pint`.

---

## Self-Review (Plan 4)

**Spec coverage:** persist plan to account (Task 1) ✅ · register saves guest plan losslessly — session plan retained AND copied to DB (Task 2, asserted) ✅ · login restores saved plan into session (Task 3, asserted) ✅ · logout (Task 3) ✅ · My Plan shows guest vs authed save affordance + authed save endpoint guarded by `auth` middleware (Task 4) ✅.

**Type consistency:** `PlanRepository::save(User, array $spec, array $ids)` called identically from `RegisteredUserController` and `PlanController::save`; `spec` is the same array shape stored by `PlanSession::setSpec()` / read by `specArray()` and consumed by `Spec` (budget/room_type/occupants/cooking/owned_product_ids). `Plan.spec` JSON cast round-trips that array.

**Deferred (follow-on / Plan 5):** merge guest plan with an existing saved plan on login (currently login overwrites session from the saved plan), password reset, email verification, and hardening `canAccessPanel`.

---

## Next
Plan 5 — harden admin access, plus Explore/Browse and the Restock calendar tab.
