# Halloween 2026 storefront

Work lives on `seasonal/halloween-2026`. `main` remains the normal storefront;
`normal-before-halloween-2026` marks the starting version. This change is local;
no production theme, products, orders, or plugin settings were changed.

## Preview and restore

Start the saved local site with:

```bash
DAKABRAND_LOCAL_PORT=8082 scripts/local-wordpress.sh start
```

Preview http://localhost:8082/. Theme files are mounted directly into WordPress.
Save the seasonal work in a commit before switching branches; Git must have a
clean working tree so seasonal files do not follow the switch. Then:

```bash
git switch main
```

To return to the campaign:

```bash
git switch seasonal/halloween-2026
```

If publishing later, deploy the Halloween branch using the project's separate
FTPS deployment workflow. To restore production, deploy the normal theme again,
regenerate the external static pages and refresh their CDN cache. Branch
switching alone does not change production or already-generated HTML.

## Seasonal layer

The custom document renderer adds `halloween-theme` to the storefront body and
loads `assets/css/halloween.css` after the base/catalog styles. The stylesheet
and `assets/js/halloween.js` use content hashes for cache versioning. The dark
palette uses dedicated seasonal tokens; original black/red variables are not
inverted. Campaign images and payment marks retain their colors. Product photos
use 88% CSS brightness to soften white backgrounds; original image files remain
unchanged. WooCommerce placeholders retain their separate 24% brightness rule.
WhatsApp card icons are dimmed, with dark green product-page buttons.

The center mobile Shop link contains a decorative inline pumpkin SVG, with the
same label and URL. The other four navigation links and cart counter remain.

Smoke uses a local alpha texture, two layers per corner, and only transform and
opacity animation. Each corner is capped at 14% opacity and 420 × 180px (desktop)
or 200 × 110px (mobile, above the toolbar). It is inert and ignores pointer input.
There is no screen-edge smoke markup on cart or checkout. The hidden thank-you
confirmation has its own small amber smoke effect below a pumpkin icon, revealed
only after successful checkout; the active checkout form remains smoke-free. CSS hides and pauses it while a
menu drawer, filter drawer, search popup, lightbox, or dialog is open; reduced
motion removes it. The small script pauses it when the document becomes hidden.

## Smoke asset provenance

Asset: `theme/assets/images/halloween-smoke.webp` (840 × 280, alpha preserved).
Generated with the built-in image generation tool, then resized and encoded as
WebP for local delivery. The source was retained in the tool's generated images
folder. No image generation API key or runtime image service is required.

Final prompt:

> Use case: stylized-concept. Asset type: transparent smoke texture for a subtle website corner animation. Primary request: one isolated low horizontal wisp of realistic soft silver-white theatrical smoke, delicate swirling tendrils, wide composition about 3:1, fading out completely and smoothly at all four edges. Transparent background, actual alpha channel. Monochrome neutral white smoke only, no objects, no text, no ground, no background, no black rectangle. The thin smoke should occupy lower central half with generous fully transparent margins, airy translucent detail, suitable as a CSS alpha mask for lavender or amber tinting.

## Verification notes

- Existing suite: 40 Python tests and 46 JavaScript tests passed; PHP syntax
  checked across the theme.
- Reviewed nine routes at 375px, 768px and 1440px. No horizontal page overflow;
  checked text/control colors, mobile toolbar, drawers and checkout fields.
- Exercised local variable product EU 40/EU 41/EU 42 states, add-to-cart,
  quantity updates, filter selection, pumpkin Shop navigation and English/SQ
  switching. Checkout shipping validation was checked without placing an order.
- Confirmed smoke pauses and hides in open overlays and is absent on cart and
  checkout. Visibility lifecycle was additionally checked in a small Node VM
  harness; the browser automation environment does not reliably simulate hidden
  tabs. Reduced-motion removal is enforced in CSS; OS preference emulation was
  not available in the connected browser.
- Existing localhost limitations: OneSignal is restricted to dakabrand.uk and
  logs a domain error locally. Catalog lists use the existing live catalog
  fallback; those remote product links may not exist in the small local demo
  database. Commerce validation used the existing local demo product instead.
  No changes were made to those integrations.
- Payment brand artwork is preserved; its existing AMEX mark colors were not
  changed by the dark layer.

## Pennywise visitor

`halloween-pennywise.svg` is an original small vector illustration of Pennywise,
used in a dismissible button. The image generator rejected the requested raster
cutout; the final implementation uses the local SVG and has no image-service
runtime dependency.

The first appearance is scheduled after 2–6 seconds, then subsequent visits
are spaced 30–45 seconds apart. Each visit lasts five seconds. Click/tap or
Escape dismisses it, with a 350ms retreat. It never takes focus automatically.
Desktop randomly uses all four edges; mobile uses left/right/top to keep the
bottom navigation clear. A clipped stage prevents off-screen poses from adding
horizontal scrolling. No audio or flashing is used.

There is no visitor markup on cart or checkout (including the confirmation).
Open overlays dismiss it; focused form fields postpone it. Hidden tabs, reduced
motion, page navigation and failed image loads stop pending visits. Returning
to a visible tab starts a fresh interval. Its dismiss label supports EN/SQ.

Manual review: wait up to 6 seconds on an eligible page, check appearance and
click dismissal, then wait 30–45 seconds for another visit. Check five-second
auto-dismissal, Escape, overlays, reduced motion and the mobile toolbar. Browser
testing was deliberately not performed for this feature at the user's request.
The eight new command-line lifecycle tests cover these timing and dismissal
rules using fake timers.
