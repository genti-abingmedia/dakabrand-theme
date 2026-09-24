<?php

if (!defined('ABSPATH')) {
    exit;
}

define('STATICBRIDGE_THEME_VERSION', '0.2.0');
define('STATICBRIDGE_RENDER_API_VERSION', '1.0');
define('STATICBRIDGE_BOOTSTRAP_VERSION', '5.3.8');

/**
 * Keep first-party theme assets secure when an upstream HTML rewriter misses
 * a srcset attribute. Local and staging hosts retain their original scheme.
 */
function staticbridge_force_https_theme_directory_uri(string $uri): string
{
    return 'dakabrand.uk' === wp_parse_url($uri, PHP_URL_HOST)
        ? set_url_scheme($uri, 'https')
        : $uri;
}

add_filter('template_directory_uri', 'staticbridge_force_https_theme_directory_uri');

/** Build the same image resize URL used by the catalog cards. */
function staticbridge_resize_image_url(string $url, int $width, int $height = 0): string
{
    $url = trim($url);
    $scheme = wp_parse_url($url, PHP_URL_SCHEME);
    if (!in_array($scheme, array('http', 'https'), true) || $width < 1) {
        return $url;
    }
    if ('img.gliterin.net' === wp_parse_url($url, PHP_URL_HOST)) {
        return $url;
    }

    $query = array('image' => $url, 'width' => $width, 'height' => $height > 0 ? $height : $width);
    return 'https://img.gliterin.net/resize.php?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
}

/** Keep the aspect ratio of full-size product photos when requesting a resize. */
function staticbridge_resized_attachment_url(int $attachment_id, int $width): string
{
    $source = wp_get_attachment_image_src($attachment_id, 'full');
    if (!$source) {
        return '';
    }
    $height = !empty($source[1]) && !empty($source[2])
        ? max(1, (int) round($width * $source[2] / $source[1]))
        : $width;
    return staticbridge_resize_image_url((string) wp_get_attachment_url($attachment_id), $width, $height);
}

/** Resize attachment images while retaining their original URL as a fallback. */
function staticbridge_resize_attachment_image_attributes(array $attributes, WP_Post $attachment): array
{
    $original = wp_get_attachment_url($attachment->ID);
    if (!$original || empty($attributes['src'])) {
        return $attributes;
    }
    $width = max(1, (int) ($attributes['width'] ?? 480));
    $height = max(1, (int) ($attributes['height'] ?? $width));
    $attributes['src'] = staticbridge_resize_image_url($original, $width, $height);
    $attributes['srcset'] = staticbridge_resize_image_url($original, $width, $height) . ' ' . $width . 'w, '
        . staticbridge_resize_image_url($original, $width * 2, $height * 2) . ' ' . ($width * 2) . 'w';
    $attributes['sizes'] = '(max-width: ' . $width . 'px) 100vw, ' . $width . 'px';
    $attributes['data-original-image'] = $original;
    return $attributes;
}
add_filter('wp_get_attachment_image_attributes', 'staticbridge_resize_attachment_image_attributes', 10, 2);

/**
 * The storefront UI is available in English and Albanian without requiring a
 * multilingual-content plugin. Content entered in WordPress remains shared.
 */
function staticbridge_supported_locales(): array
{
    return array('en_US', 'sq_AL');
}

function staticbridge_requested_locale(): string
{
    // Generated pages are intentionally locale-neutral. The browser chooses a
    // locale and loads its catalogue through the public API instead of relying
    // on a WordPress cookie (which static files cannot vary on).
    return 'en_US';
}

function staticbridge_language_messages(string $locale): array
{
    if ('sq_AL' !== $locale) {
        return array();
    }

    $catalogue = require get_template_directory() . '/languages/dakabrand-sq_AL.l10n.php';

    return is_array($catalogue['messages'] ?? null) ? $catalogue['messages'] : array();
}

function staticbridge_language_switcher(string $class_name = ''): string
{
    $classes = trim('language-switcher ' . $class_name);

    ob_start();
    ?>
    <div class="<?php echo esc_attr($classes); ?>" aria-label="<?php esc_attr_e('Choose language', 'dakabrand'); ?>" data-language-switcher>
        <button type="button" data-language-locale="en_US">EN</button>
        <span aria-hidden="true">/</span>
        <button type="button" data-language-locale="sq_AL">SQ</button>
    </div>
    <?php

    return (string) ob_get_clean();
}

function staticbridge_frontend_messages(): array
{
    return array(
        'cartItem'              => __('%s items in cart', 'dakabrand'),
        'toggleSubmenu'         => __('Toggle %s submenu', 'dakabrand'),
        'remove'                => __('Remove', 'dakabrand'),
        'removeFromCart'        => __('Remove %s from cart', 'dakabrand'),
        'quantity'              => __('Quantity: %s', 'dakabrand'),
        'decreaseQuantity'      => __('Decrease quantity of %s', 'dakabrand'),
        'increaseQuantity'      => __('Increase quantity of %s', 'dakabrand'),
        'emptyCart'             => __('Your cart is empty.', 'dakabrand'),
        'exploreCollection'     => __('Explore the collection', 'dakabrand'),
        'multipleCurrencies'    => __('Multiple currencies', 'dakabrand'),
        'choose'                => __('Choose %s', 'dakabrand'),
        'chooseOptions'         => __('Choose options', 'dakabrand'),
        'available'             => __('Available', 'dakabrand'),
        'addToCart'             => __('Add to cart', 'dakabrand'),
        'unavailable'           => __('Unavailable', 'dakabrand'),
        'outOfStock'            => __('Out of stock', 'dakabrand'),
        'linkCopied'            => __('Link copied', 'dakabrand'),
        'copyLinkFallback'      => __('Select and copy the link above', 'dakabrand'),
        'questionAboutProduct'  => __('Question about %1$s (%2$s): %3$s', 'dakabrand'),
        'productInterest'       => __('I AM INTERESTED IN THE PRODUCT: %1$s with SKU: %2$s Link: %3$s', 'dakabrand'),
        'showFilters'           => __('Show filters', 'dakabrand'),
        'hideFilters'           => __('Hide filters', 'dakabrand'),
        'tryAgain'              => __('Try again', 'dakabrand'),
        'noProducts'            => __('No products found.', 'dakabrand'),
        'catalogLoadError'      => __('We could not load products right now.', 'dakabrand'),
        'resultsFor'            => __('Results for “%s”', 'dakabrand'),
        'productsCount'         => __('%s products', 'dakabrand'),
        'viewProduct'           => __('View product', 'dakabrand'),
        'contactWhatsApp'       => __('Contact on WhatsApp', 'dakabrand'),
        'thisProduct'           => __('this product', 'dakabrand'),
        'productUnavailable'    => __('This item is currently unavailable.', 'dakabrand'),
        'quantityUnavailable'   => __('The requested quantity is not available.', 'dakabrand'),
        'quantityUnavailableWithStock' => __('Only %s left in stock.', 'dakabrand'),
        'validQuantity'         => __('Choose a valid quantity.', 'dakabrand'),
        'productDetailsMissing' => __('Product details are unavailable.', 'dakabrand'),
        'stockCheckFailed'      => __('Could not check live stock. Please try again.', 'dakabrand'),
        'preorderOnly'          => __('Preorder Only', 'dakabrand'),
        'availableProducts'     => __('Available Products', 'dakabrand'),
        'filters'               => __('Filters', 'dakabrand'),
        'filter'                => __('Filter', 'dakabrand'),
        'clearFilters'          => __('Clear filters', 'dakabrand'),
        'previousPage'          => __('Previous', 'dakabrand'),
        'nextPage'              => __('Next', 'dakabrand'),
        'loadingProducts'       => __('Loading products…', 'dakabrand'),
        'noProductsAfterFilter' => __('No products found. Try removing a filter.', 'dakabrand'),
        'inStock'               => __('In stock', 'dakabrand'),
        'preorderDays'          => __('15 days preorder', 'dakabrand'),
        'product'               => __('Product', 'dakabrand'),
        'shop'                  => __('Shop', 'dakabrand'),
        'whatsAppInterest'      => __('I AM INTERESTED IN THE PRODUCT: %1$s with SKU: %2$s Link: %3$s', 'dakabrand'),
        'contactWhatsAppAbout'  => __('Contact on WhatsApp about %s', 'dakabrand'),
    );
}

function staticbridge_whatsapp_number(): string
{
    return preg_replace('/\D+/', '', (string) apply_filters('staticbridge_whatsapp_number', '355683885286'));
}

/**
 * Return a configured public WPForms form only when WPForms is active and the
 * referenced form exists. The filters keep the deployment independent of the
 * IDs in any one database export.
 */
function staticbridge_wpforms_form_id(string $context): int
{
    $defaults = array(
        'contact'    => 351,
        'newsletter' => 472025,
    );

    if (!isset($defaults[$context]) || !function_exists('wpforms_display')) {
        return 0;
    }

    $form_id = (int) apply_filters(
        'staticbridge_wpforms_' . $context . '_form_id',
        $defaults[$context]
    );

    return $form_id > 0 && 'wpforms' === get_post_type($form_id) ? $form_id : 0;
}

/**
 * WordPress 6.5+ can consume the accompanying .l10n.php catalogue. This
 * lightweight fallback also keeps translations available on hosts that only
 * load binary MO files from theme directories.
 */
function staticbridge_theme_gettext_fallback(string $translation, string $text, string $domain): string
{
    static $messages = null;

    $woocommerce_messages = array(
        'Great things are on the horizon' => 'Gjëra të mëdha po vijnë',
        'Something big is brewing! Our store is in the works and will be launching soon!' => 'Diçka e madhe po përgatitet! Dyqani ynë është në përgatitje dhe do të hapet së shpejti!',
    );

    if ('sq_AL' !== staticbridge_requested_locale()) {
        return $translation;
    }

    if ('woocommerce' === $domain && isset($woocommerce_messages[$text])) {
        return $woocommerce_messages[$text];
    }

    if ('dakabrand' !== $domain) {
        return $translation;
    }

    if (null === $messages) {
        $catalogue = require get_template_directory() . '/languages/dakabrand-sq_AL.l10n.php';
        $messages = is_array($catalogue['messages'] ?? null) ? $catalogue['messages'] : array();
    }

    return isset($messages[$text]) ? (string) $messages[$text] : $translation;
}
add_filter('gettext', 'staticbridge_theme_gettext_fallback', 10, 3);

require_once get_template_directory() . '/inc/seo-document.php';
require_once get_template_directory() . '/inc/render-api.php';
require_once get_template_directory() . '/inc/product-data.php';
require_once get_template_directory() . '/inc/remittance-gateway.php';

function staticbridge_theme_setup(): void
{
    load_theme_textdomain('dakabrand', get_template_directory() . '/languages');

    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', array(
        'height'      => 42,
        'width'       => 86,
        'flex-height' => true,
        'flex-width'  => true,
    ));
    add_theme_support('html5', array('search-form', 'gallery', 'caption', 'style', 'script'));
    add_theme_support('woocommerce', array(
        'thumbnail_image_width' => 480,
        'single_image_width'    => 960,
        'product_grid'          => array(
            'default_rows'    => 3,
            'min_rows'        => 1,
            'max_rows'        => 12,
            'default_columns' => 4,
            'min_columns'     => 1,
            'max_columns'     => 6,
        ),
    ));

    register_nav_menus(array(
        'primary'            => __('Primary navigation', 'dakabrand'),
        'footer'             => __('Footer navigation (legacy)', 'dakabrand'),
        'footer_information' => __('Footer information', 'dakabrand'),
        'footer_services'    => __('Footer services', 'dakabrand'),
    ));
}
add_action('after_setup_theme', 'staticbridge_theme_setup');

/** Preserve the delivery cities configured by the production WooCommerce site. */
function staticbridge_add_delivery_countries(array $countries): array
{
    $countries['XK'] = __('Kosovo', 'woocommerce');

    return $countries;
}
add_filter('woocommerce_countries', 'staticbridge_add_delivery_countries');

function staticbridge_add_kosovo_to_europe(array $continents): array
{
    if (isset($continents['EU']['countries']) && !in_array('XK', $continents['EU']['countries'], true)) {
        $continents['EU']['countries'][] = 'XK';
    }

    return $continents;
}
add_filter('woocommerce_continents', 'staticbridge_add_kosovo_to_europe');

function staticbridge_delivery_cities(array $states): array
{
    $cities = array(
        'GB' => array(
            'GB-ENG' => 'England',
            'GB-NIR' => 'Northern Ireland',
            'GB-SCT' => 'Scotland',
            'GB-WLS' => 'Wales',
        ),
        'XK' => array(
            'XK-20' => 'Artanë', 'XK-21' => 'Deçan', 'XK-22' => 'Dragash', 'XK-23' => 'Drenas', 'XK-24' => 'Fushë Kosovë', 'XK-25' => 'Ferizaj', 'XK-26' => 'Gjilan', 'XK-27' => 'Gjakovë', 'XK-28' => 'Graçanicë', 'XK-29' => 'Hani I Elezit', 'XK-30' => 'Istog', 'XK-31' => 'Junik', 'XK-32' => 'Kaçanik', 'XK-33' => 'Klinë', 'XK-34' => 'Kamenicë', 'XK-35' => 'Kllokot', 'XK-36' => 'Leposaviq', 'XK-37' => 'Lipjan', 'XK-38' => 'Malishevë', 'XK-39' => 'Mamushë', 'XK-40' => 'Mitrovica Veriore', 'XK-41' => 'Mitrovicë', 'XK-42' => 'Novobërdë', 'XK-43' => 'Obiliq', 'XK-44' => 'Podujevë', 'XK-45' => 'Pejë', 'XK-46' => 'Prishtinë', 'XK-47' => 'Partesh', 'XK-48' => 'Prizren', 'XK-49' => 'Ranillug', 'XK-50' => 'Rahovec', 'XK-51' => 'Shtërpcë', 'XK-52' => 'Skënderaj', 'XK-53' => 'Shtime', 'XK-54' => 'Suharekë', 'XK-55' => 'Viti', 'XK-56' => 'Vushtrri', 'XK-57' => 'Zubin Potok', 'XK-58' => 'Zveçan',
        ),
        'AL' => array(
            'AL-20' => 'Kurbin', 'AL-21' => 'Kuçovë', 'AL-22' => 'Ksamil', 'AL-23' => 'Kolonjë', 'AL-24' => 'Krumë', 'AL-25' => 'Konispol', 'AL-26' => 'Krujë', 'AL-27' => 'Klos', 'AL-28' => 'Kavajë', 'AL-29' => 'Laç', 'AL-30' => 'Librazhd', 'AL-31' => 'Leskovik', 'AL-32' => 'Lushnjë', 'AL-33' => 'Mamuras', 'AL-34' => 'Milot', 'AL-35' => 'Mallakastër', 'AL-36' => 'Maliq', 'AL-37' => 'Malësi e Madhe', 'AL-38' => 'Mirditë', 'AL-39' => 'Mat', 'AL-40' => 'Orikum', 'AL-41' => 'Patos', 'AL-42' => 'Peqin', 'AL-43' => 'Pogradec', 'AL-44' => 'Pukë', 'AL-45' => 'Përmet', 'AL-46' => 'Poliçan', 'AL-47' => 'Peshkopi', 'AL-48' => 'Fushë Krujë', 'AL-49' => 'Prrenjas', 'AL-50' => 'Roskovec', 'AL-51' => 'Shijak', 'AL-52' => 'Maminas', 'AL-53' => 'Rrëshen', 'AL-54' => 'Skrapar', 'AL-55' => 'Sarandë', 'AL-56' => 'Sukth', 'AL-57' => 'Tepelenë', 'AL-58' => 'Tropojë', 'AL-59' => 'Vau i Dejës', 'AL-60' => 'Vorë', 'AL-61' => 'Ura Vajgurore', 'AL-62' => 'Bilisht', 'AL-66' => 'Tiranë Periferi',
        ),
        'MK' => array(
            'MK-20' => 'Bogdanca', 'MK-21' => 'Brod', 'MK-22' => 'Berova', 'MK-23' => 'Dellceva', 'MK-24' => 'Demir Hisar', 'MK-25' => 'Dibra e Madhe', 'MK-26' => 'Gostivar', 'MK-27' => 'Gjevgjelja', 'MK-28' => 'Kamenica', 'MK-29' => 'Kercove', 'MK-30' => 'Kumanova', 'MK-31' => 'Kocani', 'MK-32' => 'Kriva Palanka', 'MK-33' => 'Kratova', 'MK-34' => 'Krusheva', 'MK-35' => 'Kavadar', 'MK-36' => 'Manastir', 'MK-37' => 'Negotina', 'MK-38' => 'Oher', 'MK-39' => 'Peceva', 'MK-40' => 'Prilep', 'MK-41' => 'Probishtip', 'MK-42' => 'Radovisht', 'MK-43' => 'Resnja', 'MK-44' => 'Shtip', 'MK-45' => 'Shkup', 'MK-46' => 'Strumica', 'MK-47' => 'Sveti Nikola', 'MK-48' => 'Struga', 'MK-49' => 'Tetova', 'MK-50' => 'Vinica', 'MK-51' => 'Veles',
        ),
    );

    foreach ($cities as $country => $country_cities) {
        $states[$country] = array_replace($states[$country] ?? array(), $country_cities);
        asort($states[$country]);
    }

    return $states;
}
add_filter('woocommerce_states', 'staticbridge_delivery_cities');

/** Google Tag Manager is configurable while retaining the current production container by default. */
function staticbridge_google_tag_manager_container(): string
{
    return (string) apply_filters('staticbridge_google_tag_manager_container', 'GTM-WRCQP8LW');
}

function staticbridge_google_tag_manager_head(): void
{
    $container = staticbridge_google_tag_manager_container();
    if ('' === $container) {
        return;
    }
    ?>
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','<?php echo esc_js($container); ?>');</script>
    <?php
}

function staticbridge_google_tag_manager_body(): void
{
    $container = staticbridge_google_tag_manager_container();
    if ('' === $container) {
        return;
    }
    ?>
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo esc_attr($container); ?>" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <?php
}

function staticbridge_discount_rule_price(WC_Product $product): string
{
    $price = $product->get_price();
    $discount_price = apply_filters('advanced_woo_discount_rules_get_product_discount_price', $price, $product);

    return is_numeric($discount_price) ? (string) $discount_price : (string) $price;
}

function staticbridge_product_brands(int $product_id): array
{
    $terms = get_the_terms($product_id, 'product_brand');
    if (!is_array($terms)) {
        return array();
    }

    return array_map(static function (WP_Term $term): array {
        return array('id' => $term->term_id, 'name' => $term->name, 'slug' => $term->slug);
    }, $terms);
}

/** Keep the WooCommerce v3 product API compatible with the production data contract. */
function staticbridge_add_product_api_data(WP_REST_Response $response, WC_Product $product, WP_REST_Request $request): WP_REST_Response
{
    if (0 !== strpos($request->get_route(), '/wc/v3/products')) {
        return $response;
    }

    $data = $response->get_data();
    $discount_details = apply_filters('advanced_woo_discount_rules_get_product_discount_details', false, $product);
    $data['discount_details'] = $discount_details ?: (object) array();
    $data['sale_price'] = staticbridge_discount_rule_price($product);
    $data['brands'] = staticbridge_product_brands($product->get_id());
    $response->set_data($data);

    return $response;
}
add_filter('woocommerce_rest_prepare_product_object', 'staticbridge_add_product_api_data', 10, 3);

// Keep the discount-rule sale/strikeout display on unavailable products too.
// The catalog already includes those products in promotion results, and the
// product page must present the same promotion rather than the full price.
add_filter('advanced_woo_discount_rules_do_strikeout_for_out_of_stock_variants', '__return_true');

/** Keep unavailable variations from being selectable by storefront clients. */
function staticbridge_disable_out_of_stock_variations(bool $is_active, WC_Product_Variation $variation): bool
{
    return $variation->is_in_stock() ? $is_active : false;
}
add_filter('woocommerce_variation_is_active', 'staticbridge_disable_out_of_stock_variations', 10, 2);

/**
 * Return the permalink for a department's WordPress page.
 *
 * Keeping this lookup in one place means the campaign navigation continues to
 * work when an editor changes the Woman or Man page permalink. The route is
 * retained as a fallback for static/proxy installs where no page exists.
 */
function staticbridge_department_page_url(string $department): string
{
    static $urls = array();

    $department = sanitize_title($department);
    if (isset($urls[$department])) {
        return $urls[$department];
    }

    $page = get_page_by_path($department, OBJECT, 'page');

    // Editors commonly change a page slug while retaining its title. Use the
    // title as a second lookup so those permalink changes need no code edit.
    if (!$page instanceof WP_Post) {
        foreach (get_pages(array('post_status' => 'publish')) as $candidate) {
            if ($department === sanitize_title($candidate->post_title)) {
                $page = $candidate;
                break;
            }
        }
    }

    $fallback = home_url('/' . $department . '/');

    if (!$page instanceof WP_Post || 'publish' !== $page->post_status) {
        $urls[$department] = (string) apply_filters('staticbridge_department_page_url', $fallback, $department, null);
        return $urls[$department];
    }

    $urls[$department] = (string) apply_filters('staticbridge_department_page_url', get_permalink($page), $department, $page);
    return $urls[$department];
}

/**
 * Build a product-category archive URL that filters by a real product-category
 * slug and its current WordPress term name. The catalogue filter requires the
 * value in `slug:Name` form; category ancestry is not consistent across the
 * imported catalogue, so callers must provide the canonical archive path.
 */
function staticbridge_catalog_category_url(string $path, string $category = ''): string
{
    $url = home_url('/' . ltrim($path, '/'));

    if ('' === $category) {
        return $url;
    }

    $slug = sanitize_title($category);
    $name = ucwords(str_replace('-', ' ', preg_replace('/-(?:women|man)$/', '', $slug)));

    if (taxonomy_exists('product_cat')) {
        $term = get_term_by('slug', $slug, 'product_cat');

        if ($term instanceof WP_Term) {
            $name = $term->name;
        }
    }

    return add_query_arg('categories', $slug . ':' . $name, $url);
}

/**
 * Build a category archive URL limited to products available for preorder.
 */
function staticbridge_preorder_category_url(string $path, string $category = ''): string
{
    return add_query_arg(
        'stock_status',
        'onbackorder:onbackorder',
        staticbridge_catalog_category_url($path, $category)
    );
}

/**
 * The storefront gateway intentionally omits visible global navigation.
 * The explicit view check keeps generated front-page documents deterministic.
 */
function staticbridge_is_immersive_front_page(): bool
{
    return 'front-page' === get_query_var('staticbridge_view') || is_front_page();
}

/**
 * Keep the campaign route available on static/proxy installs without a page row.
 */
function staticbridge_is_man_request(): bool
{
    global $wp;

    return isset($wp->request) && 'man' === trim((string) $wp->request, '/');
}

function staticbridge_is_woman_request(): bool
{
    global $wp;

    return isset($wp->request) && 'woman' === trim((string) $wp->request, '/');
}

/**
 * The browser cart is deliberately independent of WooCommerce's server cart.
 */
function staticbridge_is_cart_request(): bool
{
    global $wp;

    return isset($wp->request) && 'cart' === trim((string) $wp->request, '/');
}

function staticbridge_is_checkout_request(): bool
{
    global $wp;

    if (isset($wp->request) && 'checkout' === trim((string) $wp->request, '/')) {
        return true;
    }

    // Store owners can change the WooCommerce checkout page slug. Use the
    // WooCommerce condition as a fallback so that page still receives this
    // theme's local-storage checkout, but leave order-received pages alone.
    return function_exists('is_checkout') && is_checkout()
        && !(function_exists('is_order_received_page') && is_order_received_page());
}

/**
 * Identify a department from the current product category or product's category
 * ancestry. This keeps the header department state intact beyond campaign pages.
 */
function staticbridge_is_department_context(string $department): bool
{
    $department = sanitize_title($department);

    // Static generation renders the view directly and does not always populate
    // $wp->request. The renderer sets this query var before loading the header.
    if ($department === get_query_var('staticbridge_view')) {
        return true;
    }

    if ('man' === $department && staticbridge_is_man_request()) {
        return true;
    }

    if ('woman' === $department && staticbridge_is_woman_request()) {
        return true;
    }

    /*
     * Product-category URLs retain their department in the permalink. This
     * fallback covers imported category terms whose parent relationship has
     * not been preserved, while the ancestry check below remains the source
     * of truth for normal WooCommerce category and product requests.
     */
    if (is_tax('product_cat') && isset($_SERVER['REQUEST_URI'])) {
        $request_path = (string) wp_parse_url(wp_unslash($_SERVER['REQUEST_URI']), PHP_URL_PATH);
        $department_path = '/product-category/' . $department;

        if ($request_path === $department_path || 0 === strpos($request_path, $department_path . '/')) {
            return true;
        }
    }

    if (!taxonomy_exists('product_cat')) {
        return false;
    }

    $term_ids = array();

    if (is_tax('product_cat')) {
        $term = get_queried_object();
        if ($term instanceof WP_Term) {
            $term_ids[] = (int) $term->term_id;
        }
    } elseif (is_singular('product')) {
        $terms = get_the_terms(get_queried_object_id(), 'product_cat');
        if (is_array($terms)) {
            $term_ids = wp_list_pluck($terms, 'term_id');
        }
    }

    foreach ($term_ids as $term_id) {
        $candidate_ids = array_merge(array((int) $term_id), get_ancestors((int) $term_id, 'product_cat'));
        foreach ($candidate_ids as $candidate_id) {
            $candidate = get_term((int) $candidate_id, 'product_cat');
            if ($candidate instanceof WP_Term && $department === $candidate->slug) {
                return true;
            }
        }
    }

    return false;
}

function staticbridge_man_template(string $template): string
{
    if (!staticbridge_is_man_request()) {
        return $template;
    }

    global $wp_query;

    if ($wp_query instanceof WP_Query) {
        $wp_query->is_404  = false;
        $wp_query->is_page = true;
    }

    status_header(200);

    return get_theme_file_path('/page-man.php');
}
add_filter('template_include', 'staticbridge_man_template', 99);

function staticbridge_woman_template(string $template): string
{
    if (!staticbridge_is_woman_request()) {
        return $template;
    }

    global $wp_query;

    if ($wp_query instanceof WP_Query) {
        $wp_query->is_404  = false;
        $wp_query->is_page = true;
    }

    status_header(200);

    return get_theme_file_path('/page-woman.php');
}
add_filter('template_include', 'staticbridge_woman_template', 99);

function staticbridge_cart_template(string $template): string
{
    if (!staticbridge_is_cart_request()) {
        return $template;
    }

    global $wp_query;

    if ($wp_query instanceof WP_Query) {
        $wp_query->is_404  = false;
        $wp_query->is_page = true;
    }

    status_header(200);

    return get_theme_file_path('/page-cart.php');
}
add_filter('template_include', 'staticbridge_cart_template', 99);

function staticbridge_checkout_template(string $template): string
{
    if (!staticbridge_is_checkout_request()) {
        return $template;
    }

    global $wp_query;

    if ($wp_query instanceof WP_Query) {
        $wp_query->is_404  = false;
        $wp_query->is_page = true;
    }

    status_header(200);

    return get_theme_file_path('/page-checkout.php');
}
add_filter('template_include', 'staticbridge_checkout_template', 99);

/**
 * WooCommerce redirects an empty server-side cart away from checkout before
 * template selection. The browser cart is intentionally independent, so its
 * checkout route must remain available even when WooCommerce has no session.
 */
function staticbridge_allow_empty_local_checkout(bool $redirect): bool
{
    return staticbridge_is_checkout_request() ? false : $redirect;
}
add_filter('woocommerce_checkout_redirect_empty_cart', 'staticbridge_allow_empty_local_checkout', 99);

// The storefront renders its own document shell and assets. Suppress the
// WordPress toolbar so its footer hooks cannot inject a second script bundle.
add_filter('show_admin_bar', '__return_false', 999);

function staticbridge_man_canonical_redirect($redirect_url)
{
    return staticbridge_is_man_request() || staticbridge_is_woman_request() || staticbridge_is_cart_request() || staticbridge_is_checkout_request() ? false : $redirect_url;
}
add_filter('redirect_canonical', 'staticbridge_man_canonical_redirect');

function staticbridge_man_document_title(array $title): array
{
    if (staticbridge_is_man_request()) {
        $title['title'] = __('Man', 'dakabrand');
    } elseif (staticbridge_is_woman_request()) {
        $title['title'] = __('Woman', 'dakabrand');
    } elseif (staticbridge_is_cart_request()) {
        $title['title'] = __('Cart', 'dakabrand');
    } elseif (staticbridge_is_checkout_request()) {
        $title['title'] = __('Checkout', 'dakabrand');
    }

    return $title;
}
add_filter('document_title_parts', 'staticbridge_man_document_title');

/**
 * Provide useful storefront links until an administrator assigns a menu.
 * WordPress passes the wp_nav_menu() arguments as an object to fallbacks.
 */
function staticbridge_primary_menu_fallback($args): void
{
    $menu_class = is_array($args) && !empty($args['menu_class'])
        ? (string) $args['menu_class']
        : (!empty($args->menu_class) ? (string) $args->menu_class : 'site-navigation__menu');
    ?>
    <ul class="<?php echo esc_attr($menu_class); ?>">
        <li><a href="<?php echo esc_url(staticbridge_department_page_url('woman')); ?>"><?php esc_html_e('Women', 'dakabrand'); ?></a></li>
        <li><a href="<?php echo esc_url(staticbridge_department_page_url('man')); ?>"><?php esc_html_e('Man', 'dakabrand'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/product-category/watches/')); ?>"><?php esc_html_e('Watches', 'dakabrand'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/shop/?stock_status=onbackorder%3Aonbackorder')); ?>"><?php esc_html_e('15 Days Preorder', 'dakabrand'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/product-category/women/clothing/bikini/')); ?>"><?php esc_html_e('Bikini', 'dakabrand'); ?></a></li>
        <li class="menu-item-has-children">
            <a href="<?php echo esc_url(home_url('/shop/')); ?>"><?php esc_html_e('New Collection', 'dakabrand'); ?></a>
            <ul class="sub-menu">
                <li><a href="<?php echo esc_url(home_url('/product-category/man/new-collection-man/')); ?>"><?php esc_html_e('Man', 'dakabrand'); ?></a></li>
                <li><a href="<?php echo esc_url(home_url('/product-category/women/new-collection-women/')); ?>"><?php esc_html_e('Women', 'dakabrand'); ?></a></li>
            </ul>
        </li>
    </ul>
    <?php
}

function staticbridge_footer_information_fallback($args = array()): void
{
    ?>
    <ul class="footer-menu">
        <li><a href="<?php echo esc_url(home_url('/cart/')); ?>" data-cart-link><?php esc_html_e('My Cart', 'dakabrand'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/shop/')); ?>"><?php esc_html_e('Shop', 'dakabrand'); ?></a></li>
    </ul>
    <?php
}

/** Do not render account or wishlist entry points from WordPress-managed storefront menus. */
function staticbridge_hide_restricted_menu_items(array $items): array
{
    return array_values(array_filter($items, static function ($item): bool {
        $path = (string) wp_parse_url((string) ($item->url ?? ''), PHP_URL_PATH);
        $path = trim(strtolower($path), '/');
        return !($path === 'my-account' || str_starts_with($path, 'my-account/') ||
            in_array($path, array('login', 'register', 'wp-login.php', 'wp-register.php', 'wishlist'), true));
    }));
}
add_filter('wp_nav_menu_objects', 'staticbridge_hide_restricted_menu_items');

function staticbridge_hide_account_page(): void
{
    if (!is_page('my-account')) {
        return;
    }
    global $wp_query;
    $wp_query->set_404();
    status_header(404);
    nocache_headers();
}
add_action('template_redirect', 'staticbridge_hide_account_page', 1);

function staticbridge_disable_classic_checkout_script(): void
{
    if (staticbridge_is_checkout_request()) {
        wp_dequeue_script('wc-checkout');
    }
}
add_action('wp_enqueue_scripts', 'staticbridge_disable_classic_checkout_script', 100);

function staticbridge_enqueue_assets(): void
{
    $bootstrap_css_path = get_template_directory() . '/assets/vendor/bootstrap/bootstrap.min.css';
    $bootstrap_js_path  = get_template_directory() . '/assets/vendor/bootstrap/bootstrap.bundle.min.js';
    $css_path = get_template_directory() . '/assets/css/main.css';
    $js_path  = get_template_directory() . '/assets/js/main.js';
    $order_attribution_js_path = get_template_directory() . '/assets/js/order-attribution.js';
    $cart_js_path = get_template_directory() . '/assets/js/cart.js';
    $video_popup_css_path = get_template_directory() . '/assets/css/video-popup.css';
    $video_popup_js_path = get_template_directory() . '/assets/js/video-popup.js';

    wp_enqueue_style(
        'staticbridge-bootstrap',
        get_template_directory_uri() . '/assets/vendor/bootstrap/bootstrap.min.css',
        array(),
        file_exists($bootstrap_css_path) ? STATICBRIDGE_BOOTSTRAP_VERSION : null
    );

    wp_enqueue_style(
        'staticbridge-main',
        get_template_directory_uri() . '/assets/css/main.css',
        array('staticbridge-bootstrap'),
        file_exists($css_path) ? (string) filemtime($css_path) : STATICBRIDGE_THEME_VERSION
    );

    wp_enqueue_script(
        'staticbridge-bootstrap',
        get_template_directory_uri() . '/assets/vendor/bootstrap/bootstrap.bundle.min.js',
        array(),
        file_exists($bootstrap_js_path) ? STATICBRIDGE_BOOTSTRAP_VERSION : null,
        true
    );

    wp_enqueue_script(
        'staticbridge-main',
        get_template_directory_uri() . '/assets/js/main.js',
        array('staticbridge-bootstrap'),
        file_exists($js_path) ? (string) filemtime($js_path) : STATICBRIDGE_THEME_VERSION,
        true
    );

    wp_enqueue_script(
        'staticbridge-order-attribution',
        get_template_directory_uri() . '/assets/js/order-attribution.js',
        array(),
        file_exists($order_attribution_js_path) ? (string) filemtime($order_attribution_js_path) : STATICBRIDGE_THEME_VERSION,
        true
    );

    wp_enqueue_style(
        'staticbridge-video-popup',
        get_template_directory_uri() . '/assets/css/video-popup.css',
        array('staticbridge-main'),
        file_exists($video_popup_css_path) ? (string) filemtime($video_popup_css_path) : STATICBRIDGE_THEME_VERSION
    );

    wp_enqueue_script(
        'staticbridge-video-popup',
        get_template_directory_uri() . '/assets/js/video-popup.js',
        array(),
        file_exists($video_popup_js_path) ? (string) filemtime($video_popup_js_path) : STATICBRIDGE_THEME_VERSION,
        true
    );

    wp_localize_script('staticbridge-video-popup', 'StaticBridgeVideoPopup', array(
        'videoUrl' => (string) apply_filters(
            'staticbridge_video_popup_url',
            'https://dakabrand.uk/wp-content/uploads/2026/09/dakabrand_backtoschool.mp4'
        ),
        'storageKey' => 'daka_back_to_school_popup',
        'maxShowsPerDay' => 2,
        'hoursBetweenShows' => 5,
        'showDelay' => 1000,
        'dialogLabel' => __('Daka Outlet Back to School', 'dakabrand'),
        'closeLabel' => __('Close video popup', 'dakabrand'),
    ));

    wp_enqueue_script(
        'staticbridge-cart',
        get_template_directory_uri() . '/assets/js/cart.js',
        array('staticbridge-main'),
        file_exists($cart_js_path) ? (string) filemtime($cart_js_path) : STATICBRIDGE_THEME_VERSION,
        true
    );

    $view = get_query_var('staticbridge_view');
    $is_catalog = 'product-archive' === $view
        || is_post_type_archive('product')
        || is_tax('product_cat')
        || (function_exists('is_shop') && is_shop());

    $has_catalog_grids = $is_catalog || in_array($view, array('man', 'woman', 'product'), true)
        || staticbridge_is_man_request() || staticbridge_is_woman_request()
        || (function_exists('is_product') && is_product());

    if ($has_catalog_grids) {
        $catalog_css_path = get_template_directory() . '/assets/css/catalog.css';
        $catalog_js_path = get_template_directory() . '/assets/js/catalog.js';

        wp_enqueue_style(
            'staticbridge-catalog',
            get_template_directory_uri() . '/assets/css/catalog.css',
            array('staticbridge-main'),
            file_exists($catalog_css_path) ? (string) filemtime($catalog_css_path) : STATICBRIDGE_THEME_VERSION
        );
        wp_enqueue_script(
            'staticbridge-catalog',
            get_template_directory_uri() . '/assets/js/catalog.js',
            array('staticbridge-main'),
            file_exists($catalog_js_path) ? (string) filemtime($catalog_js_path) : STATICBRIDGE_THEME_VERSION,
            true
        );
    }

    $is_product = 'product' === get_query_var('staticbridge_view')
        || (function_exists('is_product') && is_product());
    if ($is_product) {
        $product_js_path = get_template_directory() . '/assets/js/product.js';
        wp_enqueue_script(
            'staticbridge-product',
            get_template_directory_uri() . '/assets/js/product.js',
            array('staticbridge-cart'),
            file_exists($product_js_path) ? (string) filemtime($product_js_path) : STATICBRIDGE_THEME_VERSION,
            true
        );
    }

    if ('checkout' === $view || staticbridge_is_checkout_request()) {
        $checkout_js_path = get_template_directory() . '/assets/js/checkout.js';
        wp_enqueue_script(
            'staticbridge-checkout',
            get_template_directory_uri() . '/assets/js/checkout.js',
            array('staticbridge-cart', 'staticbridge-order-attribution'),
            file_exists($checkout_js_path) ? (string) filemtime($checkout_js_path) : STATICBRIDGE_THEME_VERSION,
            true
        );
    }

    if ('contact' === $view || is_page('contact-us')) {
        $contact_js_path = get_template_directory() . '/assets/js/contact.js';
        wp_enqueue_script(
            'staticbridge-contact',
            get_template_directory_uri() . '/assets/js/contact.js',
            array(),
            file_exists($contact_js_path) ? (string) filemtime($contact_js_path) : STATICBRIDGE_THEME_VERSION,
            true
        );

        wp_localize_script('staticbridge-contact', 'StaticBridgeContact', array(
            // Configure a delivery service later; an empty endpoint sends nothing.
            'endpoint' => (string) apply_filters('staticbridge_contact_endpoint', ''),
            'messages' => array(
                'unavailable' => __('Online messages are temporarily unavailable. Please use the contact details on this page.', 'dakabrand'),
                'success'     => __('Thank you. Your message has been sent.', 'dakabrand'),
                'error'       => __('We could not send your message. Please try again.', 'dakabrand'),
            ),
        ));
    }

    wp_localize_script('staticbridge-main', 'StaticBridgeConfig', array(
        'renderApiVersion' => STATICBRIDGE_RENDER_API_VERSION,
        'apiBase'          => (string) apply_filters('staticbridge_api_base',
            'local' === wp_get_environment_type() ? '/wp-json/' : '/api/'),
        'cartStorageKey'   => 'staticbridge_cart_v1',
        'stockModeStorageKey' => 'staticbridge_stock_mode_v1',
        'cartUrl'          => home_url('/cart/'),
        'shopUrl'          => home_url('/shop/'),
        'whatsappNumber'   => staticbridge_whatsapp_number(),
        'locale'           => staticbridge_requested_locale(),
        'languageStorageKey' => 'staticbridge_language_v1',
        // This is emitted into every generated document, so changing language
        // never requires a runtime request to WordPress or the API proxy.
        'languageCatalogues' => array('sq_AL' => staticbridge_language_messages('sq_AL')),
        'messages'         => staticbridge_frontend_messages(),
        'staticLabels'     => array(
            'Clothing' => __('Clothing', 'dakabrand'), 'Shoes' => __('Shoes', 'dakabrand'),
            'Accessories' => __('Accessories', 'dakabrand'), 'Boots' => __('Boots', 'dakabrand'),
            'Preorder' => __('Preorder', 'dakabrand'), 'T-Shirts' => __('T-Shirts', 'dakabrand'),
            'Jackets' => __('Jackets', 'dakabrand'), 'Jeans' => __('Jeans', 'dakabrand'),
            'Outfits & Sets' => __('Outfits & Sets', 'dakabrand'), 'Pants' => __('Pants', 'dakabrand'),
            'Dresses' => __('Dresses', 'dakabrand'), 'Shirts' => __('Shirts', 'dakabrand'),
            'Swimwear' => __('Swimwear', 'dakabrand'), 'Bikini' => __('Bikini', 'dakabrand'),
            'Sweatpants' => __('Sweatpants', 'dakabrand'), 'Body' => __('Body', 'dakabrand'),
            'Sneakers' => __('Sneakers', 'dakabrand'), 'Loafers' => __('Loafers', 'dakabrand'),
            'Slippers' => __('Slippers', 'dakabrand'), 'Bags' => __('Bags', 'dakabrand'),
            'Belts' => __('Belts', 'dakabrand'), 'Watches' => __('Watches', 'dakabrand'),
            'Sunglasses' => __('Sunglasses', 'dakabrand'), 'Hats' => __('Hats', 'dakabrand'),
            'Scarves' => __('Scarves', 'dakabrand'), 'Jewelry' => __('Jewelry', 'dakabrand'),
            'Hoodies' => __('Hoodies', 'dakabrand'), 'T-Shirt' => __('T-Shirt', 'dakabrand'),
            'Track Suits' => __('Track Suits', 'dakabrand'), 'Wallets' => __('Wallets', 'dakabrand'),
            'Luggage' => __('Luggage', 'dakabrand'),
        ),
        /**
         * The future proxy can provide an absolute or same-origin endpoint.
         * An empty value shows the unavailable notice instead of the form.
         */
        'newsletterEndpoint' => (string) apply_filters('staticbridge_newsletter_endpoint', ''),
        'newsletterMessages' => array(
            'unavailable' => __('Newsletter signup is temporarily unavailable. Please try again later.', 'dakabrand'),
            'success'     => __('Thank you for subscribing.', 'dakabrand'),
            'error'       => __('We could not complete your signup. Please try again.', 'dakabrand'),
        ),
    ));
}
add_action('wp_enqueue_scripts', 'staticbridge_enqueue_assets');
