<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<main id="main" class="site-main site-shell" data-static-view="index">
    <?php if (have_posts()) : ?>
        <?php while (have_posts()) : the_post(); ?>
            <article <?php post_class(); ?>>
                <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                <?php the_excerpt(); ?>
            </article>
        <?php endwhile; ?>
        <?php the_posts_pagination(); ?>
    <?php else : ?>
        <p><?php esc_html_e('Nothing found.', 'dakabrand'); ?></p>
    <?php endif; ?>
</main>
