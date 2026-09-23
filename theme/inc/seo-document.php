<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * The custom document renderer owns every crawl-facing tag.  Keep this
 * separate from WordPress's conventional head hooks so generated storefront
 * pages are deterministic and never accidentally combine plugin metadata with
 * theme metadata.
 */
function staticbridge_seo_public_url(string $url): string
{
    $origin = home_url('/');
    $parts = wp_parse_url($url);
    if (!is_array($parts) || empty($parts['path'])) {
        return $origin;
    }

    $path = '/' . ltrim((string) $parts['path'], '/');
    $canonical = home_url($path);
    if (!empty($parts['query'])) {
        $canonical .= '?' . $parts['query'];
    }

    return $canonical;
}

function staticbridge_seo_current_page(): int
{
    return max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
}

function staticbridge_seo_has_noncanonical_query(): bool
{
    $allowed = array('paged', 'page');
    foreach (array_keys($_GET) as $key) {
        if (!in_array((string) $key, $allowed, true)) {
            return true;
        }
    }
    return false;
}

function staticbridge_seo_product_availability(WC_Product $product): string
{
    if ($product->is_in_stock()) {
        return 'https://schema.org/InStock';
    }
    if ('onbackorder' === $product->get_stock_status()) {
        return 'https://schema.org/PreOrder';
    }
    return 'https://schema.org/OutOfStock';
}

function staticbridge_seo_breadcrumbs(): array
{
    $items = array(array('name' => get_bloginfo('name'), 'url' => home_url('/')));
    if (function_exists('is_product') && is_product()) {
        $product = wc_get_product(get_queried_object_id());
        if (!$product instanceof WC_Product) {
            return array();
        }
        $items[] = array('name' => __('Shop', 'dakabrand'), 'url' => wc_get_page_permalink('shop'));
        $items[] = array('name' => $product->get_name(), 'url' => get_permalink($product->get_id()));
        return $items;
    }
    if (is_tax('product_cat')) {
        $term = get_queried_object();
        if (!$term instanceof WP_Term) {
            return array();
        }
        $items[] = array('name' => __('Shop', 'dakabrand'), 'url' => wc_get_page_permalink('shop'));
        foreach (array_reverse(get_ancestors($term->term_id, 'product_cat', 'taxonomy')) as $ancestor_id) {
            $ancestor = get_term($ancestor_id, 'product_cat');
            if ($ancestor instanceof WP_Term) {
                $url = get_term_link($ancestor);
                if (!is_wp_error($url)) {
                    $items[] = array('name' => $ancestor->name, 'url' => $url);
                }
            }
        }
        $items[] = array('name' => $term->name, 'url' => get_term_link($term));
        return $items;
    }
    return array();
}

function staticbridge_seo_document(): array
{
    $site_name = get_bloginfo('name') ?: 'DakaBrand';
    $description = trim(wp_strip_all_tags((string) get_bloginfo('description')));
    $title = $site_name;
    $canonical = home_url('/');
    $image = get_template_directory_uri() . '/assets/images/dakabrand-logo.png';
    $indexable = true;
    $type = 'website';

    if (is_404()) {
        $title = __('Page not found', 'dakabrand') . ' | ' . $site_name;
        $indexable = false;
    } elseif (is_search() || staticbridge_is_cart_request() || staticbridge_is_checkout_request() || is_page('my-account')) {
        $title = wp_get_document_title();
        $canonical = home_url('/');
        $indexable = false;
    } elseif (function_exists('is_product') && is_product()) {
        $product = wc_get_product(get_queried_object_id());
        if ($product instanceof WC_Product) {
            $title = $product->get_name() . ' | ' . $site_name;
            $description = trim(wp_strip_all_tags($product->get_short_description() ?: $product->get_description()));
            $canonical = get_permalink($product->get_id());
            $image = wp_get_attachment_image_url($product->get_image_id(), 'full') ?: $image;
            $type = 'product';
        }
    } elseif (is_tax('product_cat')) {
        $term = get_queried_object();
        if ($term instanceof WP_Term) {
            $title = $term->name . ' | ' . $site_name;
            $description = trim(wp_strip_all_tags(term_description($term)));
            $canonical = (string) get_term_link($term);
        }
    } elseif (function_exists('is_shop') && is_shop()) {
        $title = __('Shop', 'dakabrand') . ' | ' . $site_name;
        $canonical = wc_get_page_permalink('shop');
    } elseif (is_page() || is_singular()) {
        $post_id = get_queried_object_id();
        $title = get_the_title($post_id) . ' | ' . $site_name;
        $description = trim(wp_strip_all_tags(get_the_excerpt($post_id)));
        if ('' === $description) {
            $description = trim(wp_strip_all_tags((string) get_post_field('post_content', $post_id)));
        }
        $canonical = get_permalink($post_id);
        $image = get_the_post_thumbnail_url($post_id, 'full') ?: $image;
    } elseif (is_home() || is_front_page()) {
        $canonical = home_url('/');
    }

    $page = staticbridge_seo_current_page();
    if ((is_post_type_archive('product') || is_tax('product_cat') || (function_exists('is_shop') && is_shop())) && $page > 1) {
        $canonical = get_pagenum_link($page);
        $title = sprintf(__('%1$s – Page %2$d | %3$s', 'dakabrand'), wp_strip_all_tags($title), $page, $site_name);
    }
    if (staticbridge_seo_has_noncanonical_query()) {
        $indexable = false;
    }

    return array(
        'title' => wp_strip_all_tags($title),
        'description' => wp_trim_words($description, 32, ''),
        'canonical' => staticbridge_seo_public_url((string) $canonical),
        'robots' => $indexable ? 'index,follow' : 'noindex,follow',
        'indexable' => $indexable,
        'image' => staticbridge_seo_public_url($image),
        'type' => $type,
        'breadcrumbs' => staticbridge_seo_breadcrumbs(),
    );
}

function staticbridge_seo_jsonld(array $seo): array
{
    $site_url = home_url('/');
    $organization_id = $site_url . '#organization';
    $website_id = $site_url . '#website';
    $graph = array(
        array('@type' => 'Organization', '@id' => $organization_id, 'name' => get_bloginfo('name'), 'url' => $site_url, 'logo' => staticbridge_document_asset_url('assets/images/dakabrand-logo.png')),
        array('@type' => 'WebSite', '@id' => $website_id, 'url' => $site_url, 'name' => get_bloginfo('name'), 'publisher' => array('@id' => $organization_id)),
    );

    if ($seo['breadcrumbs']) {
        $items = array();
        foreach ($seo['breadcrumbs'] as $position => $crumb) {
            if (is_wp_error($crumb['url'])) {
                continue;
            }
            $items[] = array('@type' => 'ListItem', 'position' => $position + 1, 'name' => $crumb['name'], 'item' => staticbridge_seo_public_url($crumb['url']));
        }
        if ($items) {
            $graph[] = array('@type' => 'BreadcrumbList', '@id' => $seo['canonical'] . '#breadcrumb', 'itemListElement' => $items);
        }
    }

    if (function_exists('is_product') && is_product()) {
        $product = wc_get_product(get_queried_object_id());
        if ($product instanceof WC_Product && $product->get_price() !== '') {
            $product_node = array(
                '@type' => 'Product', '@id' => $seo['canonical'] . '#product', 'name' => $product->get_name(),
                'url' => $seo['canonical'], 'image' => array($seo['image']),
                'description' => trim(wp_strip_all_tags($product->get_short_description() ?: $product->get_description())),
            );
            if ($product->get_sku()) {
                $product_node['sku'] = $product->get_sku();
            }
            $brands = get_the_terms($product->get_id(), 'product_brand');
            if (is_array($brands) && $brands) {
                $product_node['brand'] = array('@type' => 'Brand', 'name' => $brands[0]->name);
            }
            if ($product->is_type('variable')) {
                $prices = array();
                foreach ($product->get_children() as $variation_id) {
                    $variation = wc_get_product($variation_id);
                    if ($variation instanceof WC_Product && $variation->is_purchasable()) {
                        $prices[] = (float) staticbridge_discount_rule_price($variation);
                    }
                }
                if ($prices) {
                    $product_node['offers'] = array('@type' => 'AggregateOffer', 'priceCurrency' => get_woocommerce_currency(), 'lowPrice' => (string) min($prices), 'highPrice' => (string) max($prices), 'offerCount' => count($prices), 'availability' => staticbridge_seo_product_availability($product), 'seller' => array('@id' => $organization_id));
                }
            } else {
                $product_node['offers'] = array('@type' => 'Offer', 'url' => $seo['canonical'], 'priceCurrency' => get_woocommerce_currency(), 'price' => staticbridge_discount_rule_price($product), 'availability' => staticbridge_seo_product_availability($product), 'itemCondition' => 'https://schema.org/NewCondition', 'seller' => array('@id' => $organization_id));
            }
            $graph[] = $product_node;
        }
    }
    return array('@context' => 'https://schema.org', '@graph' => $graph);
}

function staticbridge_render_seo_head(array $seo): void
{
    ?>
    <title><?php echo esc_html($seo['title']); ?></title>
    <?php if ($seo['description']) : ?><meta name="description" content="<?php echo esc_attr($seo['description']); ?>"><?php endif; ?>
    <meta name="robots" content="<?php echo esc_attr($seo['robots']); ?>">
    <link rel="canonical" href="<?php echo esc_url($seo['canonical']); ?>">
    <meta property="og:type" content="<?php echo esc_attr($seo['type']); ?>">
    <meta property="og:site_name" content="<?php echo esc_attr(get_bloginfo('name')); ?>">
    <meta property="og:title" content="<?php echo esc_attr($seo['title']); ?>">
    <?php if ($seo['description']) : ?><meta property="og:description" content="<?php echo esc_attr($seo['description']); ?>"><?php endif; ?>
    <meta property="og:url" content="<?php echo esc_url($seo['canonical']); ?>">
    <meta property="og:image" content="<?php echo esc_url($seo['image']); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?php echo esc_attr($seo['title']); ?>">
    <?php if ($seo['description']) : ?><meta name="twitter:description" content="<?php echo esc_attr($seo['description']); ?>"><?php endif; ?>
    <meta name="twitter:image" content="<?php echo esc_url($seo['image']); ?>">
    <script type="application/ld+json"><?php echo wp_json_encode(staticbridge_seo_jsonld($seo), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?></script>
    <?php staticbridge_render_tag_manager_head(); ?>
    <?php
}

function staticbridge_document_asset_url(string $asset): string
{
    $path = get_template_directory() . '/' . ltrim($asset, '/');
    return add_query_arg('ver', file_exists($path) ? (string) filemtime($path) : STATICBRIDGE_THEME_VERSION, get_template_directory_uri() . '/' . ltrim($asset, '/'));
}

function staticbridge_document_view(): string
{
    return (string) get_query_var('staticbridge_view');
}

function staticbridge_render_document_assets(): void
{
    $view = staticbridge_document_view();
    $catalog = 'product-archive' === $view || in_array($view, array('man', 'woman', 'product'), true) || is_post_type_archive('product') || is_tax('product_cat') || (function_exists('is_shop') && is_shop());
    foreach (array('assets/vendor/bootstrap/bootstrap.min.css', 'assets/css/main.css', 'assets/css/video-popup.css') as $asset) {
        printf("<link rel=\"stylesheet\" href=\"%s\">\n", esc_url(staticbridge_document_asset_url($asset)));
    }
    if ($catalog) {
        printf("<link rel=\"stylesheet\" href=\"%s\">\n", esc_url(staticbridge_document_asset_url('assets/css/catalog.css')));
    }
}

function staticbridge_render_document_scripts(): void
{
    $view = staticbridge_document_view();
    $catalog = 'product-archive' === $view || in_array($view, array('man', 'woman', 'product'), true) || is_post_type_archive('product') || is_tax('product_cat') || (function_exists('is_shop') && is_shop());
    $config = array(
        'renderApiVersion' => STATICBRIDGE_RENDER_API_VERSION, 'apiBase' => (string) apply_filters('staticbridge_api_base', 'local' === wp_get_environment_type() ? '/wp-json/' : '/api/'),
        'cartStorageKey' => 'staticbridge_cart_v1', 'stockModeStorageKey' => 'staticbridge_stock_mode_v1',
        'cartUrl' => home_url('/cart/'), 'shopUrl' => home_url('/shop/'), 'whatsappNumber' => staticbridge_whatsapp_number(), 'locale' => staticbridge_requested_locale(), 'languageStorageKey' => 'staticbridge_language_v1',
        'languageCatalogues' => array('sq_AL' => staticbridge_language_messages('sq_AL')), 'messages' => staticbridge_frontend_messages(),
        'staticLabels' => array('Clothing' => __('Clothing', 'dakabrand'), 'Shoes' => __('Shoes', 'dakabrand'), 'Accessories' => __('Accessories', 'dakabrand'), 'Boots' => __('Boots', 'dakabrand'), 'Preorder' => __('Preorder', 'dakabrand'), 'T-Shirts' => __('T-Shirts', 'dakabrand'), 'Jackets' => __('Jackets', 'dakabrand'), 'Jeans' => __('Jeans', 'dakabrand'), 'Outfits & Sets' => __('Outfits & Sets', 'dakabrand'), 'Pants' => __('Pants', 'dakabrand'), 'Dresses' => __('Dresses', 'dakabrand'), 'Shirts' => __('Shirts', 'dakabrand'), 'Swimwear' => __('Swimwear', 'dakabrand'), 'Bikini' => __('Bikini', 'dakabrand'), 'Sweatpants' => __('Sweatpants', 'dakabrand'), 'Body' => __('Body', 'dakabrand'), 'Sneakers' => __('Sneakers', 'dakabrand'), 'Loafers' => __('Loafers', 'dakabrand'), 'Slippers' => __('Slippers', 'dakabrand'), 'Bags' => __('Bags', 'dakabrand'), 'Belts' => __('Belts', 'dakabrand'), 'Watches' => __('Watches', 'dakabrand'), 'Sunglasses' => __('Sunglasses', 'dakabrand'), 'Hats' => __('Hats', 'dakabrand'), 'Scarves' => __('Scarves', 'dakabrand'), 'Jewelry' => __('Jewelry', 'dakabrand'), 'Hoodies' => __('Hoodies', 'dakabrand'), 'T-Shirt' => __('T-Shirt', 'dakabrand'), 'Track Suits' => __('Track Suits', 'dakabrand'), 'Wallets' => __('Wallets', 'dakabrand'), 'Luggage' => __('Luggage', 'dakabrand')),
        'newsletterEndpoint' => (string) apply_filters('staticbridge_newsletter_endpoint', ''),
        'newsletterMessages' => array('unavailable' => __('Newsletter signup is temporarily unavailable. Please try again later.', 'dakabrand'), 'success' => __('Thank you for subscribing.', 'dakabrand'), 'error' => __('We could not complete your signup. Please try again.', 'dakabrand')),
    );
    printf("<script>window.StaticBridgeConfig=%s;</script>\n", wp_json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
    foreach (array('assets/vendor/bootstrap/bootstrap.bundle.min.js', 'assets/js/order-attribution.js', 'assets/js/main.js') as $asset) {
        printf("<script defer src=\"%s\"></script>\n", esc_url(staticbridge_document_asset_url($asset)));
    }
    $video_config = array('videoUrl' => (string) apply_filters('staticbridge_video_popup_url', 'https://dakabrand.uk/wp-content/uploads/2026/09/dakabrand_backtoschool.mp4'), 'storageKey' => 'daka_back_to_school_popup', 'maxShowsPerDay' => 2, 'hoursBetweenShows' => 5, 'showDelay' => 1000, 'dialogLabel' => __('Daka Outlet Back to School', 'dakabrand'), 'closeLabel' => __('Close video popup', 'dakabrand'));
    printf("<script>window.StaticBridgeVideoPopup=%s;</script>\n", wp_json_encode($video_config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
    foreach (array('assets/js/video-popup.js', 'assets/js/cart.js') as $asset) {
        printf("<script defer src=\"%s\"></script>\n", esc_url(staticbridge_document_asset_url($asset)));
    }
    if ($catalog) {
        printf("<script defer src=\"%s\"></script>\n", esc_url(staticbridge_document_asset_url('assets/js/catalog.js')));
    }
    if ('product' === $view || (function_exists('is_product') && is_product())) {
        printf("<script defer src=\"%s\"></script>\n", esc_url(staticbridge_document_asset_url('assets/js/product.js')));
    }
    if ('checkout' === $view || staticbridge_is_checkout_request()) {
        printf("<script defer src=\"%s\"></script>\n", esc_url(staticbridge_document_asset_url('assets/js/checkout.js')));
    }
    if ('contact' === $view || is_page('contact-us')) {
        printf("<script>window.StaticBridgeContact=%s;</script>\n", wp_json_encode(array('endpoint' => (string) apply_filters('staticbridge_contact_endpoint', ''), 'messages' => array('unavailable' => __('Online messages are temporarily unavailable. Please use the contact details on this page.', 'dakabrand'), 'success' => __('Thank you. Your message has been sent.', 'dakabrand'), 'error' => __('We could not send your message. Please try again.', 'dakabrand'))), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
        printf("<script defer src=\"%s\"></script>\n", esc_url(staticbridge_document_asset_url('assets/js/contact.js')));
    }
}

function staticbridge_render_tag_manager_head(): void
{
    $container = staticbridge_google_tag_manager_container();
    if ($container) {
        printf("<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','%s');</script>\n", esc_js($container));
    }
}

function staticbridge_render_tag_manager_body(): void
{
    $container = staticbridge_google_tag_manager_container();
    if ($container) {
        printf('<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=%s" height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>', esc_attr($container));
    }
}
