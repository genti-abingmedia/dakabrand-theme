<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Resolve theme-native pages before a static renderer falls back to the
 * generic page view. Static generation calls staticbridge_render_document()
 * directly, so WordPress's usual page-template hierarchy does not run there.
 */
function staticbridge_campaign_page_view(): ?string
{
    $page_id = (int) get_queried_object_id();
    if ($page_id <= 0) {
        return null;
    }

    $template_views = array(
        'page-man.php'      => 'man',
        'page-woman.php'    => 'woman',
        'page-cart.php'     => 'cart',
        'page-checkout.php' => 'checkout',
        'page-contact-us.php'       => 'contact',
        'page-privacy-policy.php'   => 'policy',
        'page-terms-conditions.php' => 'policy',
        'page-refund_returns.php'   => 'policy',
    );
    $template = ltrim(str_replace('\\', '/', (string) get_page_template_slug($page_id)), '/');

    if (isset($template_views[$template])) {
        return $template_views[$template];
    }

    // Preserve the theme-native routes for pages that have not yet been
    // assigned their explicit template in the WordPress page editor.
    $page = get_post($page_id);
    if ($page instanceof WP_Post && 'page' === $page->post_type) {
        $slug_views = array(
            'man'      => 'man',
            'woman'    => 'woman',
            'cart'     => 'cart',
            'checkout' => 'checkout',
            'contact-us'       => 'contact',
            'privacy-policy'   => 'policy',
            'terms-conditions' => 'policy',
            'refund_returns'   => 'policy',
        );
        $slug = sanitize_title($page->post_name);

        if (isset($slug_views[$slug])) {
            return $slug_views[$slug];
        }
    }

    return null;
}

/**
 * Public contract used by both WordPress templates and the static generator.
 *
 * The generator is responsible for preparing the correct global $wp_query and
 * queried object before calling this function.
 */
function staticbridge_render_document_start(): void
{
    $seo = staticbridge_seo_document();
    ?>
    <!doctype html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php staticbridge_render_seo_head($seo); ?>
        <?php staticbridge_render_document_assets(); ?>
    </head>
    <body <?php body_class(); ?>>
    <?php staticbridge_render_tag_manager_body(); ?>
    <?php get_template_part('header'); ?>
    <?php
}

function staticbridge_render_document_end(): void
{
    ?>
    <?php get_template_part('footer'); ?>
    <?php staticbridge_render_document_scripts(); ?>
    </body>
    </html>
    <?php
}

function staticbridge_render_document(string $view, array $context = array()): void
{
    if ('page' === $view) {
        $campaign_view = staticbridge_campaign_page_view();
        if (null !== $campaign_view) {
            $view = $campaign_view;
        }
    }

    if ('page' === $view && is_page('my-account')) {
        throw new InvalidArgumentException('The account page must not be published as static storefront HTML.');
    }
    $allowed_views = array(
        'front-page',
        'man',
        'woman',
        'cart',
        'checkout',
        'contact',
        'policy',
        'index',
        'page',
        'product',
        'product-archive',
        '404',
    );

    if (!in_array($view, $allowed_views, true)) {
        throw new InvalidArgumentException('Unsupported StaticBridge view: ' . $view);
    }

    set_query_var('staticbridge_context', $context);
    set_query_var('staticbridge_view', $view);

    staticbridge_render_document_start();

    /**
     * Allows the frontend team to add shared markup without changing the
     * generator plugin.
     */
    do_action('staticbridge_before_main', $view, $context);

    get_template_part('template-parts/views/' . $view);

    do_action('staticbridge_after_main', $view, $context);

    staticbridge_render_document_end();
}

/**
 * Metadata the generator can inspect before rendering.
 */
function staticbridge_render_contract(): array
{
    return array(
        'version' => STATICBRIDGE_RENDER_API_VERSION,
        'excluded_paths' => array('/my-account/'),
        'views'   => array(
            'front_page'      => 'front-page',
            'man_page'        => 'man',
            'woman_page'      => 'woman',
            'cart_page'       => 'cart',
            'checkout_page'   => 'checkout',
            'contact_page'    => 'contact',
            'privacy_policy_page'   => 'policy',
            'terms_conditions_page' => 'policy',
            'refund_returns_page'   => 'policy',
            'page'            => 'page',
            'product'         => 'product',
            'product_archive' => 'product-archive',
            'product_category'=> 'product-archive',
            'fallback'        => 'index',
            'not_found'       => '404',
        ),
    );
}
