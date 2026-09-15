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

The browser cart and proxy integration will be implemented separately.

## Shared shell extensions

Bootstrap 5.3.8 is bundled locally and loaded before the theme stylesheet and
script. Configure the `primary`, `footer_information`, and `footer_services`
menu locations in WordPress; the legacy `footer` location remains a fallback.

Newsletter integrations should provide a URL through the
`staticbridge_newsletter_endpoint` filter. The endpoint accepts a JSON object
with an `email` property and may return a user-facing `message` in a successful
JSON response.
