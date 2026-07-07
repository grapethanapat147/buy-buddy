---
name: design-taste
description: Visual craft and taste for BuyBuddy's UI. Use when designing, redesigning, styling, or polishing any page or component — establishing hierarchy, colour, typography, spacing, shadows, and the warm/friendly brand feel, and avoiding a generic AI look. Invoke before writing UI code, and pair with motion-design for interaction.
---

# BuyBuddy — Design Taste

BuyBuddy is a **friendly buddy who helps you shop smart on a budget**. The feeling to hit: **warm, reassuring, a little playful, and unmistakably trustworthy with money.** Never cold, never corporate, never anxiety-inducing — especially on budget and price screens, where the user is worried about spending too much.

The current codebase ships in neutral grays as scaffolding. When you touch a screen, elevate it toward the identity below.

## Brand identity

**Palette** (define as Tailwind theme tokens in `tailwind.config.js`, don't scatter hex):
- `brand` coral — primary actions, the logo, highlights. `#FF6B5E` (600) with `#FF8A6E` (500) hover, `#FFF1EE` (50) tint.
- `cream` surfaces — page background `#FFF9F4`, cards `#FFFFFF`, subtle fills `#FFF3EA`.
- `ink` text — primary `#2B2724`, secondary `#7A716B`, muted `#A79E97` (warm grays, not blue-grays).
- **Semantic, reserved for meaning only:** success/in-budget `emerald`, warning/near-limit `amber`, danger/over-budget `rose`. Never use these decoratively — a red thing must *mean* over-budget.
- Tier accents: must = rose tint, recommended = amber tint, optional = neutral. Keep these consistent everywhere they appear.

**Type:** one warm humanist sans (e.g. `Plus Jakarta Sans`, `Nunito`, or `General Sans`) for everything; a slightly rounded face reinforces "friendly". Two weights only — 400 and 600. Scale: 12 / 14 / 16 body / 20 / 24 / 32. Numbers (prices, budgets) are first-class — give them tabular figures (`font-variant-numeric: tabular-nums`) so meters and totals don't jitter.

**Shape & depth:** generous radii (`rounded-xl` / `rounded-2xl` for cards, pill buttons), soft low-contrast shadows (`shadow-[0_2px_16px_-4px_rgba(43,39,36,0.10)]`) — never harsh black drop shadows. Flat fills over gradients, except one optional soft brand wash on hero moments.

**Voice/microcopy:** warm and human, contractions welcome, Thai-first. Celebrate ("จัดครบแล้ว 🎉"), reassure on over-budget ("เกินงบนิดเดียว ปรับได้ง่าย ๆ"), never scold. A tiny bit of personality (the "buddy") in empty states and confirmations.

## Craft rules (the difference between fine and great)

1. **Hierarchy first.** Each screen has exactly one primary thing. Size, weight, colour, and whitespace should make it obvious in a half-second squint. If everything is bold, nothing is.
2. **Spacing is the design.** Use a consistent 4px scale (4/8/12/16/24/32). Group related things tight, separate unrelated things with air. Whitespace > borders for separation; reach for a hairline border only when air isn't enough.
3. **Fewer borders, softer surfaces.** Prefer surface/elevation changes over lines. When you do use a border, `0.5–1px` at low contrast.
4. **Colour is meaning, not decoration.** Most of the UI is cream + ink; brand coral and the semantic colours are seasoning. A screen that's 90% neutral with a single confident coral CTA beats a rainbow.
5. **Money must be legible and calm.** Budget meter, totals, and price deltas are the emotional core — big, clear, tabular numbers; the over-budget colour shift should read instantly but the copy stays kind.
6. **Alignment & rhythm.** Everything sits on a grid; optical-align icons with text; consistent card padding across the app. Misalignment is the #1 tell of AI-generated UI.
7. **Density that breathes on mobile.** Design mobile-first (this is a phone-shaped app). Tap targets ≥ 44px, comfortable line-height (1.5–1.7 for body).

## Avoid the generic-AI look

- ❌ Purple/indigo gradient hero, glassmorphism everywhere, three equal-weight CTAs, centered everything, emoji as the only personality, `shadow-2xl` on every card, identical spacing top-to-bottom.
- ✅ A committed warm palette, one clear focal point per screen, intentional asymmetry, real content hierarchy, restraint.

## Workflow

1. Name the screen's **one job** and its **one primary action**.
2. Pick the **emotional read** (reassuring? celebratory? focused?) — it drives colour temperature and motion.
3. Lay out with spacing + hierarchy in neutral, then add the **single** brand/semantic accent.
4. Hand off to `motion-design` for states and delight, and `web-accessibility` for contrast + focus.
5. Squint test: is the focal point obvious? Does it feel like a friendly buddy, or a form?

When exploring visual directions, produce 2–3 distinct options rather than one — vary the accent usage, density, and personality, then pick.
