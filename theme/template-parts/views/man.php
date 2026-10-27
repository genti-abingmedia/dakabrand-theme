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

$man_collections = array(
    'new'     => array(__('New In', 'dakabrand'), '/product-category/man/'),
    'deals'   => array(__('Top Deals', 'dakabrand'), '/product-category/big-offer/'),
    'limited' => array(__('Limited Stock', 'dakabrand'), '/product-category/man/?stock_status=instock%3Ainstock'),
);

?>
<main id="main" class="man-landing" data-static-view="man">
    <h1 class="screen-reader-text"><?php esc_html_e('Man', 'dakabrand'); ?></h1>

    <section class="man-hero halloween-hero man-halloween-hero" aria-label="<?php esc_attr_e('Man', 'dakabrand'); ?>">
        <picture class="man-hero__media">
            <source media="(max-width: 767.98px)" srcset="<?php echo esc_url(staticbridge_resize_image_url('https://dakabrand.uk/wp-content/uploads/2026/10/Rectangle-24-1.jpg', 1291, 1828)); ?>" width="1291" height="1828">
            <img src="<?php echo esc_url(staticbridge_resize_image_url('https://dakabrand.uk/wp-content/uploads/2026/10/Rectangle-40-1.jpg', 1600, 685)); ?>" width="2480" height="1062" alt="" fetchpriority="high">
        </picture>
        <div class="halloween-hero__copy">
            <h2 class="halloween-hero__title"><span><?php esc_html_e('Happy', 'dakabrand'); ?></span><span><?php esc_html_e('Halloween', 'dakabrand'); ?></span></h2>
            <p><?php esc_html_e('Explore Our Halloween Drops', 'dakabrand'); ?></p>
        </div>
        <a class="halloween-hero__cta" href="<?php echo esc_url(home_url('/product-category/man/')); ?>"><?php esc_html_e('Shop New Collection', 'dakabrand'); ?></a>
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
                    <img src="<?php echo esc_url(staticbridge_resize_image_url(get_template_directory_uri() . '/assets/images/man-editorial-primary.jpg', 620, 596)); ?>" width="1237" height="1190" alt="" loading="lazy">
                    <span><?php esc_html_e('Preorder Only', 'dakabrand'); ?></span>
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14M14 6l6 6-6 6"/></svg>
                </a>
                <a class="man-editorial" href="<?php echo esc_url(home_url('/product-category/man/')); ?>">
                    <img src="<?php echo esc_url(staticbridge_resize_image_url(get_template_directory_uri() . '/assets/images/man-editorial-secondary.jpg', 620, 596)); ?>" width="1237" height="1190" alt="" loading="lazy">
                    <span><?php esc_html_e('Season Essentials', 'dakabrand'); ?></span>
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 12h14M14 6l6 6-6 6"/></svg>
                </a>
            </section>
        <?php endif; ?>

        <section class="man-collection site-shell" aria-labelledby="man-<?php echo esc_attr($collection_key); ?>-title">
            <h2 id="man-<?php echo esc_attr($collection_key); ?>-title"><?php echo esc_html($collection[0]); ?></h2>
            <div class="man-product-grid product-grid" data-product-grid data-storefront-product-cards data-catalog-source="<?php echo esc_url($collection[1]); ?>" data-catalog-limit="10" aria-busy="true"></div>
            <p class="catalog-rail-status" role="status" data-catalog-rail-status><?php esc_html_e('Loading products…', 'dakabrand'); ?></p>

            <a class="man-collection__cta" href="<?php echo esc_url(home_url($collection[1])); ?>"><?php esc_html_e('View all', 'dakabrand'); ?></a>
        </section>
    <?php endforeach; ?>
</main>
