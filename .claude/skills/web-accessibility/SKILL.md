---
name: web-accessibility
description: Accessibility, responsive robustness, and inclusive design for BuyBuddy. Use when building interactive UI (buttons, forms, tabs, modals, toggles), adding motion, choosing colours, or reviewing a page — to keep it keyboard-usable, screen-reader-friendly, high-contrast, and comfortable on every screen. Especially important because BuyBuddy leans on lots of visual flair.
---

# BuyBuddy — Accessibility & Robustness

Flair and accessibility are not in tension — the best delightful UIs are also the most usable. Because BuyBuddy will carry a lot of motion and visual effect, accessibility is the guardrail that keeps it usable for everyone and on every device. Bake it in as you build; don't bolt it on later.

## Non-negotiables

1. **Everything works with a keyboard.** Every interactive thing is a real `<button>`/`<a>`/`<input>` (or has `role` + `tabindex="0"` + key handlers). Tab order follows visual order. Never `onClick` on a bare `<div>`.
2. **Visible focus, always.** Use `focus-visible:ring-2 focus-visible:ring-brand/40` — never `outline-none` without a replacement. The "+"/add buttons, tabs, and cart link all need clear focus.
3. **Name every control.** Icon-only buttons (the `+`, `✓`, remove, cart) get `aria-label`. Decorative icons/emoji get `aria-hidden="true"`. Inputs have real `<label>`s (not just placeholders).
4. **Contrast passes.** Body text ≥ 4.5:1, large text/UI ≥ 3:1 against its background. Warm grays on cream still need to pass — check `ink-soft`/`ink-muted`. Never signal state by **colour alone**: the over-budget state needs an icon/label + text, not just red (colour-blind users). The budget meter needs a text readout, not just a coloured bar.
5. **Respect `prefers-reduced-motion`.** All non-essential animation (counters, confetti, pulses, springs) reduces to a fade or nothing. Use `useReducedMotion()` / the CSS media query. Nothing loops forever demanding attention.
6. **Touch targets ≥ 44×44px.** The `h-9 w-9` icon buttons are borderline — give them enough hit area (padding) on mobile.

## Patterns for BuyBuddy's components

- **Tabs (My Plan list/calendar):** `role="tablist"` / `role="tab"` with `aria-selected`, arrow-key navigation, and `role="tabpanel"`. Or keep it simple but still keyboard-focusable + labelled.
- **Add/remove buttons:** `aria-label="ใส่ลงแผน"` / `aria-label="เลื่อนออก"`; announce the result. When the total changes, expose it to screen readers via an `aria-live="polite"` region on the budget total so blind users hear "฿4,780, อยู่ในงบ".
- **Budget meter:** give the fill `role="progressbar"` with `aria-valuenow/aria-valuemax/aria-valuetext="฿5,780 จากงบ ฿5,000 เกินงบ"`.
- **Forms (wizard/auth):** associate errors with fields (`aria-describedby`, `aria-invalid`); focus the first error on submit; the wizard's option cards are radio-like (`role="radio"`/`radiogroup` or real radios).
- **Toasts/celebrations:** `aria-live="polite"`; never rely on a fleeting animation to convey a required message.
- **Modals/sheets (if added):** trap focus, restore focus on close, `Esc` closes, `aria-modal` + labelled.

## Responsive robustness (breaks that look like bugs)

- **No horizontal scroll.** Long product names use `truncate` + `min-w-0` in flex children; the page body never scrolls sideways.
- **Text scales.** Don't hard-cap with tiny fixed heights that clip when the OS font size is large; prefer padding + line-height.
- **Content-first breakpoints.** Design at ~375px, then let it breathe up; don't design desktop-first and cram it down.
- **Safe areas.** Sticky bottom bars respect `env(safe-area-inset-bottom)` on phones.
- **Loading & empty & error states exist** for every data view — a spinner/skeleton, a friendly empty message, and a recoverable error, not a blank screen.

## Quick review checklist (run before calling a screen done)

- [ ] Tab through it start-to-finish — reachable, visible focus, logical order?
- [ ] Every icon button has an `aria-label`; every input has a label.
- [ ] State is never colour-only (icon/text too); contrast checked on cream.
- [ ] Budget total / important changes are in an `aria-live` region.
- [ ] Reduced-motion path exists and is calm.
- [ ] 375px: no sideways scroll, nothing clipped, targets ≥ 44px.
- [ ] Keyboard can do everything the mouse can (add, remove, switch tabs, submit).
