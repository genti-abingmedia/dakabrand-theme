<?php
if (!defined('ABSPATH')) {
    exit;
}

$home_sections = array(
    array(
        'key'       => 'woman',
        'label'     => __('Woman', 'dakabrand'),
        'image'     => 'home-woman.jpg',
        'landing'   => staticbridge_department_page_url('woman'),
        'category'  => '/product-category/women/',
        'preorder'  => '/product-category/women/?stock_status=onbackorder%3Aonbackorder',
        'in_stock'  => '/product-category/women/?stock_status=instock%3Ainstock%2Coutofstock%3Aoutofstock',
        'offer'     => '/product-category/women/big-offer-women/?stock_status=instock%3Ainstock%2Coutofstock%3Aoutofstock',
    ),
    array(
        'key'       => 'man',
        'label'     => __('Man', 'dakabrand'),
        'image'     => 'home-man.jpg',
        'landing'   => staticbridge_department_page_url('man'),
        'category'  => '/product-category/man/',
        'preorder'  => '/product-category/man/?stock_status=onbackorder%3Aonbackorder',
        'in_stock'  => '/product-category/man/?stock_status=instock%3Ainstock%2Coutofstock%3Aoutofstock',
        'offer'     => '/product-category/man/big-offer/?stock_status=instock%3Ainstock%2Coutofstock%3Aoutofstock',
    ),
);
?>
<main id="main" class="home-gateway" data-static-view="front-page">
    <h1 class="screen-reader-text"><?php echo esc_html(get_bloginfo('name')); ?></h1>
    <?php echo staticbridge_language_switcher('language-switcher--gateway'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    <nav class="home-gateway__mobile-switcher" aria-label="<?php esc_attr_e('Choose a collection', 'dakabrand'); ?>" data-gateway-switcher>
        <a href="#home-woman" aria-current="true"><?php esc_html_e('Woman', 'dakabrand'); ?></a>
        <a href="#home-man"><?php esc_html_e('Man', 'dakabrand'); ?></a>
    </nav>

    <?php foreach ($home_sections as $section) : ?>
        <section id="home-<?php echo esc_attr($section['key']); ?>" class="home-gateway__panel home-gateway__panel--<?php echo esc_attr($section['key']); ?>" aria-labelledby="home-<?php echo esc_attr($section['key']); ?>-title" data-gateway-panel>
            <a class="home-gateway__image-link" href="<?php echo esc_url($section['landing']); ?>" aria-label="<?php echo esc_attr(sprintf(__('Shop %s', 'dakabrand'), $section['label'])); ?>">
                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/' . $section['image']); ?>" alt="" width="1163" height="1236" fetchpriority="high">
            </a>
            <h2 class="screen-reader-text" id="home-<?php echo esc_attr($section['key']); ?>-title"><?php echo esc_html($section['label']); ?></h2>

            <nav class="home-gateway__actions" aria-label="<?php echo esc_attr(sprintf(__('%s collections', 'dakabrand'), $section['label'])); ?>">
                <a class="home-gateway__button home-gateway__button--dark" href="<?php echo esc_url(home_url($section['preorder'])); ?>"><?php esc_html_e('Preorder Only', 'dakabrand'); ?></a>
                <a class="home-gateway__button" href="<?php echo esc_url(home_url($section['in_stock'])); ?>"><?php esc_html_e('Shop New Collection', 'dakabrand'); ?></a>
                <a class="home-gateway__button home-gateway__button--offer" href="<?php echo esc_url(home_url($section['offer'])); ?>"><?php esc_html_e('Big Offer', 'dakabrand'); ?></a>
            </nav>
        </section>
    <?php endforeach; ?>
</main>
