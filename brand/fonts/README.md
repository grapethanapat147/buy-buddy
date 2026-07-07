# Font assets

Only needed if you want to **self-host** the brand typeface (faster, no external request, works offline). Otherwise just put the Google Fonts name + weights in `../BRAND.md` §2 and skip this folder.

## Drop here
- `*.woff2` — one file per weight/style you use (e.g. `Brand-Regular.woff2`, `Brand-SemiBold.woff2`). WOFF2 is the modern, smallest format.
- `*.woff` — optional fallback for very old browsers.
- A license file if the font requires it.

## Weights
List which weights map to what (matches BRAND.md):
- Regular (400) → body text
- SemiBold (600) → headings, buttons, emphasis

The agent will wire these via `@font-face` in `resources/css/app.css`, set `font-sans` in `tailwind.config.js`, and remove the temporary Google Fonts `<link>` from `resources/views/app.blade.php`.
