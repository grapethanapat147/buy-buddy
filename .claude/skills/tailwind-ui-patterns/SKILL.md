---
name: tailwind-ui-patterns
description: Reusable Tailwind CSS 3 + React 19 component and layout patterns for BuyBuddy. Use when building or refactoring UI components (buttons, cards, inputs, tabs, badges, meters, sheets), setting up theme tokens, or keeping styling consistent and DRY. Pair with design-taste (look) and motion-design (feel).
---

# BuyBuddy — Tailwind UI Patterns

Keep styling **consistent, tokenised, and DRY**. Design decisions live in `tailwind.config.js` (colours, radii, shadows, fonts, keyframes) — components reference tokens, not raw hex or magic numbers. Extract any class string used 3+ times into a small React component.

## Theme setup (do this first)

Put the brand identity into `tailwind.config.js` `theme.extend` so `bg-brand`, `text-ink`, `bg-cream` etc. exist:

```js
colors: {
  brand:  { DEFAULT: '#FF6B5E', 50: '#FFF1EE', 500: '#FF8A6E', 600: '#FF6B5E', 700: '#E5533F' },
  cream:  { DEFAULT: '#FFF9F4', card: '#FFFFFF', sunk: '#FFF3EA' },
  ink:    { DEFAULT: '#2B2724', soft: '#7A716B', muted: '#A79E97' },
},
borderRadius: { xl: '14px', '2xl': '20px' },
boxShadow: { soft: '0 2px 16px -4px rgba(43,39,36,0.10)', lift: '0 8px 28px -8px rgba(43,39,36,0.18)' },
fontFamily: { sans: ['Plus Jakarta Sans', 'ui-sans-serif', 'system-ui'] },
```

Load the font in `resources/css/app.css` (self-host or Google Fonts). Set `body` to `bg-cream text-ink font-sans`.

## Component conventions

- **Small, single-purpose components** in `resources/js/Components/`. Props over copy-paste. A `Button`, `Card`, `Badge`, `TierBadge`, `Money`, `BudgetMeter` cover most of the app.
- **Variant maps, not conditionals soup.** Define a `const styles = { primary: '…', ghost: '…' }` and index by prop.
- **`clsx` for conditional classes** (propose `npm i clsx` if not present) — cleaner than template strings.
- **Money is a component.** `<Money amount={1890} />` renders `฿1,890` with `tabular-nums` so meters/totals never jitter. Centralise formatting.

## Canonical snippets

**Button (pill, warm, tactile):**
```jsx
const variants = {
  primary: 'bg-brand text-white hover:bg-brand-500 shadow-soft',
  ghost:   'border border-ink/10 text-ink hover:bg-cream-sunk',
  quiet:   'text-ink-soft hover:text-ink',
};
export default function Button({ variant = 'primary', className = '', ...p }) {
  return <button {...p}
    className={`inline-flex items-center justify-center gap-2 rounded-full px-4 py-2.5 text-sm font-semibold
      transition active:scale-[0.98] focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand/40
      disabled:opacity-50 ${variants[variant]} ${className}`} />;
}
```

**Card:** `bg-cream-card rounded-2xl shadow-soft p-4` (add `transition hover:shadow-lift hover:-translate-y-0.5` for interactive cards).

**Input:** `w-full rounded-xl border border-ink/10 bg-cream-card px-3 py-2.5 text-sm placeholder:text-ink-muted focus:border-brand focus:ring-2 focus:ring-brand/20 focus:outline-none`.

**Tier badge:** `inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold` + tier map `{ must:'bg-rose-50 text-rose-700', recommended:'bg-amber-50 text-amber-700', optional:'bg-ink/5 text-ink-soft' }`.

**Tabs (used on My Plan):** underline style — active `border-b-2 border-brand font-semibold`, inactive `text-ink-soft`. Client `useState`; animate the underline with a sliding indicator when adding flair (see motion-design).

**Budget meter:** track `h-2.5 rounded-full bg-ink/8`; fill `h-full rounded-full transition-[width,background-color] duration-300` with colour by state (`bg-emerald-500` / `bg-amber-500` / `bg-rose-500`).

## Layout

- **Mobile-first, phone-shaped app.** Content column `mx-auto max-w-xl px-4`; the whole app reads like a mobile screen even on desktop.
- **Responsive grids:** `grid gap-3 sm:grid-cols-2` for product lists; never fixed pixel widths that overflow — use `min-w-0` inside flex children so text truncates instead of pushing.
- **Sticky affordances:** keep the budget meter / primary CTA reachable (`sticky bottom-0` bar) on long lists, with a safe-area-aware padding.
- **Truncation & wrapping:** `truncate` on names, `line-clamp-2` on descriptions; prices never wrap.

## DRY & hygiene

- No repeated 8-class strings across files — extract a component or a shared constant.
- No inline hex or arbitrary values when a token exists; add a token if a value recurs.
- Order classes roughly: layout → box → typography → colour → state → responsive, so diffs stay readable (or use a Prettier Tailwind plugin).
- Keep dark mode out of scope unless asked — BuyBuddy is a light, warm brand by design.
