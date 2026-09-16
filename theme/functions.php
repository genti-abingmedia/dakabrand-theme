<?php

if (!defined('ABSPATH')) {
    exit;
}

define('STATICBRIDGE_THEME_VERSION', '0.2.0');
define('STATICBRIDGE_RENDER_API_VERSION', '1.0');
define('STATICBRIDGE_BOOTSTRAP_VERSION', '5.3.8');

require_once get_template_directory() . '/inc/render-api.php';
require_once get_template_directory() . '/inc/product-data.php';

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

function staticbridge_man_canonical_redirect($redirect_url)
{
    return staticbridge_is_man_request() || staticbridge_is_woman_request() ? false : $redirect_url;
}
add_filter('redirect_canonical', 'staticbridge_man_canonical_redirect');

function staticbridge_man_document_title(array $title): array
{
    if (staticbridge_is_man_request()) {
        $title['title'] = __('Man', 'dakabrand');
    } elseif (staticbridge_is_woman_request()) {
        $title['title'] = __('Woman', 'dakabrand');
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
        <li><a href="<?php echo esc_url(home_url('/product-category/women/')); ?>"><?php esc_html_e('Women', 'dakabrand'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/product-category/man/')); ?>"><?php esc_html_e('Man', 'dakabrand'); ?></a></li>
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
        <li><a href="<?php echo esc_url(home_url('/my-account/')); ?>"><?php esc_html_e('My Account', 'dakabrand'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/cart/')); ?>"><?php esc_html_e('My Cart', 'dakabrand'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/wishlist/')); ?>"><?php esc_html_e('Wishlist', 'dakabrand'); ?></a></li>
        <li><a href="<?php echo esc_url(home_url('/checkout/')); ?>"><?php esc_html_e('Checkout', 'dakabrand'); ?></a></li>
    </ul>
    <?php
}

function staticbridge_enqueue_assets(): void
{
    $bootstrap_css_path = get_template_directory() . '/assets/vendor/bootstrap/bootstrap.min.css';
    $bootstrap_js_path  = get_template_directory() . '/assets/vendor/bootstrap/bootstrap.bundle.min.js';
    $css_path = get_template_directory() . '/assets/css/main.css';
    $js_path  = get_template_directory() . '/assets/js/main.js';

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

    $is_catalog = 'product-archive' === get_query_var('staticbridge_view')
        || is_post_type_archive('product')
        || is_tax('product_cat')
        || (function_exists('is_shop') && is_shop());

    if ($is_catalog) {
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

    wp_localize_script('staticbridge-main', 'StaticBridgeConfig', array(
        'renderApiVersion' => STATICBRIDGE_RENDER_API_VERSION,
        'apiBase'          => home_url('/api/'),
        'cartStorageKey'   => 'staticbridge_cart_v1',
        /**
         * The future proxy can provide an absolute or same-origin endpoint.
         * An empty value keeps the form visible but prevents false submissions.
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
