<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Public contract used by both WordPress templates and the static generator.
 *
 * The generator is responsible for preparing the correct global $wp_query and
 * queried object before calling this function.
 */
function staticbridge_render_document(string $view, array $context = array()): void
{
    $allowed_views = array(
        'front-page',
        'man',
        'woman',
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

    get_header();

    /**
     * Allows the frontend team to add shared markup without changing the
     * generator plugin.
     */
    do_action('staticbridge_before_main', $view, $context);

    get_template_part('template-parts/views/' . $view);

    do_action('staticbridge_after_main', $view, $context);

    get_footer();
}

/**
 * Metadata the generator can inspect before rendering.
 */
function staticbridge_render_contract(): array
{
    return array(
        'version' => STATICBRIDGE_RENDER_API_VERSION,
        'views'   => array(
            'front_page'      => 'front-page',
            'man_page'        => 'man',
            'woman_page'      => 'woman',
            'page'            => 'page',
            'product'         => 'product',
            'product_archive' => 'product-archive',
            'product_category'=> 'product-archive',
            'fallback'        => 'index',
            'not_found'       => '404',
        ),
    );
}
