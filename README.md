# BuyBuddy

> Your buddy for buying what you actually need — within budget.

BuyBuddy is a **shopping planner and advisor** (not a store) for people moving into a rented room — new grads and first-jobbers who don't know what to buy, what's worth it, or how to stay inside their budget. It recommends what to get based on your situation, shows the cheapest reference price across stores, suggests the things you'll need alongside each item, and helps you plan one-off move-in buys and recurring restocks. Then it sends you off to a real store to buy. **It helps you decide — it doesn't sell.**

## Features

- **Spec-based recommendations** — answer a short wizard (budget, room, occupants, cooking habits) and get a tailored starter kit, grouped by room area, prioritised by what's essential.
- **Budget-aware planning** — a persistent budget meter and three honest over-budget states: auto-suggest deferrals, manual trimming, and a "your essentials alone exceed budget" path that never silently cuts must-haves.
- **Smart Bundle** — "often bought with" chains (rice cooker → rice → storage box).
- **Price comparison** — cheapest curated reference price per item, plus a "buy everything at this store" per-platform rollup.
- **Move-in & Restock modes** — one-off setup vs. recurring buys, with a calendar view that groups restocks by weekly/monthly cadence.
- **Explore** — browse the whole catalog by category or search for people who already know what they want.
- **Guest-first accounts** — build a plan without signing up; register or log in to save it, and your in-progress plan is never lost (it merges, not overwrites).
- **Admin** — a Filament panel to curate the catalog, prices, bundles, and recommendation trigger rules.

## Tech stack

- **Backend:** Laravel 11 (PHP 8.3)
- **Admin:** Filament 3
- **Frontend:** Inertia.js v3 + React 19, Vite, Tailwind CSS 3
- **Tests:** Pest 3
- **Database:** SQLite (dev) — swap for MySQL/Postgres in production

## Getting started

```bash
git clone git@github.com:grapethanapat147/buy-buddy.git
cd buy-buddy

composer install
npm install

cp .env.example .env
php artisan key:generate

# SQLite (default): create the file, then migrate + seed a demo catalog
touch database/database.sqlite
php artisan migrate --seed

npm run build   # or: npm run dev  (for HMR during development)
php artisan serve
```

Visit the served URL (or `buy-buddy.test` under Laravel Herd).

**Admin panel:** `/admin` — seeded login `admin@grocery.test` / `password` (admin access is gated by the `is_admin` flag; regular registered users get a 403).

## Testing

```bash
./vendor/bin/pest          # full suite
./vendor/bin/pint          # code style
```

## Project structure

```
app/
  Recommendation/     Pure domain logic — Spec, TriggerEvaluator,
                      RecommendationService (budget-fit), PlanAdvisor, StoreRollup
  Models/             Category, Product, ProductPrice, Plan, User
  Filament/           Admin resources (Category, Product + prices/pairings)
  Http/Controllers/   Wizard, Recommendation, Product, Plan, Explore, Auth
resources/js/
  Pages/              Inertia React pages (Wizard, Recommendations,
                      ProductDetail, MyPlan, Explore, Auth)
  Layouts/ Components/
docs/
  *-design.md               Product/UX design spec
  recommendation-logic*.md  Rule-based engine + success metrics
  wireframes/index.html     Standalone wireframes (open in a browser)
  plans/                    TDD implementation plans (Plans 1–6)
.claude/skills/       Project design skills (taste, motion, tailwind, a11y)
```

## Design direction

BuyBuddy leans **warm, friendly, and trustworthy** — a helpful buddy, not a cold utility. Design work should reach for delight (motion, micro-interactions, playful-but-clear copy) while keeping money and budget states legible and reassuring. See `.claude/skills/` for the design taste, motion, Tailwind pattern, and accessibility guides.

## Roadmap

- Restock reminder notifications
- Password reset & email verification
- Empty / error / legal pages
- Internationalisation (TH/EN)
- Basket optimiser that factors in shipping and free-shipping thresholds
- Live price integrations (affiliate APIs) beyond curated reference prices

## Status

MVP — a working full-stack app (recommendation engine, admin, frontend, auth) with a green test suite. Built plan-by-plan; see `docs/plans/`.

---

_BuyBuddy is an advisor. It recommends and plans; the actual purchase happens on the store you choose. Store rankings are by genuine value, and outbound links may be affiliate links._
