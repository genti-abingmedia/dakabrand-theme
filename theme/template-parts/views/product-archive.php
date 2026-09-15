<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<main id="main" class="site-main site-shell" data-static-view="product-archive">
    <header class="page-header">
        <h1><?php woocommerce_page_title(); ?></h1>
        <?php do_action('woocommerce_archive_description'); ?>
    </header>

    <?php if (woocommerce_product_loop()) : ?>
        <div class="product-grid" data-product-grid>
            <?php while (have_posts()) : the_post(); ?>
                <?php get_template_part('template-parts/components/product-card'); ?>
            <?php endwhile; ?>
        </div>

        <?php the_posts_pagination(); ?>
    <?php else : ?>
        <p><?php esc_html_e('No products found.', 'dakabrand'); ?></p>
    <?php endif; ?>
</main>
