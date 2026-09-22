# DakaBrand storefront theme

This repository contains the deployable DakaBrand WordPress theme, a disposable
local WordPress/WooCommerce environment, and tooling to compare the theme with
the hosted copy. WordPress core, plugins, uploads, database data, and generated
pages remain outside version control in local Docker volumes.

## Architecture

Normal storefront requests are served as generated files and do not boot
WordPress:

```text
User -> Cloudflare (CDN/WAF/cache) -> Nginx -> /static-pages/*.html
```

The generated pages call the same-origin proxy only for live storefront data:

```text
Browser -> Nginx /api/wc/store/v1/* -> WordPress/WooCommerce Store API
Browser -> Nginx /api/staticbridge/v1/language -> WordPress theme API
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

The cart stays in localStorage until checkout. A public Store API product lookup
validates an add or quantity increase. Checkout uses a fresh Cart-Token to add
the saved lines, calculate shipping and totals, and place a guest order through
the theme's Western Union / MoneyGram / Ria remittance gateway. The backend
must proxy the Store API paths listed
in `theme/README.md`, preserve Cart-Token headers, and disable caching of cart
and checkout responses. The local Docker site uses the same-origin
`/wp-json/` Store API route for development.

The static generator must skip `/my-account/`, delete any older generated copy,
and purge its CDN key. The theme blocks account links and direct account-page
rendering; it cannot delete files owned by the external generator.

Language selection is client-side: the generated HTML remains locale-neutral,
and the browser stores the selected locale in `localStorage` then fetches its
catalogue from `GET /api/staticbridge/v1/language?locale=en_US|sq_AL`. The API
response is public and cacheable; it must be proxied to WordPress. Do not route
the language switcher to `wp-admin/admin-post.php` or vary static HTML by a
WordPress cookie.

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
compose.yaml                   isolated local WordPress/WooCommerce stack
scripts/local-wordpress.sh     local setup, lifecycle, WP-CLI, and smoke tests
```

## Local WordPress environment

Docker is the only prerequisite. The first setup downloads WordPress,
MariaDB, WP-CLI, and WooCommerce, then activates this repository's theme and
creates a small, idempotent demo catalog:

```bash
scripts/local-wordpress.sh setup
```

Open <http://localhost:8080> or <http://localhost:8080/wp-admin/>. The default
local-only login is `admin` / `admin`. Override it, the email, or port when
needed:

```bash
DAKABRAND_LOCAL_PORT=8081 \
DAKABRAND_ADMIN_USER=developer \
DAKABRAND_ADMIN_PASSWORD='choose-a-local-password' \
scripts/local-wordpress.sh setup
```

Theme files are bind-mounted read-only into WordPress, so saving a file under
`theme/` is immediately reflected in the browser without copying or uploading.
WordPress and database state persist in Docker volumes.
For a database dump with a non-default table prefix, set
`DAKABRAND_LOCAL_TABLE_PREFIX` in the ignored `.env` file before starting the
containers. The local site uses `wp_` by default.
Local WP-Cron is disabled so imported production scheduled jobs do not run
automatically while you inspect the database.

```bash
scripts/local-wordpress.sh start
scripts/local-wordpress.sh stop
scripts/local-wordpress.sh status
scripts/local-wordpress.sh logs
scripts/local-wordpress.sh test
scripts/local-wordpress.sh wp option get siteurl
```

To rebuild only the disposable local site from scratch, use the deliberately
guarded reset command:

```bash
DAKABRAND_CONFIRM_RESET=yes scripts/local-wordpress.sh reset
```

The local workflow never invokes `scripts/theme_ftp.py`. FTP remains a separate,
explicit publish step after local checks and visual review are complete.

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
- `Footer information` contains account, cart, and checkout links.
- `Footer services` contains contact and policy links.
- `Footer navigation (legacy)` remains as the fallback for existing installs.

The responsive shell contains the app announcement, sticky header, five-item
mobile app toolbar, service benefits, newsletter, link groups, and social links.
The front page is an intentional exception to the header and footer only: it
renders a full-viewport, swipeable MAN/WOMAN shopping gateway while retaining
the mobile toolbar for fast access to Home, Women, Shop, Man, and Cart.

The `/man/` page uses a dedicated theme-native campaign view with locally owned
hero/editorial assets, category shortcuts, and dynamic WooCommerce sections for
New In, Top Deals, and Limited Stock.

The `/woman/` page follows the same campaign pattern with Women-specific local
assets and collection links. Homepage campaign imagery links directly to the
Women and Men landing pages.

### Newsletter proxy contract

The newsletter UI is safe to ship before the proxy exists. By default it shows
an unavailable message instead of an inactive signup form. The proxy layer can
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
python3 -m unittest -v tests/test_theme_ftp.py tests/test_theme_structure.py tests/test_local_wordpress.py
node --test tests/test_cart.js tests/test_checkout.js tests/test_product.js tests/test_contact.js
python3 scripts/theme_ftp.py status
```

Visually check the shell at 375px, 768px, and 1440px, including keyboard focus,
Escape-to-close on the mobile navigation, nested menus, cart count updates,
footer accordions, and all newsletter states.
