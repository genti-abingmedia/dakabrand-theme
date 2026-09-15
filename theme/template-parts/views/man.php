<?php
if (!defined('ABSPATH')) {
    exit;
}

$man_categories = array(
    __('New', 'dakabrand')      => '/product-category/man/',
    __('Puffers', 'dakabrand')  => '/product-category/man/clothing-man/?categories=jackets-man%3AJackets&page=1&limit=20&keyword=Puffer',
    __('Sneakers', 'dakabrand') => '/product-category/man/?categories=shoes-man%3ASHOES',
    __('Hoodies', 'dakabrand')  => '/product-category/man/clothing-man/?categories=hoodies-man%3AHoodies',
    __('Shirts', 'dakabrand')   => '/product-category/man/clothing-man/?categories=shirts-man%3AShirts',
    __('Jeans', 'dakabrand')    => '/product-category/man/clothing-man/?categories=jeans-man%3AJeans',
    __('Sets', 'dakabrand')     => '/product-category/man/?page=1&limit=20&keyword=set',
    __('Jackets', 'dakabrand')  => '/product-category/man/clothing-man/?categories=jackets-man%3AJackets',
    __('Watches', 'dakabrand')  => '/product-category/man/accessories-man/?categories=watches-man%3AWatches',
);

/**
 * Build a compact product query for each editorial collection rail.
 */
$man_product_query = static function (string $collection): WP_Query {
    $args = array(
        'post_type'           => 'product',
        'post_status'         => 'publish',
        'posts_per_page'      => 10,
        'ignore_sticky_posts' => true,
        'tax_query'           => array(
            array(
                'taxonomy'         => 'product_cat',
                'field'            => 'slug',
                'terms'            => array('man'),
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

$man_collections = array(
    'new'     => array(__('New In', 'dakabrand'), '/product-category/man/'),
    'deals'   => array(__('Top Deals', 'dakabrand'), '/product-category/big-offer/'),
    'limited' => array(__('Limited Stock', 'dakabrand'), '/product-category/man/?stock_status=instock%3Ainstock'),
);

// Keeps static previews useful when the connected catalog has not been imported.
$man_fallback_products = array(
    array('Hermes Bag', 'man-product-hermes-277.jpg', '/product/hermes-bag-277/'),
    array('Hermes Bag', 'man-product-hermes-199.jpg', '/product/hermes-bag-199/'),
    array('Hermes Bag', 'man-product-hermes-53.jpg', '/product/hermes-bag-53/'),
    array('Hermes Bag', 'man-product-hermes-190.jpg', '/product/hermes-bag-190/'),
    array('LV Bag', 'man-product-lv-550.jpg', '/product/lv-bag-550/'),
);
?>
<main id="main" class="man-landing" data-static-view="man">
    <h1 class="screen-reader-text"><?php esc_html_e('Man', 'dakabrand'); ?></h1>

    <section class="man-hero" aria-labelledby="man-hero-title">
        <picture class="man-hero__media">
            <source media="(max-width: 767.98px)" srcset="<?php echo esc_url(get_template_directory_uri() . '/assets/images/man-hero-mobile.jpg'); ?>">
            <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/man-hero-desktop.jpg'); ?>" width="2480" height="1062" alt="" fetchpriority="high">
        </picture>
        <div class="man-hero__content">
            <p id="man-hero-title"><?php esc_html_e('Man', 'dakabrand'); ?></p>
            <a href="<?php echo esc_url(home_url('/product-category/man/')); ?>"><?php esc_html_e('Shop new collection', 'dakabrand'); ?></a>
        </div>
    </section>

    <nav class="man-category-nav site-shell" aria-label="<?php esc_attr_e('Shop men by category', 'dakabrand'); ?>">
        <?php foreach ($man_categories as $label => $path) : ?>
            <a href="<?php echo esc_url(home_url($path)); ?>"><?php echo esc_html($label); ?></a>
        <?php endforeach; ?>
    </nav>

    <?php foreach ($man_collections as $collection_key => $collection) : ?>
        <?php if ('deals' === $collection_key) : ?>
            <section class="man-editorials" aria-label="<?php esc_attr_e('Featured men collections', 'dakabrand'); ?>">
                <a class="man-editorial man-editorial--preorder" href="<?php echo esc_url(home_url('/product-category/man/?stock_status=onbackorder%3Aonbackorder')); ?>">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/man-editorial-primary.jpg'); ?>" width="1237" height="1190" alt="" loading="lazy">
                    <span><?php esc_html_e('Preorder Only', 'dakabrand'); ?></span>
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14M14 6l6 6-6 6"/></svg>
                </a>
                <a class="man-editorial" href="<?php echo esc_url(home_url('/product-category/man/')); ?>">
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/man-editorial-secondary.jpg'); ?>" width="1237" height="1190" alt="" loading="lazy">
                    <span><?php esc_html_e('Season Essentials', 'dakabrand'); ?></span>
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14M14 6l6 6-6 6"/></svg>
                </a>
            </section>
        <?php endif; ?>

        <?php $products = $man_product_query($collection_key); ?>
        <section class="man-collection site-shell" aria-labelledby="man-<?php echo esc_attr($collection_key); ?>-title">
            <h2 id="man-<?php echo esc_attr($collection_key); ?>-title"><?php echo esc_html($collection[0]); ?></h2>

            <?php if ($products->have_posts()) : ?>
                <div class="man-product-grid product-grid" data-product-grid>
                    <?php while ($products->have_posts()) : $products->the_post(); ?>
                        <?php get_template_part('template-parts/components/product-card'); ?>
                    <?php endwhile; ?>
                </div>
            <?php else : ?>
                <div class="man-product-grid product-grid" data-product-grid data-catalog-fallback>
                    <?php foreach ($man_fallback_products as $fallback_product) : ?>
                        <article class="product-card man-fallback-product">
                            <a class="product-card__link" href="<?php echo esc_url(home_url($fallback_product[2])); ?>">
                                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/' . $fallback_product[1]); ?>" width="300" height="300" alt="<?php echo esc_attr($fallback_product[0]); ?>" loading="lazy">
                                <p><?php esc_html_e('Accessories', 'dakabrand'); ?></p>
                                <h2><?php echo esc_html($fallback_product[0]); ?></h2>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <a class="man-collection__cta" href="<?php echo esc_url(home_url($collection[1])); ?>"><?php esc_html_e('View all', 'dakabrand'); ?></a>
        </section>
        <?php wp_reset_postdata(); ?>
    <?php endforeach; ?>
</main>
