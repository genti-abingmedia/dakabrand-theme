# DakaBrand storefront theme

This repository contains only the deployable DakaBrand WordPress theme and the
tooling needed to compare it with the hosted copy. WordPress core, plugins,
uploads, database data, generated pages, and infrastructure configuration do not
belong in this repository.

## Architecture

Normal storefront requests are served as generated files and do not boot
WordPress:

```text
User -> Cloudflare (CDN/WAF/cache) -> Nginx -> /static-pages/*.html
```

Routes that require a live session remain dynamic:

```text
User -> Cloudflare -> Nginx -> PHP-FPM -> WordPress/WooCommerce
                                      -> /cart
                                      -> /checkout
                                      -> /my-account
```

Publishing product content follows this path:

```text
WooCommerce admin
  -> product/category update hook
  -> generator plugin
  -> regenerate affected product and category HTML
  -> regenerate the homepage when its content changed
  -> write /static-pages/
  -> purge the affected Cloudflare cache keys
```

Redis and other infrastructure caches may support the proxy and WordPress, but
they are outside this theme repository.

## Theme/proxy boundary

The target contract is strict: theme PHP and browser JavaScript must not call
WooCommerce functions, instantiate WooCommerce classes, or depend on WooCommerce
template globals. A separate proxy/generator layer may use WooCommerce APIs. It
must normalize the data and pass a stable, presentation-ready context to the
theme. Cart mutations and other interactive commerce operations go through the
proxy rather than WooCommerce browser endpoints.

The initial remote theme mirror is intentionally preserved unchanged. It
currently uses `WC_Product`, `wc_get_product()`, WooCommerce product-loop helpers,
and WooCommerce template globals. Removing those dependencies is technical debt
for the next implementation phase, not part of this bootstrap.

## Repository layout

```text
theme/                         deployable DakaBrand theme only
scripts/theme_ftp.py           guarded FTPS inventory/sync/deploy helper
tests/test_theme_ftp.py        local safety and classification tests
.codex/theme-deploy.json       fixed local and remote theme boundaries
backups/                       ignored local pull/deploy backups
.env                           ignored FTPS credentials
```

## FTPS workflow

The helper reads `FTP_SERVER`, `FTP_PORT`, `FTP_ACCOUNT_NAME`, `FTP_PASSWORD`,
and the optional certificate hostname `FTP_TLS_SERVER_NAME` from `.env`. It uses
explicit, certificate-verified TLS for both the control and data connections and
never prints credentials. `FTP_TLS_SERVER_NAME` is required when `FTP_SERVER` is
an IP address that is covered by a certificate issued to its hosting hostname.

```bash
python3 scripts/theme_ftp.py inventory
python3 scripts/theme_ftp.py status
python3 scripts/theme_ftp.py pull
python3 scripts/theme_ftp.py deploy
```

- `inventory` reads theme and plugin names/versions from the server without
  saving plugin or WordPress files locally.
- `status` compares file content and reports `A` for local-only files that would
  be added remotely, `M` for differing files, and `R` for remote-only files.
- `pull` copies only the configured remote theme into `theme/`. Existing local
  files are backed up before replacement, writes are atomic, and local-only
  files are not deleted.
- `deploy` shows the exact `A`/`M` scope and requires typing
  `DEPLOY dakabrand`. Remote originals are downloaded to `backups/deploy/`
  before temporary uploads are renamed into place. Remote-only files are never
  deleted.

Run `status` immediately before and after every deployment. Automatic publishing
is disabled in `.codex/theme-deploy.json`.

## Storefront shell

The theme includes Bootstrap 5.3.8 as local, versioned assets under
`theme/assets/vendor/bootstrap/`; storefront pages never load the framework
from a CDN. DakaBrand-specific layout and behavior remain in `assets/css/main.css`
and `assets/js/main.js`. The bundled logo is the fallback when no WordPress
custom logo is configured.

Assign these menu locations in WordPress:

- `Primary navigation` powers both the desktop dropdown navigation and the
  accessible mobile offcanvas menu.
- `Footer information` contains account, cart, wishlist, and checkout links.
- `Footer services` contains contact and policy links.
- `Footer navigation (legacy)` remains as the fallback for existing installs.

The responsive shell contains the app announcement, sticky header, mobile
shortcut bar, service benefits, newsletter, link groups, and social links. It
is rendered by every StaticBridge view, including the front page.

### Newsletter proxy contract

The newsletter UI is safe to ship before the proxy exists. By default it does
not send a request and reports that signup is unavailable. The proxy layer can
enable it without editing the theme:

```php
add_filter('staticbridge_newsletter_endpoint', static fn (): string => home_url('/api/newsletter/'));
```

The browser sends `POST` JSON containing `{"email":"customer@example.com"}`.
Any 2xx response is successful and may return a JSON `message`; non-2xx and
network responses are shown as accessible inline errors.

### Frontend verification

```bash
find theme -name '*.php' -print0 | xargs -0 -n1 php -l
python3 -m unittest -v tests/test_theme_ftp.py tests/test_theme_structure.py
python3 scripts/theme_ftp.py status
```

Visually check the shell at 375px, 768px, and 1440px, including keyboard focus,
Escape-to-close on the mobile navigation, nested menus, cart count updates,
footer accordions, and all newsletter states.
