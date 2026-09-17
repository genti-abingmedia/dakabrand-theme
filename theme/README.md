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
open the drawer. It does not synchronize with WooCommerce. The cart page's
checkout link intentionally does not transfer its browser cart to WooCommerce.

`validateStock(item, requestedQuantity)` in `cart.js` is the asynchronous
boundary for a future live-stock API. Today it only checks the rendered
purchasability and stock status; prices and availability shown in the drawer
are provisional. The API-rendered catalog grid still links to product pages
rather than offering quick add.

Run `node --test tests/test_cart.js` from the repository root for cart logic
checks. Browser storage must be available for cart changes to persist.

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
New In, Top Deals, and Limited Stock remain dynamic WooCommerce product queries.

The `/woman/` route follows the same theme-native campaign contract with
Women-specific imagery, category links, and WooCommerce queries. The two
homepage campaign images lead to `/woman/` and `/man/` respectively.
