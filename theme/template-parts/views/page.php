<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<main id="main" class="site-main site-shell" data-static-view="page">
    <?php while (have_posts()) : the_post(); ?>
        <article <?php post_class(); ?>>
            <h1><?php the_title(); ?></h1>
            <div class="entry-content"><?php the_content(); ?></div>
        </article>
    <?php endwhile; ?>
</main>

