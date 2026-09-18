<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<main id="main" class="site-main site-shell information-page information-page--policy" data-static-view="policy">
    <?php while (have_posts()) : the_post(); ?>
        <article <?php post_class('information-page__article'); ?>>
            <header class="information-page__header">
                <h1><?php the_title(); ?></h1>
            </header>
            <div class="information-page__content entry-content"><?php the_content(); ?></div>
        </article>
    <?php endwhile; ?>
</main>
