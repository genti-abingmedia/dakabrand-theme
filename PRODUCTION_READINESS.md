# Production readiness review — 2026-09-18

**Verdict: Not ready for release.** The local theme passes its automated and
WordPress checks, but the hosted static storefront returns 404 for public pages
and its checkout rejects the required remittance payment method.

## Verified locally

- All theme PHP files pass `php -l`; all theme JavaScript files pass `node --check`.
- 30 Python tests and 28 JavaScript tests pass. `scripts/local-wordpress.sh test`
  passes its route and installation checks.
- After publishing disposable local test pages, the homepage, Man, Woman, shop,
  cart, checkout, contact, policy, and product routes return 200. `/my-account/`
  returns 404. The local checkout and cart have `noindex` metadata.
- A Store API checkout using `staticbridge_remittance` created local order 32
  with `on-hold` status. The order was canceled, restoring the tested product's
  stock from 5 to 6. No local test order remains active. WooCommerce reports
  `staticbridge_remittance` as its only locally available payment gateway.
- Local checkout assets and the referenced theme font and icon respond with 200.
  Albanian markup renders when the locale cookie is set.

## Hosted blockers

1. **Static pages unavailable:** `/`, `/man/`, `/woman/`, `/shop/`, `/cart/`,
   `/checkout/`, `/contact-us/`, the three policy routes, and a sampled product
   route returned 404 from the `x-gliterin-storefront: static` layer. The
   homepage briefly returned 200 earlier in the review, then remained 404 on
   repeated checks. Restore generated files and confirm the generator/CDN
   publishing and purge path before release.
2. **Remittance checkout unavailable:** the hosted Store API accepted a product
   and returned a selected free shipping rate, but `POST /checkout` with
   `payment_method: staticbridge_remittance` returned HTTP 400
   `rest_invalid_param` (`Invalid parameter(s): payment_method`). A hosted
   `GET /checkout` reported `cod` as its payment method. No hosted order was
   created. Reconcile the remote theme and WooCommerce gateway configuration,
   then repeat one marked test order and cancel it afterward.
3. **Remote theme drift:** FTPS status reports 13 modified files and two local
   translation files absent remotely. The changed set includes checkout,
   `functions.php`, both shared assets, and rendered views. Review the exact
   diff before a separately authorized deployment. Automatic publishing is
   disabled in `.codex/theme-deploy.json`.

## Remaining checks

- The catalog filter endpoint returned 200 with a product and CORS enabled.
  The hosted Store API returned `no-store` on cart and checkout responses when
  reachable, but it also timed out on two fresh-cart requests during this
  review. Confirm stable API latency after the static site is restored.
- Browser based visual and keyboard checks at 375, 768, and 1440 px were not
  completed because no browser was available in this session. Perform them on
  the restored hosted pages, including navigation, product variations, cart,
  checkout, focus, dialogs, and the optional form unavailable notices.
- Confirm that the static generator supports locale-specific output and the
  language switcher's WordPress `admin-post.php` request. The theme uses a
  cookie and nonce-bearing form; static HTML cannot itself vary by cookie.
- Publish and review real contact and policy page content on the hosted site.
  The local pages used for this audit contain fixture text only.

## Theme changes made during review

The theme now presents remittance as its only checkout method and sends only
`staticbridge_remittance`. It hides contact and newsletter forms when their
optional endpoints are absent, displays unavailable notices, and removes
unverified shipping, return, support, and newsletter promises. The README,
Albanian catalogue, and focused regression checks were updated accordingly.
