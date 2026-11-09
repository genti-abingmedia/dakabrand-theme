<?php
if (!defined('ABSPATH')) {
    exit;
}

?>
<main id="main" class="black-friday-home" data-static-view="front-page">
    <?php echo staticbridge_language_switcher('language-switcher--gateway'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <picture class="black-friday-home__background">
        <source media="(max-width: 991.98px)" srcset="<?php echo esc_url(get_template_directory_uri() . '/assets/images/black-friday-clothing-mobile.png'); ?>" width="941" height="1672">
        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/black-friday-clothing-desktop.png'); ?>" alt="" width="1672" height="941" fetchpriority="high">
    </picture>
    <div class="black-friday-home__content">
        <p class="black-friday-home__brand"><?php echo esc_html(get_bloginfo('name')); ?></p>
        <h1><?php esc_html_e('Black Friday', 'dakabrand'); ?></h1>
        <p class="black-friday-home__intro"><?php esc_html_e('Discover the Black Friday collection.', 'dakabrand'); ?></p>
        <nav class="black-friday-home__actions" aria-label="<?php esc_attr_e('Choose a collection', 'dakabrand'); ?>">
            <a class="black-friday-button black-friday-button--primary" href="<?php echo esc_url(home_url('/product-category/black-friday/')); ?>"><?php esc_html_e('Shop Black Friday', 'dakabrand'); ?></a>
            <a class="black-friday-button" href="<?php echo esc_url(staticbridge_department_page_url('man')); ?>"><?php esc_html_e('Man', 'dakabrand'); ?></a>
            <a class="black-friday-button" href="<?php echo esc_url(staticbridge_department_page_url('woman')); ?>"><?php esc_html_e('Woman', 'dakabrand'); ?></a>
        </nav>
    </div>
</main>
