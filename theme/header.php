<?php
if (!defined('ABSPATH')) {
    exit;
}
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#main"><?php esc_html_e('Skip to content', 'dakabrand'); ?></a>

<div class="announcement-bar" aria-label="<?php esc_attr_e('DakaBrand apps', 'dakabrand'); ?>">
    <div class="announcement-bar__track">
        <a href="https://play.google.com/store/apps/details?id=uk.dakabrand" target="_blank" rel="noopener noreferrer">Daka Brand <span aria-hidden="true">&#8594;</span> <strong>Google Play</strong></a>
        <a href="https://apps.apple.com/us/app/daka-brand/id6467129499" target="_blank" rel="noopener noreferrer">Daka Brand <span aria-hidden="true">&#8594;</span> <strong>App Store</strong></a>
        <a href="https://play.google.com/store/apps/details?id=uk.dakabrand" target="_blank" rel="noopener noreferrer" aria-hidden="true" tabindex="-1">Daka Brand <span aria-hidden="true">&#8594;</span> <strong>Google Play</strong></a>
        <a href="https://apps.apple.com/us/app/daka-brand/id6467129499" target="_blank" rel="noopener noreferrer" aria-hidden="true" tabindex="-1">Daka Brand <span aria-hidden="true">&#8594;</span> <strong>App Store</strong></a>
    </div>
</div>

<header class="site-header" data-component="site-header">
    <div class="site-promo d-none d-lg-block">
        <div class="site-shell site-promo__inner">
            <a href="https://www.instagram.com/daka__man" target="_blank" rel="noopener noreferrer" class="site-promo__social">
                <span aria-hidden="true">◎</span> <?php esc_html_e('100k Followers', 'dakabrand'); ?>
            </a>
            <p><?php esc_html_e('Open the doors to a world of fashion', 'dakabrand'); ?> <span aria-hidden="true"></span> <a href="<?php echo esc_url(home_url('/shop/')); ?>"><?php esc_html_e('Discover more', 'dakabrand'); ?></a></p>
        </div>
    </div>

    <div class="site-header__main">
        <div class="site-shell site-header__inner">
            <button class="header-action header-menu-toggle d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobile-navigation" aria-controls="mobile-navigation" aria-label="<?php esc_attr_e('Open navigation', 'dakabrand'); ?>">
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
            </button>

            <nav class="site-navigation d-none d-lg-block" aria-label="<?php esc_attr_e('Primary navigation', 'dakabrand'); ?>">
                <?php
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'container'      => false,
                    'fallback_cb'    => 'staticbridge_primary_menu_fallback',
                    'menu_class'     => 'site-navigation__menu',
                    'depth'          => 3,
                ));
                ?>
            </nav>

            <div class="site-branding">
                <?php if (has_custom_logo()) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <a class="site-brand" href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/dakabrand-logo.png'); ?>" width="86" height="42" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
                    </a>
                <?php endif; ?>
            </div>

            <div class="site-header__actions">
                <a class="header-action" href="<?php echo esc_url(home_url('/my-account/')); ?>" aria-label="<?php esc_attr_e('My account', 'dakabrand'); ?>">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                </a>
                <a class="header-action cart-link" href="<?php echo esc_url(home_url('/cart/')); ?>" aria-label="<?php esc_attr_e('Cart', 'dakabrand'); ?>" data-cart-link>
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 8h14l-1 13H6L5 8Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg>
                    <span class="cart-count" data-cart-count aria-live="polite">0</span>
                </a>
            </div>
        </div>
    </div>
</header>

<div class="offcanvas offcanvas-start mobile-navigation" tabindex="-1" id="mobile-navigation" aria-labelledby="mobile-navigation-title">
    <div class="offcanvas-header">
        <h2 class="offcanvas-title" id="mobile-navigation-title"><?php esc_html_e('Menu', 'dakabrand'); ?></h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="<?php esc_attr_e('Close navigation', 'dakabrand'); ?>"></button>
    </div>
    <div class="offcanvas-body">
        <?php
        wp_nav_menu(array(
            'theme_location' => 'primary',
            'container'      => 'nav',
            'container_aria_label' => __('Mobile navigation', 'dakabrand'),
            'fallback_cb'    => 'staticbridge_primary_menu_fallback',
            'menu_class'     => 'mobile-navigation__menu',
            'depth'          => 3,
        ));
        ?>
        <a class="mobile-navigation__account" href="<?php echo esc_url(home_url('/my-account/')); ?>"><?php esc_html_e('My account', 'dakabrand'); ?></a>
    </div>
</div>
