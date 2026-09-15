<?php
if (!defined('ABSPATH')) {
    exit;
}

$woman_categories = array(
    __('New', 'dakabrand')        => '/product-category/women/',
    __('Puffers', 'dakabrand')    => '/product-category/women/?page=1&limit=20&keyword=Puffer',
    __('Bags', 'dakabrand')       => '/product-category/women/?categories=handbags%3ABags',
    __('Dresses', 'dakabrand')    => '/product-category/women/clothing/?categories=dresses%3ADresses',
    __('Shirts', 'dakabrand')     => '/product-category/women/clothing/?categories=shirts-women%3AShirts',
    __('Jeans', 'dakabrand')      => '/product-category/women/clothing/?categories=jeans-women%3AJeans',
    __('Sets', 'dakabrand')       => '/product-category/women/?page=1&limit=20&keyword=Set',
    __('Coats', 'dakabrand')      => '/product-category/women/?page=1&limit=20&keyword=coat',
    __('Sunglasses', 'dakabrand') => '/product-category/women/accessories/?categories=sunglasses-women%3ASunglasses',
);

/**
 * Build a compact product query for each editorial collection rail.
 */
$woman_product_query = static function (string $collection): WP_Query {
    $args = array(
        'post_type'           => 'product',
        'post_status'         => 'publish',
        'posts_per_page'      => 10,
        'ignore_sticky_posts' => true,
        'tax_query'           => array(
            array(
                'taxonomy'         => 'product_cat',
                'field'            => 'slug',
                'terms'            => array('women'),
                'include_children' => true,
            ),
        ),
    );

    if ('deals' === $collection) {
        $args['meta_query'] = array(
            array(
                'key'     => '_sale_price',
                'value'   => '',
                'compare' => '!=',
            ),
        );
    } elseif ('limited' === $collection) {
        $args['meta_query'] = array(
            array(
                'key'     => '_stock_status',
                'value'   => 'instock',
                'compare' => '=',
            ),
        );
        $args['orderby'] = 'modified';
        $args['order']   = 'DESC';
    }

    return new WP_Query($args);
};

$woman_collections = array(
    'new'     => array(__('New In', 'dakabrand'), '/product-category/women/'),
    'deals'   => array(__('Top Deals', 'dakabrand'), '/product-category/big-offer-women/'),
    'limited' => array(__('Limited Stock', 'dakabrand'), '/product-category/women/?stock_status=instock%3Ainstock'),
);

// Keeps static previews useful when the connected catalog has not been imported.
$woman_fallback_products = array(
    array('Bottega Veneta Bag', 'woman-product-bottega-174.jpg', '/product/bottega-veneta-bag-174/'),
    array('Bottega Veneta Bag', 'woman-product-bottega-173.jpg', '/product/bottega-veneta-bag-173/'),
    array('Bottega Veneta Bag', 'woman-product-bottega-172.jpg', '/product/bottega-veneta-bag-172/'),
    array('Chanel Bag', 'woman-product-chanel-380.jpg', '/product/chanel-bag-380/'),
    array('Bottega Veneta Bag', 'woman-product-bottega-171.jpg', '/product/bottega-veneta-bag-171/'),
);
?>
<main id="main" class="woman-landing man-landing" data-static-view="woman">
    <h1 class="screen-reader-text"><?php esc_html_e('Woman', 'dakabrand'); ?></h1>

    <section class="woman-hero man-hero" aria-labelledby="woman-hero-title">
        <picture class="man-hero__media">
            <source media="(max-width: 767.98px)" srcset="<?php echo esc_url(get_template_directory_uri() . '/assets/images/woman-hero-mobile.jpg'); ?>">
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/woman-hero-desktop.jpg'); ?>" width="2480" height="1062" alt="" fetchpriority="high">
        </picture>
        <div class="man-hero__content">
            <p id="woman-hero-title"><?php esc_html_e('Woman', 'dakabrand'); ?></p>
            <a href="<?php echo esc_url(home_url('/product-category/women/')); ?>"><?php esc_html_e('Shop new collection', 'dakabrand'); ?></a>
        </div>
    </section>

    <nav class="woman-category-nav man-category-nav site-shell" aria-label="<?php esc_attr_e('Shop women by category', 'dakabrand'); ?>">
        <?php foreach ($woman_categories as $label => $path) : ?>
            <a href="<?php echo esc_url(home_url($path)); ?>"><?php echo esc_html($label); ?></a>
        <?php endforeach; ?>
    </nav>

    <?php foreach ($woman_collections as $collection_key => $collection) : ?>
        <?php if ('deals' === $collection_key) : ?>
            <section class="woman-editorials man-editorials" aria-label="<?php esc_attr_e('Featured women collections', 'dakabrand'); ?>">
                <a class="woman-editorial man-editorial man-editorial--preorder" href="<?php echo esc_url(home_url('/product-category/women/?stock_status=onbackorder%3Aonbackorder')); ?>">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/woman-editorial-primary.jpg'); ?>" width="1237" height="1190" alt="" loading="lazy">
                    <span><?php esc_html_e('Preorder Only', 'dakabrand'); ?></span>
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14M14 6l6 6-6 6"/></svg>
                </a>
                <a class="woman-editorial man-editorial" href="<?php echo esc_url(home_url('/product-category/women/')); ?>">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/woman-editorial-secondary.jpg'); ?>" width="1237" height="1189" alt="" loading="lazy">
                    <span><?php esc_html_e('Season Essentials', 'dakabrand'); ?></span>
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14M14 6l6 6-6 6"/></svg>
                </a>
            </section>
        <?php endif; ?>

        <?php $products = $woman_product_query($collection_key); ?>
        <section class="woman-collection man-collection site-shell" aria-labelledby="woman-<?php echo esc_attr($collection_key); ?>-title">
            <h2 id="woman-<?php echo esc_attr($collection_key); ?>-title"><?php echo esc_html($collection[0]); ?></h2>

            <?php if ($products->have_posts()) : ?>
                <div class="woman-product-grid man-product-grid product-grid" data-product-grid>
                    <?php while ($products->have_posts()) : $products->the_post(); ?>
                        <?php get_template_part('template-parts/components/product-card'); ?>
                    <?php endwhile; ?>
                </div>
            <?php else : ?>
                <div class="woman-product-grid man-product-grid product-grid" data-product-grid data-catalog-fallback>
                    <?php foreach ($woman_fallback_products as $fallback_product) : ?>
                        <article class="product-card man-fallback-product woman-fallback-product">
                            <a class="product-card__link" href="<?php echo esc_url(home_url($fallback_product[2])); ?>">
                                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/' . $fallback_product[1]); ?>" width="300" height="300" alt="<?php echo esc_attr($fallback_product[0]); ?>" loading="lazy">
                                <p><?php esc_html_e('Accessories', 'dakabrand'); ?></p>
                                <h2><?php echo esc_html($fallback_product[0]); ?></h2>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <a class="woman-collection__cta man-collection__cta" href="<?php echo esc_url(home_url($collection[1])); ?>"><?php esc_html_e('View all', 'dakabrand'); ?></a>
        </section>
        <?php wp_reset_postdata(); ?>
    <?php endforeach; ?>
</main>
