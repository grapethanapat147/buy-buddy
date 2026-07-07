---
name: motion-design
description: Animation, motion, transitions, and micro-interactions for BuyBuddy's React/Tailwind UI. Use when adding hover/press/focus effects, page or list transitions, loading and success states, budget-meter animation, celebratory delight, or any "make it feel alive / add flair" request. Pair with design-taste for the visual layer.
---

# BuyBuddy — Motion Design

Motion is how BuyBuddy feels like a **living buddy**, not a static form. But motion has a job: it should **explain, confirm, guide attention, or delight** — never decorate for its own sake. Every animation answers "what just happened / what can I do / where did that go".

## Principles

1. **Fast and light.** UI transitions live in **150–300ms**. Hovers ~150ms, entrances ~250ms, celebratory moments up to ~500ms. Anything slower feels sluggish on a phone.
2. **Natural easing.** Use ease-out for things entering/responding (`cubic-bezier(0.22, 1, 0.36, 1)`), ease-in for things leaving. Avoid linear (robotic) except for continuous spinners/progress. A touch of spring/overshoot on playful confirmations, sparingly.
3. **Animate only `transform` and `opacity`.** These are GPU-composited and won't jank. Avoid animating `width`/`height`/`top`/`left`/`box-shadow` in hot paths; use `transform: scale/translate` and opacity instead. Add `will-change` only on elements that actually animate, and remove it after.
4. **Motion has meaning through direction.** Things enter from where they come from, leave toward where they go. A card added to the plan should fly/scale toward the cart; a deferred item slides down/out.
5. **Respect the user.** Always honour `prefers-reduced-motion`: reduce to a simple fade or none. Never trap attention with looping motion.
6. **State, not just decoration.** The most valuable motion is on **state change** — a number counting up, a meter filling, a button confirming, a toast arriving.

## Where motion earns its place in BuyBuddy

- **Budget meter** — animate the fill width and the total **counting up/down** when items change; a colour cross-fade (emerald → amber → rose) as it crosses the budget line. This is the signature interaction — make it smooth and satisfying.
- **Add / remove from plan** — the `+` button springs to a check; the item's price animates into the running total; the cart badge bumps (`scale` pop).
- **Over-budget suggestion** — the suggested-to-defer item gently pulses its amber highlight once to draw the eye, then settles.
- **Back-in-budget** — a small celebratory beat (check, subtle confetti or a soft glow) when a plan crosses from over → within budget.
- **List reordering / filtering** — Explore/Recommendations items animate position changes (FLIP) rather than snapping.
- **Page transitions** — gentle cross-fade/slide between Inertia pages; skeletons or a shimmer for perceived speed on loads.
- **Empty states** — a friendly idle wiggle on the "buddy" mark invites the first action.

## How to build it (this stack: React 19 + Tailwind 3)

**CSS-first (no dependency) — reach for this by default:**
- Tailwind `transition`, `duration-200`, `ease-out`, and state variants: `hover:-translate-y-0.5 active:scale-[0.98] focus-visible:ring-2`.
- Keyframes in `tailwind.config.js` `theme.extend.keyframes` + `animation` (e.g. `pop`, `shimmer`, `pulse-once`, `count`).
- `@media (prefers-reduced-motion: reduce)` to disable non-essential animation.

```js
// tailwind.config.js
theme: { extend: {
  keyframes: {
    pop: { '0%': { transform: 'scale(1)' }, '50%': { transform: 'scale(1.15)' }, '100%': { transform: 'scale(1)' } },
    shimmer: { '100%': { transform: 'translateX(100%)' } },
  },
  animation: { pop: 'pop 200ms ease-out', shimmer: 'shimmer 1.2s infinite' },
}}
```

**Framer Motion (`motion`) — add when you need orchestration** (list enter/exit, layout/FLIP, shared-element, gesture springs). It is NOT installed yet — propose `npm i motion` and get the OK before adding the dependency. Then:
- `AnimatePresence` for mount/unmount (items leaving the plan).
- `layout` prop for automatic FLIP on reflow (filtering Explore, reordering).
- `useReducedMotion()` to branch, and a spring `transition` for playful confirmations.
- Animated counters: interpolate the total with `animate`/`useMotionValue` and render with tabular figures.

## Guardrails

- One or two motion "moments" per screen, not twenty. Restraint keeps delight delightful.
- Don't block interaction on animation — the UI stays usable mid-transition; keep durations short.
- Test on a mid-range phone feel: if it's not buttery, simplify.
- Every non-essential animation must degrade gracefully under reduced-motion and never cause layout shift (CLS).
