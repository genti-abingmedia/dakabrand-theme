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
<?php if (!staticbridge_is_immersive_front_page()) : ?>
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

            <div class="site-branding">
                <?php if (has_custom_logo()) : ?>
                    <?php the_custom_logo(); ?>
                <?php else : ?>
                    <a class="site-brand" href="<?php echo esc_url(home_url('/')); ?>" rel="home">
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/dakabrand-logo.png'); ?>" width="86" height="42" alt="<?php echo esc_attr(get_bloginfo('name')); ?>">
                    </a>
                <?php endif; ?>
            </div>

            <?php $active_fashion_department = staticbridge_is_department_context('man') ? 'men' : 'women'; ?>
            <nav class="fashion-navigation d-none d-lg-block" aria-label="<?php esc_attr_e('Shop by department', 'dakabrand'); ?>" data-fashion-navigation>
                <div class="fashion-navigation__departments" role="tablist" aria-label="<?php esc_attr_e('Departments', 'dakabrand'); ?>">
                    <a class="fashion-navigation__department<?php echo 'women' === $active_fashion_department ? ' is-current' : ''; ?>" href="<?php echo esc_url(staticbridge_department_page_url('woman')); ?>" role="tab" aria-selected="<?php echo 'women' === $active_fashion_department ? 'true' : 'false'; ?>" data-fashion-department="women"><?php esc_html_e('Women', 'dakabrand'); ?></a>
                    <a class="fashion-navigation__department<?php echo 'men' === $active_fashion_department ? ' is-current' : ''; ?>" href="<?php echo esc_url(staticbridge_department_page_url('man')); ?>" role="tab" aria-selected="<?php echo 'men' === $active_fashion_department ? 'true' : 'false'; ?>" data-fashion-department="men"><?php esc_html_e('Man', 'dakabrand'); ?></a>
                </div>

                <div class="fashion-navigation__collections<?php echo 'women' === $active_fashion_department ? ' is-current' : ''; ?>" data-fashion-collection="women"<?php echo 'women' !== $active_fashion_department ? ' hidden' : ''; ?>>
                    <div class="fashion-navigation__links">
                        <a class="has-mega-menu" href="<?php echo esc_url(home_url('/product-category/women/new-collection-women/')); ?>" data-mega-trigger="women-new-in"><?php esc_html_e('New In', 'dakabrand'); ?></a>
                        <a class="has-mega-menu" href="<?php echo esc_url(home_url('/product-category/women/clothing/')); ?>" data-mega-trigger="women-clothing"><?php esc_html_e('Clothing', 'dakabrand'); ?></a>
                        <a class="has-mega-menu" href="<?php echo esc_url(home_url('/product-category/women/shoes/')); ?>" data-mega-trigger="women-shoes"><?php esc_html_e('Shoes', 'dakabrand'); ?></a>
                        <a class="has-mega-menu" href="<?php echo esc_url(home_url('/product-category/women/accessories/')); ?>" data-mega-trigger="women-accessories"><?php esc_html_e('Accessories', 'dakabrand'); ?></a>
                        <a class="has-mega-menu" href="<?php echo esc_url(home_url('/shop/?stock_status=onbackorder%3Aonbackorder')); ?>" data-mega-trigger="women-preorder"><?php esc_html_e('Preorder', 'dakabrand'); ?></a>
                        <a href="<?php echo esc_url(home_url('/shop/?sale=1')); ?>"><?php esc_html_e('Big Offer', 'dakabrand'); ?></a>
                    </div>
                    <div class="fashion-mega-menu" data-mega-panel="women-new-in" aria-label="<?php esc_attr_e('Women new arrivals', 'dakabrand'); ?>">
                        <div class="fashion-mega-menu__groups"><div><strong><?php esc_html_e('Shop by category', 'dakabrand'); ?></strong><a href="<?php echo esc_url(home_url('/product-category/women/clothing/')); ?>">Clothing</a><a href="<?php echo esc_url(home_url('/product-category/women/shoes/')); ?>">Shoes</a><a href="<?php echo esc_url(home_url('/product-category/women/accessories/')); ?>">Accessories</a><a href="<?php echo esc_url(home_url('/product-category/women/shoes/boots/')); ?>">Boots</a><a href="<?php echo esc_url(home_url('/shop/?stock_status=onbackorder%3Aonbackorder')); ?>">Preorder</a></div></div>
                        <a class="fashion-mega-menu__feature" href="<?php echo esc_url(home_url('/product-category/women/new-collection-women/')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/woman-editorial-primary.jpg'); ?>" alt="<?php esc_attr_e('New women’s arrivals', 'dakabrand'); ?>"><span><?php esc_html_e('View all', 'dakabrand'); ?></span></a>
                    </div>
                    <div class="fashion-mega-menu" data-mega-panel="women-clothing" aria-label="<?php esc_attr_e('Women clothing categories', 'dakabrand'); ?>">
                        <div class="fashion-mega-menu__groups">
                            <div><strong><?php esc_html_e('Shop by category', 'dakabrand'); ?></strong><a href="<?php echo esc_url(home_url('/product-category/women/clothing/t-shirt/')); ?>">T-Shirts</a><a href="<?php echo esc_url(home_url('/product-category/women/clothing/jackets/')); ?>">Jackets</a><a href="<?php echo esc_url(home_url('/product-category/women/clothing/jeans/')); ?>">Jeans</a><a href="<?php echo esc_url(home_url('/product-category/women/clothing/outfits-sets/')); ?>">Outfits &amp; Sets</a><a href="<?php echo esc_url(home_url('/product-category/women/clothing/pants/')); ?>">Pants</a><a href="<?php echo esc_url(home_url('/product-category/women/clothing/dresses/')); ?>">Dresses</a></div>
                            <div class="fashion-mega-menu__offset"><a href="<?php echo esc_url(home_url('/product-category/women/clothing/shirts/')); ?>">Shirts</a><a href="<?php echo esc_url(home_url('/product-category/women/clothing/swimwear/')); ?>">Swimwear</a><a href="<?php echo esc_url(home_url('/product-category/women/clothing/bikini/')); ?>">Bikini</a><a href="<?php echo esc_url(home_url('/product-category/women/clothing/sweatpants/')); ?>">Sweatpants</a><a href="<?php echo esc_url(home_url('/product-category/women/clothing/body/')); ?>">Body</a></div>
                        </div>
                        <a class="fashion-mega-menu__feature" href="<?php echo esc_url(home_url('/product-category/women/clothing/')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/woman-product-bottega-174.jpg'); ?>" alt="<?php esc_attr_e('Women’s clothing', 'dakabrand'); ?>"><span><?php esc_html_e('View all', 'dakabrand'); ?></span></a>
                    </div>
                    <div class="fashion-mega-menu" data-mega-panel="women-shoes" aria-label="<?php esc_attr_e('Women shoe categories', 'dakabrand'); ?>">
                        <div class="fashion-mega-menu__groups"><div><strong><?php esc_html_e('Shop by category', 'dakabrand'); ?></strong><a href="<?php echo esc_url(home_url('/product-category/women/shoes/sneakers/')); ?>">Sneakers</a><a href="<?php echo esc_url(home_url('/product-category/women/shoes/loafers/')); ?>">Loafers</a><a href="<?php echo esc_url(home_url('/product-category/women/shoes/slippers/')); ?>">Slippers</a><a href="<?php echo esc_url(home_url('/product-category/women/shoes/boots/')); ?>">Boots</a></div></div>
                        <a class="fashion-mega-menu__feature" href="<?php echo esc_url(home_url('/product-category/women/shoes/')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/woman-product-bottega-173.jpg'); ?>" alt="<?php esc_attr_e('Women’s shoes', 'dakabrand'); ?>"><span><?php esc_html_e('View all', 'dakabrand'); ?></span></a>
                    </div>
                    <div class="fashion-mega-menu" data-mega-panel="women-accessories" aria-label="<?php esc_attr_e('Women accessory categories', 'dakabrand'); ?>">
                        <div class="fashion-mega-menu__groups"><div><strong><?php esc_html_e('Shop by category', 'dakabrand'); ?></strong><a href="<?php echo esc_url(home_url('/product-category/women/accessories/bags/')); ?>">Bags</a><a href="<?php echo esc_url(home_url('/product-category/women/accessories/belts/')); ?>">Belts</a><a href="<?php echo esc_url(home_url('/product-category/watches/')); ?>">Watches</a><a href="<?php echo esc_url(home_url('/product-category/women/accessories/sunglasses/')); ?>">Sunglasses</a><a href="<?php echo esc_url(home_url('/product-category/women/accessories/hats/')); ?>">Hats</a><a href="<?php echo esc_url(home_url('/product-category/women/accessories/scarves/')); ?>">Scarves</a><a href="<?php echo esc_url(home_url('/product-category/women/accessories/jewelry/')); ?>">Jewelry</a></div></div>
                        <a class="fashion-mega-menu__feature" href="<?php echo esc_url(home_url('/product-category/women/accessories/')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/woman-product-chanel-380.jpg'); ?>" alt="<?php esc_attr_e('Women’s accessories', 'dakabrand'); ?>"><span><?php esc_html_e('View all', 'dakabrand'); ?></span></a>
                    </div>
                    <div class="fashion-mega-menu" data-mega-panel="women-preorder" aria-label="<?php esc_attr_e('Women preorder categories', 'dakabrand'); ?>">
                        <div class="fashion-mega-menu__groups"><div><strong><?php esc_html_e('Shop by category', 'dakabrand'); ?></strong><a href="<?php echo esc_url(home_url('/product-category/women/clothing/')); ?>">Clothing</a><a href="<?php echo esc_url(home_url('/product-category/women/shoes/')); ?>">Shoes</a><a href="<?php echo esc_url(home_url('/product-category/women/accessories/bags/')); ?>">Bags</a><a href="<?php echo esc_url(home_url('/product-category/women/accessories/belts/')); ?>">Belts</a></div></div>
                        <a class="fashion-mega-menu__feature" href="<?php echo esc_url(home_url('/shop/?stock_status=onbackorder%3Aonbackorder')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/woman-product-bottega-171.jpg'); ?>" alt="<?php esc_attr_e('Women’s preorder collection', 'dakabrand'); ?>"><span><?php esc_html_e('View all', 'dakabrand'); ?></span></a>
                    </div>
                </div>

                <div class="fashion-navigation__collections<?php echo 'men' === $active_fashion_department ? ' is-current' : ''; ?>" data-fashion-collection="men"<?php echo 'men' !== $active_fashion_department ? ' hidden' : ''; ?>>
                    <div class="fashion-navigation__links">
                        <a class="has-mega-menu" href="<?php echo esc_url(home_url('/product-category/man/new-collection-man/')); ?>" data-mega-trigger="men-new-in"><?php esc_html_e('New In', 'dakabrand'); ?></a>
                        <a class="has-mega-menu" href="<?php echo esc_url(home_url('/product-category/man/clothing/')); ?>" data-mega-trigger="men-clothing"><?php esc_html_e('Clothing', 'dakabrand'); ?></a>
                        <a class="has-mega-menu" href="<?php echo esc_url(home_url('/product-category/man/shoes/')); ?>" data-mega-trigger="men-shoes"><?php esc_html_e('Shoes', 'dakabrand'); ?></a>
                        <a class="has-mega-menu" href="<?php echo esc_url(home_url('/product-category/man/accessories/')); ?>" data-mega-trigger="men-accessories"><?php esc_html_e('Accessories', 'dakabrand'); ?></a>
                        <a class="has-mega-menu" href="<?php echo esc_url(home_url('/shop/?stock_status=onbackorder%3Aonbackorder')); ?>" data-mega-trigger="men-preorder"><?php esc_html_e('Preorder', 'dakabrand'); ?></a>
                        <a href="<?php echo esc_url(home_url('/shop/?sale=1')); ?>"><?php esc_html_e('Big Offer', 'dakabrand'); ?></a>
                    </div>
                    <div class="fashion-mega-menu" data-mega-panel="men-new-in" aria-label="<?php esc_attr_e('Men new arrivals', 'dakabrand'); ?>">
                        <div class="fashion-mega-menu__groups"><div><strong><?php esc_html_e('Shop by category', 'dakabrand'); ?></strong><a href="<?php echo esc_url(home_url('/product-category/man/clothing/')); ?>">Clothing</a><a href="<?php echo esc_url(home_url('/product-category/man/shoes/')); ?>">Shoes</a><a href="<?php echo esc_url(home_url('/product-category/man/accessories/')); ?>">Accessories</a><a href="<?php echo esc_url(home_url('/shop/?stock_status=onbackorder%3Aonbackorder')); ?>">Preorder</a></div></div>
                        <a class="fashion-mega-menu__feature" href="<?php echo esc_url(home_url('/product-category/man/new-collection-man/')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/man-editorial-primary.jpg'); ?>" alt="<?php esc_attr_e('New men’s arrivals', 'dakabrand'); ?>"><span><?php esc_html_e('View all', 'dakabrand'); ?></span></a>
                    </div>
                    <div class="fashion-mega-menu" data-mega-panel="men-clothing" aria-label="<?php esc_attr_e('Men clothing categories', 'dakabrand'); ?>">
                        <div class="fashion-mega-menu__groups">
                            <div><strong><?php esc_html_e('Shop by category', 'dakabrand'); ?></strong><a href="<?php echo esc_url(home_url('/product-category/man/clothing/hoodies/')); ?>">Hoodies</a><a href="<?php echo esc_url(home_url('/product-category/man/clothing/jackets/')); ?>">Jackets</a><a href="<?php echo esc_url(home_url('/product-category/man/clothing/jeans/')); ?>">Jeans</a><a href="<?php echo esc_url(home_url('/product-category/man/clothing/shirts/')); ?>">Shirts</a><a href="<?php echo esc_url(home_url('/product-category/man/clothing/pants/')); ?>">Pants</a></div>
                            <div class="fashion-mega-menu__offset"><a href="<?php echo esc_url(home_url('/product-category/man/clothing/shirts/')); ?>">Shirts</a><a href="<?php echo esc_url(home_url('/product-category/man/clothing/t-shirts/')); ?>">T-Shirt</a><a href="<?php echo esc_url(home_url('/product-category/man/clothing/swimwear/')); ?>">Swimwear</a><a href="<?php echo esc_url(home_url('/product-category/man/clothing/sweatpants/')); ?>">Sweatpants</a><a href="<?php echo esc_url(home_url('/product-category/man/clothing/tracksuits/')); ?>">Track Suits</a></div>
                        </div>
                        <a class="fashion-mega-menu__feature" href="<?php echo esc_url(home_url('/product-category/man/clothing/')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/man-product-lv-550.jpg'); ?>" alt="<?php esc_attr_e('Men’s clothing', 'dakabrand'); ?>"><span><?php esc_html_e('View all', 'dakabrand'); ?></span></a>
                    </div>
                    <div class="fashion-mega-menu" data-mega-panel="men-shoes" aria-label="<?php esc_attr_e('Men shoe categories', 'dakabrand'); ?>">
                        <div class="fashion-mega-menu__groups"><div><strong><?php esc_html_e('Shop by category', 'dakabrand'); ?></strong><a href="<?php echo esc_url(home_url('/product-category/man/shoes/sneakers/')); ?>">Sneakers</a><a href="<?php echo esc_url(home_url('/product-category/man/shoes/loafers/')); ?>">Loafers</a><a href="<?php echo esc_url(home_url('/product-category/man/shoes/slippers/')); ?>">Slippers</a></div></div>
                        <a class="fashion-mega-menu__feature" href="<?php echo esc_url(home_url('/product-category/man/shoes/')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/man-product-hermes-53.jpg'); ?>" alt="<?php esc_attr_e('Men’s shoes', 'dakabrand'); ?>"><span><?php esc_html_e('View all', 'dakabrand'); ?></span></a>
                    </div>
                    <div class="fashion-mega-menu" data-mega-panel="men-accessories" aria-label="<?php esc_attr_e('Men accessory categories', 'dakabrand'); ?>">
                        <div class="fashion-mega-menu__groups"><div><strong><?php esc_html_e('Shop by category', 'dakabrand'); ?></strong><a href="<?php echo esc_url(home_url('/product-category/man/accessories/bags/')); ?>">Bags</a><a href="<?php echo esc_url(home_url('/product-category/watches/')); ?>">Watches</a><a href="<?php echo esc_url(home_url('/product-category/man/accessories/belts/')); ?>">Belts</a><a href="<?php echo esc_url(home_url('/product-category/man/accessories/sunglasses/')); ?>">Sunglasses</a><a href="<?php echo esc_url(home_url('/product-category/man/accessories/hats/')); ?>">Hats</a><a href="<?php echo esc_url(home_url('/product-category/man/accessories/wallets/')); ?>">Wallets</a><a href="<?php echo esc_url(home_url('/product-category/man/accessories/luggage/')); ?>">Luggage</a></div></div>
                        <a class="fashion-mega-menu__feature" href="<?php echo esc_url(home_url('/product-category/man/accessories/')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/man-product-hermes-190.jpg'); ?>" alt="<?php esc_attr_e('Men’s accessories', 'dakabrand'); ?>"><span><?php esc_html_e('View all', 'dakabrand'); ?></span></a>
                    </div>
                    <div class="fashion-mega-menu" data-mega-panel="men-preorder" aria-label="<?php esc_attr_e('Men preorder categories', 'dakabrand'); ?>">
                        <div class="fashion-mega-menu__groups"><div><strong><?php esc_html_e('Shop by category', 'dakabrand'); ?></strong><a href="<?php echo esc_url(home_url('/product-category/man/clothing/')); ?>">Clothing</a><a href="<?php echo esc_url(home_url('/product-category/man/shoes/')); ?>">Shoes</a><a href="<?php echo esc_url(home_url('/product-category/man/accessories/')); ?>">Accessories</a><a href="<?php echo esc_url(home_url('/product-category/man/accessories/bags/')); ?>">Bags</a></div></div>
                        <a class="fashion-mega-menu__feature" href="<?php echo esc_url(home_url('/shop/?stock_status=onbackorder%3Aonbackorder')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/man-product-hermes-277.jpg'); ?>" alt="<?php esc_attr_e('Men’s preorder collection', 'dakabrand'); ?>"><span><?php esc_html_e('View all', 'dakabrand'); ?></span></a>
                    </div>
                </div>
            </nav>

            <div class="site-header__actions">
                <?php echo staticbridge_language_switcher('language-switcher--desktop d-none d-lg-flex'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <button class="header-action header-cart" type="button" data-cart-open aria-label="<?php esc_attr_e('Open cart', 'dakabrand'); ?>" aria-controls="cart-drawer">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 8h14l-1 13H6L5 8ZM9 9V6a3 3 0 0 1 6 0v3"/></svg>
                    <span class="cart-count" data-cart-count aria-live="polite">0</span>
                </button>
                <details class="site-search" data-site-search>
                    <summary class="header-action" aria-label="<?php esc_attr_e('Search products', 'dakabrand'); ?>">
                        <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="10.8" cy="10.8" r="6.7"/><path d="m16 16 5 5"/></svg>
                    </summary>
                    <form class="site-search__form" role="search" action="<?php echo esc_url(home_url('/shop/')); ?>" method="get">
                        <label for="site-search-keyword"><?php esc_html_e('Search products', 'dakabrand'); ?></label>
                        <div class="site-search__field">
                            <input id="site-search-keyword" type="search" name="keyword" placeholder="<?php esc_attr_e('What are you looking for?', 'dakabrand'); ?>" required>
                            <button type="submit"><?php esc_html_e('Search', 'dakabrand'); ?></button>
                        </div>
                    </form>
                </details>
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
        <?php echo staticbridge_language_switcher('language-switcher--mobile'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
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
    </div>
</div>
<?php endif; ?>
