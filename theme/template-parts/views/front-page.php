<?php
if (!defined('ABSPATH')) {
    exit;
}

$products = new WP_Query(array(
    'post_type'      => 'product',
    'post_status'    => 'publish',
    'posts_per_page' => 12,
));
?>
<main id="main" class="site-main site-shell" data-static-view="front-page">
    <header class="page-header">
        <h1><?php echo esc_html(get_bloginfo('name')); ?></h1>
        <?php if (get_bloginfo('description')) : ?>
            <p><?php echo esc_html(get_bloginfo('description')); ?></p>
        <?php endif; ?>
    </header>

    <section aria-labelledby="latest-products-heading">
        <h2 id="latest-products-heading"><?php esc_html_e('Latest products', 'dakabrand'); ?></h2>
        <div class="product-grid" data-product-grid>
            <?php while ($products->have_posts()) : $products->the_post(); ?>
                <?php get_template_part('template-parts/components/product-card'); ?>
            <?php endwhile; ?>
        </div>
    </section>
</main>
<?php wp_reset_postdata(); ?>
