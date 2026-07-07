# Logo assets

Drop the BuyBuddy logo here. **SVG preferred** (the agent can inline it as a React component, recolour it, and it stays crisp at any size).

| File | What it is | Used for |
|---|---|---|
| `logo.svg` | Full-colour lockup (mark + "BuyBuddy") | Header, marketing |
| `logo-mono.svg` | Single-colour version | Dark or photo backgrounds |
| `mark.svg` | Mark only (no text) | Small header, favicon, app icon |
| `logo.png` / `mark.png` | Raster fallback, transparent bg, @2x | Where SVG can't be used |

Notes:
- Transparent background.
- If you have clear-space / min-size rules, add them to `../BRAND.md` §4.
- The mark will replace the placeholder `🛍️` emoji in `resources/js/Layouts/AppLayout.jsx`, and seed `public/favicon` + an OG image.
