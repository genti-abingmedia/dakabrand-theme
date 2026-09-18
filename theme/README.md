# DakaBrand

Minimal classic WooCommerce theme prepared for static HTML generation.

## Rendering contract

Normal WordPress templates and the generator plugin use the same public function:

```php
staticbridge_render_document('product');
```

The generator must prepare the appropriate global WordPress query and queried
object before calling the function. The current contract version is available as
`STATICBRIDGE_RENDER_API_VERSION` and through `staticbridge_render_contract()`.

Supported views:

- `front-page`
- `man`
- `woman`
- `cart`
- `checkout`
- `contact`
- `policy`
- `index`
- `page`
- `product`
- `product-archive`
- `404`

## Frontend development

Page markup belongs in `template-parts/views/`. Reusable markup belongs in
`template-parts/components/`. CSS and JavaScript belong in `assets/`.

Frontend developers may change markup and design freely, but should preserve:

- The `staticbridge_render_document()` function signature.
- Supported view names.
- Product and variation IDs in generated product data.
- `data-product-id`, `data-product-type`, and `data-add-to-cart` attributes.
- The `<script type="application/json" data-staticbridge-product>` payload.
- Calls to `wp_head()`, `wp_body_open()`, and `wp_footer()`.

## Browser cart

The cart drawer is shared by every view, and `/cart/` is a theme-native
localStorage-rendered page. `assets/js/cart.js` stores an array of
cart lines under `staticbridge_cart_v1` in localStorage; each line has a product
ID, optional variation ID and selected attributes, display data, unit price,
currency, stock snapshot, and quantity. The header, mobile tab, and cart links
open the drawer. `/checkout/` syncs the browser cart to a fresh WooCommerce
Cart-Token, shows server shipping rates and totals, and places a guest cash-on-
delivery order. The local cart is cleared only after a confirmed order.
Checkout supports a separate shipping address, seller note, shipping estimate,
and coupon codes through the same cart token.

`validateStock(item, requestedQuantity)` fetches the live Store API product or
variation before adding or increasing a local line. The local price remains
provisional; WooCommerce validates the complete cart at checkout. All shop,
category, Man, Woman, and related-product grids load through the filter API;
landing-page simple products retain quick add. Recently viewed is removed.

The same-origin proxy must expose `GET /api/wc/store/v1/products/{id}`,
`GET /api/wc/store/v1/cart`, and `POST` for `cart/add-item`,
`cart/update-customer`, `cart/select-shipping-rate`, `cart/apply-coupon`,
`cart/remove-coupon`, and `checkout`. Preserve
`Cart-Token` request and response headers. Never cache cart or checkout
responses. Enable the WooCommerce `cod` gateway. The theme defaults to `/api/`
in production and `/wp-json/` in the local Docker environment. Override the
base with `staticbridge_api_base` if needed. The filter source origin defaults
to `https://static-daka.gliterindemo.com` and can be changed with
`staticbridge_catalog_source_origin`.

The generator must omit `/my-account/`, delete any previously generated static
file for it, and purge its CDN key. Account links are suppressed in theme menus.

Run `node --test tests/test_cart.js tests/test_checkout.js tests/test_product.js`
from the repository root. Browser storage must be available for cart changes.

## Shared shell extensions

Bootstrap 5.3.8 is bundled locally and loaded before the theme stylesheet and
script. Configure the `primary`, `footer_information`, and `footer_services`
menu locations in WordPress; the legacy `footer` location remains a fallback.

Newsletter integrations should provide a URL through the
`staticbridge_newsletter_endpoint` filter. The endpoint accepts a JSON object
with an `email` property and may return a user-facing `message` in a successful
JSON response.

The `front-page` view is an immersive MAN/WOMAN gateway. It intentionally omits
the header and footer, retains the mobile app toolbar, and keeps `wp_head()`,
`wp_body_open()`, and `wp_footer()` so plugins and generated documents continue
to work correctly.

The `/man/` route uses `page-man.php` and the `man` render view. A guarded
theme-level route also serves it on static/proxy installs that do not have a
matching WordPress page row. Its campaign imagery is owned by the theme, while
New In, Top Deals, and Limited Stock load from the filter API in `catalog.js`.

The `/woman/` route follows the same theme-native campaign contract with
Women-specific imagery, category links, and filter API grids. The two
homepage campaign images lead to `/woman/` and `/man/` respectively.

## Contact and policy pages

The `/contact-us/` page uses the theme's contact form and public store details.
The `/privacy-policy/`, `/terms-conditions/`, and `/refund_returns/` pages share
the policy view and display their WordPress page title and content. Keep the
policy wording in the page editor rather than in the theme.

The contact form does not submit until a delivery endpoint is configured. A
future integration can provide an absolute or same-origin URL with:

```php
add_filter('staticbridge_contact_endpoint', static fn (): string => home_url('/api/contact/'));
```

The browser sends a JSON `POST` with `name`, `email`, and `message`. A 2xx response
means success and may contain a JSON `message`; errors appear beside the form.
When the filter is empty, the form sends no request and reports that online
messages are unavailable.
