# Mascot assets

Drop the BuyBuddy mascot here.

## Format
- **Vector / illustration → `.svg`** (best — the agent can inline it and animate parts: waving, blinking, bouncing).
- **Rendered 3D / painted → `.png` with a transparent background**, high-res (include @2x, e.g. ~1024px).

## Poses (name by state — tied to app moments)
Provide as many as you can. Same body proportions and baseline across all poses so they swap without jumping.

| File (svg or png) | Pose / expression | Shows up when |
|---|---|---|
| `mascot-wave` | waving / greeting | landing, empty states |
| `mascot-celebrate` | happy / cheering | plan goes back within budget, plan complete |
| `mascot-thinking` | thoughtful / advising | recommendations, "why recommended" |
| `mascot-worried` | caring / gentle concern (NOT scolding) | over-budget states |
| `mascot-holding` | holding a bag / showing an item | product detail, bundle |

Optional extras: `mascot-idle`, `mascot-loading`, `mascot-checkout`.

## Rules
- Transparent background, generous canvas padding around the character.
- Consistent size, baseline, and facing direction across poses.
- If you have a name / personality / voice for the mascot, note it in `../BRAND.md` §5.
- A hero/loose pose (e.g. `mascot-hero.png`, larger) is welcome for the landing page.
