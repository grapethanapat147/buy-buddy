# BuyBuddy — Brand Identity

> **How to use this file:** it's the single source of truth for BuyBuddy's look and voice.
> The values below are the **current in-code defaults** (placeholders). Replace them with your
> real brand, and drop assets into `logo/`, `mascot/`, and `fonts/`. When you're done, tell the
> agent "apply the brand" — it will map everything below into `tailwind.config.js`,
> `resources/`, and the components. Fields marked **⟵ FILL** need your input.

---

## 1. Colours

Map each role to a hex. These feed `tailwind.config.js` → `theme.extend.colors` (tokens `brand`, `cream`, `ink`) and the semantic Tailwind colours.

| Role | Token / where it's used | Current (placeholder) | Your value |
|---|---|---|---|
| Primary / brand | `brand.600` — CTAs, logo, highlights | `#FF6B5E` | ⟵ FILL |
| Primary hover | `brand.500` | `#FF8A6E` | ⟵ FILL |
| Primary tint | `brand.50` — chips, selected states | `#FFF1EE` | ⟵ FILL |
| Page background | `cream.DEFAULT` | `#FFF9F4` | ⟵ FILL |
| Card surface | `cream.card` | `#FFFFFF` | ⟵ FILL |
| Sunk surface | `cream.sunk` — rollup pills, inputs bg | `#FFF3EA` | ⟵ FILL |
| Text primary | `ink.DEFAULT` | `#2B2724` | ⟵ FILL |
| Text secondary | `ink.soft` | `#7A716B` | ⟵ FILL |
| Text muted | `ink.muted` | `#A79E97` | ⟵ FILL |
| Success / in-budget | Tailwind `emerald` | `#10B981` | ⟵ FILL (or keep) |
| Warning / near-limit | Tailwind `amber` | `#F59E0B` | ⟵ FILL (or keep) |
| Danger / over-budget | Tailwind `rose` | `#F43F5E` | ⟵ FILL (or keep) |

> Keep semantic colours (success/warning/danger) meaningful — they signal budget states, not decoration. Note any gradients here if the brand uses them (we currently use flat fills only).

## 2. Typography

| Item | Current | Your value |
|---|---|---|
| Primary typeface | Plus Jakarta Sans | ⟵ FILL (name) |
| Heading font (if different) | same | ⟵ FILL |
| Weights used | 400 (regular), 600 (semibold) | ⟵ FILL |
| Thai support required? | yes | ⟵ FILL |
| Delivery | Google Fonts link in `app.blade.php` | ⟵ `.woff2` files in `fonts/` (preferred) or Google Font name |

Type scale (px): 12 / 14 / 16 (body) / 20 / 24 / 32. Numbers use `tabular-nums`. Change here if your brand differs.

## 3. Shape and depth

| Item | Current | Your value |
|---|---|---|
| Card radius | `rounded-2xl` = 20px | ⟵ FILL |
| Control radius | buttons = full pill, inputs = 14px | ⟵ FILL |
| Card shadow | `shadow-soft` = `0 2px 16px -4px rgba(43,39,36,0.10)` | ⟵ FILL |
| Hover shadow | `shadow-lift` = `0 8px 28px -8px rgba(43,39,36,0.18)` | ⟵ FILL |
| Gradients / textures | none (flat) | ⟵ FILL |

## 4. Logo

Drop into `brand/logo/` (see its README). Provide **SVG** where possible:
- `logo.svg` — full colour lockup (mark + wordmark)
- `logo-mono.svg` — single-colour (for dark/photo backgrounds)
- `mark.svg` — mark only (favicon, small header, app icon)

Clear-space and minimum-size rules: ⟵ FILL.
Currently the header shows a `🛍️` emoji + "BuyBuddy" text (`resources/js/Layouts/AppLayout.jsx`) as a placeholder — the mark replaces the emoji.

## 5. Mascot

Drop into `brand/mascot/` (see its README). **SVG** if vector, **transparent PNG** (@2x) if rendered. Same body proportions/baseline across poses so they swap cleanly.

Poses tied to app moments:

| Pose | Used when | Suggested file |
|---|---|---|
| Wave / greet | landing, empty states | `mascot-wave` |
| Celebrate | back within budget, plan complete | `mascot-celebrate` |
| Thinking / advising | recommendations, "why recommended" | `mascot-thinking` |
| Caring / reassuring | over-budget (must NOT look scolding) | `mascot-worried` |
| Holding bag / showing | product, bundle | `mascot-holding` |

Mascot name / personality notes: ⟵ FILL.

## 6. Iconography and illustration

- Icon style (line/solid/duotone), stroke weight, corner style: ⟵ FILL
- Illustration style notes: ⟵ FILL
- (We don't ship an icon set yet — a couple of emoji are used as placeholders. Note a preferred icon library or a custom set here.)

## 7. Motion

| Item | Current | Your value |
|---|---|---|
| Standard duration | 150–300ms | ⟵ FILL |
| Easing | `cubic-bezier(0.22, 1, 0.36, 1)` (ease-out) | ⟵ FILL |
| Signature moments | budget meter count-up, add→✓ pop, celebrate | ⟵ FILL |
| Reduced-motion | respected globally (`MotionConfig reducedMotion="user"`) | keep |

## 8. Voice and tone

- **Personality:** warm, friendly buddy — helpful, a little playful, trustworthy with money.
- **Language:** Thai-first. ⟵ FILL (EN too?)
- **On money/over-budget:** reassure, never scold ("เกินงบนิดเดียว ปรับได้ง่าย ๆ").
- **Celebrate wins** ("จัดครบแล้ว 🎉"), keep confirmations short.
- Sample phrases / words to use / words to avoid: ⟵ FILL

## 9. Do / Don't

**Do:** ⟵ FILL (e.g. keep the coral for one primary action per screen; lots of warm cream space)
**Don't:** ⟵ FILL (e.g. don't use the mascot on every screen; don't signal state by colour alone)

---

_Once filled and assets dropped in, ask the agent to "apply the brand". Nothing here changes the app until then._
