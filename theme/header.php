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
                        <a class="has-mega-menu" href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'clothing')); ?>" data-mega-trigger="women-clothing"><?php esc_html_e('Clothing', 'dakabrand'); ?></a>
                        <a class="has-mega-menu" href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'shoes')); ?>" data-mega-trigger="women-shoes"><?php esc_html_e('Shoes', 'dakabrand'); ?></a>
                        <a class="has-mega-menu" href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'accessories')); ?>" data-mega-trigger="women-accessories"><?php esc_html_e('Accessories', 'dakabrand'); ?></a>
                        <a class="has-mega-menu" href="<?php echo esc_url(home_url('/shop/?stock_status=onbackorder%3Aonbackorder')); ?>" data-mega-trigger="women-preorder"><?php esc_html_e('Preorder', 'dakabrand'); ?></a>
                        <a href="<?php echo esc_url(home_url('/shop/?sale=1')); ?>"><?php esc_html_e('Big Offer', 'dakabrand'); ?></a>
                    </div>
                    <div class="fashion-mega-menu" data-mega-panel="women-new-in" aria-label="<?php esc_attr_e('Women new arrivals', 'dakabrand'); ?>">
                        <div class="fashion-mega-menu__groups"><div><strong><?php esc_html_e('Shop by category', 'dakabrand'); ?></strong><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'clothing')); ?>">Clothing</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'shoes')); ?>">Shoes</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'accessories')); ?>">Accessories</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'mules-slides-slippers-women')); ?>">Mules, Slides &amp; Slippers</a><a href="<?php echo esc_url(home_url('/shop/?stock_status=onbackorder%3Aonbackorder')); ?>">Preorder</a></div></div>
                        <a class="fashion-mega-menu__feature" href="<?php echo esc_url(home_url('/product-category/women/new-collection-women/')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/woman-editorial-primary.jpg'); ?>" alt="<?php esc_attr_e('New women’s arrivals', 'dakabrand'); ?>"><span><?php esc_html_e('View all', 'dakabrand'); ?></span></a>
                    </div>
                    <div class="fashion-mega-menu" data-mega-panel="women-clothing" aria-label="<?php esc_attr_e('Women clothing categories', 'dakabrand'); ?>">
                        <div class="fashion-mega-menu__groups">
                            <div><strong><?php esc_html_e('Shop by category', 'dakabrand'); ?></strong><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/clothing/', 't-shirts-women')); ?>">T-Shirts</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/clothing/', 'jackets-women')); ?>">Jackets</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/clothing/', 'jeans-women')); ?>">Jeans</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/clothing/', 'outfits-sets')); ?>">Outfits &amp; Sets</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/clothing/', 'pants')); ?>">Pants</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/clothing/', 'dresses')); ?>">Dresses</a></div>
                            <div class="fashion-mega-menu__offset"><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/clothing/', 'shirts-women')); ?>">Shirts</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/clothing/', 'swimwear-2')); ?>">Swimwear</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/clothing/', 'bikini')); ?>">Bikini</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/clothing/', 'blouse')); ?>">Blouse</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/clothing/', 'body')); ?>">Body</a></div>
                        </div>
                        <a class="fashion-mega-menu__feature" href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'clothing')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/mega-women-clothing.jpg'); ?>" alt="<?php esc_attr_e('Women’s clothing', 'dakabrand'); ?>"><span><?php esc_html_e('View all', 'dakabrand'); ?></span></a>
                    </div>
                    <div class="fashion-mega-menu" data-mega-panel="women-shoes" aria-label="<?php esc_attr_e('Women shoe categories', 'dakabrand'); ?>">
                        <div class="fashion-mega-menu__groups"><div><strong><?php esc_html_e('Shop by category', 'dakabrand'); ?></strong><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'mules-slides-slippers-women')); ?>">Mules, Slides &amp; Slippers</a></div></div>
                        <a class="fashion-mega-menu__feature" href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'shoes')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/mega-women-shoes.jpg'); ?>" alt="<?php esc_attr_e('Women’s shoes', 'dakabrand'); ?>"><span><?php esc_html_e('View all', 'dakabrand'); ?></span></a>
                    </div>
                    <div class="fashion-mega-menu" data-mega-panel="women-accessories" aria-label="<?php esc_attr_e('Women accessory categories', 'dakabrand'); ?>">
                        <div class="fashion-mega-menu__groups"><div><strong><?php esc_html_e('Shop by category', 'dakabrand'); ?></strong><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'handbags')); ?>">Bags</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'belts-women')); ?>">Belts</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/accessories/', 'watches-women')); ?>">Watches</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/accessories/', 'sunglasses-women')); ?>">Sunglasses</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/accessories/', 'hats-women')); ?>">Hats</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/accessories/', 'scarves')); ?>">Scarves</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/accessories/', 'jewelry-women')); ?>">Jewelry</a></div></div>
                        <a class="fashion-mega-menu__feature" href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'accessories')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/woman-product-chanel-380.jpg'); ?>" alt="<?php esc_attr_e('Women’s accessories', 'dakabrand'); ?>"><span><?php esc_html_e('View all', 'dakabrand'); ?></span></a>
                    </div>
                    <div class="fashion-mega-menu" data-mega-panel="women-preorder" aria-label="<?php esc_attr_e('Women preorder categories', 'dakabrand'); ?>">
                        <div class="fashion-mega-menu__groups"><div><strong><?php esc_html_e('Shop by category', 'dakabrand'); ?></strong><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'clothing')); ?>">Clothing</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'shoes')); ?>">Shoes</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'handbags')); ?>">Bags</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'belts-women')); ?>">Belts</a></div></div>
                        <a class="fashion-mega-menu__feature" href="<?php echo esc_url(home_url('/shop/?stock_status=onbackorder%3Aonbackorder')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/woman-editorial-secondary.jpg'); ?>" alt="<?php esc_attr_e('Women’s preorder collection', 'dakabrand'); ?>"><span><?php esc_html_e('View all', 'dakabrand'); ?></span></a>
                    </div>
                </div>

                <div class="fashion-navigation__collections<?php echo 'men' === $active_fashion_department ? ' is-current' : ''; ?>" data-fashion-collection="men"<?php echo 'men' !== $active_fashion_department ? ' hidden' : ''; ?>>
                    <div class="fashion-navigation__links">
                        <a class="has-mega-menu" href="<?php echo esc_url(home_url('/product-category/man/new-collection-man/')); ?>" data-mega-trigger="men-new-in"><?php esc_html_e('New In', 'dakabrand'); ?></a>
                        <a class="has-mega-menu" href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/', 'clothing-man')); ?>" data-mega-trigger="men-clothing"><?php esc_html_e('Clothing', 'dakabrand'); ?></a>
                        <a class="has-mega-menu" href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/', 'shoes-man')); ?>" data-mega-trigger="men-shoes"><?php esc_html_e('Shoes', 'dakabrand'); ?></a>
                        <a class="has-mega-menu" href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/', 'accessories-man')); ?>" data-mega-trigger="men-accessories"><?php esc_html_e('Accessories', 'dakabrand'); ?></a>
                        <a class="has-mega-menu" href="<?php echo esc_url(home_url('/shop/?stock_status=onbackorder%3Aonbackorder')); ?>" data-mega-trigger="men-preorder"><?php esc_html_e('Preorder', 'dakabrand'); ?></a>
                        <a href="<?php echo esc_url(home_url('/shop/?sale=1')); ?>"><?php esc_html_e('Big Offer', 'dakabrand'); ?></a>
                    </div>
                    <div class="fashion-mega-menu" data-mega-panel="men-new-in" aria-label="<?php esc_attr_e('Men new arrivals', 'dakabrand'); ?>">
                        <div class="fashion-mega-menu__groups"><div><strong><?php esc_html_e('Shop by category', 'dakabrand'); ?></strong><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/', 'clothing-man')); ?>">Clothing</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/', 'shoes-man')); ?>">Shoes</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/', 'accessories-man')); ?>">Accessories</a><a href="<?php echo esc_url(home_url('/shop/?stock_status=onbackorder%3Aonbackorder')); ?>">Preorder</a></div></div>
                        <a class="fashion-mega-menu__feature" href="<?php echo esc_url(home_url('/product-category/man/new-collection-man/')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/man-editorial-primary.jpg'); ?>" alt="<?php esc_attr_e('New men’s arrivals', 'dakabrand'); ?>"><span><?php esc_html_e('View all', 'dakabrand'); ?></span></a>
                    </div>
                    <div class="fashion-mega-menu" data-mega-panel="men-clothing" aria-label="<?php esc_attr_e('Men clothing categories', 'dakabrand'); ?>">
                        <div class="fashion-mega-menu__groups">
                            <div><strong><?php esc_html_e('Shop by category', 'dakabrand'); ?></strong><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/clothing-man/', 'hoodies-man')); ?>">Hoodies</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/clothing-man/', 'jackets-man')); ?>">Jackets</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/clothing-man/', 'jeans-man')); ?>">Jeans</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/clothing-man/', 'shirts-man')); ?>">Shirts</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/clothing-man/', 'pants-man')); ?>">Pants</a></div>
                            <div class="fashion-mega-menu__offset"><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/clothing-man/', 'shirts-man')); ?>">Shirts</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/clothing-man/', 't-shirts-man')); ?>">T-Shirt</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/clothing-man/', 'swimwear')); ?>">Swimwear</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/clothing-man/', 'track-suits-man')); ?>">Track Suits</a></div>
                        </div>
                        <a class="fashion-mega-menu__feature" href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/', 'clothing-man')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/mega-men-clothing.jpg'); ?>" alt="<?php esc_attr_e('Men’s clothing', 'dakabrand'); ?>"><span><?php esc_html_e('View all', 'dakabrand'); ?></span></a>
                    </div>
                    <div class="fashion-mega-menu" data-mega-panel="men-shoes" aria-label="<?php esc_attr_e('Men shoe categories', 'dakabrand'); ?>">
                        <div class="fashion-mega-menu__groups"><div><strong><?php esc_html_e('Shop by category', 'dakabrand'); ?></strong><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/', 'mules-slides-slippers')); ?>">Mules, Slides &amp; Slippers</a></div></div>
                        <a class="fashion-mega-menu__feature" href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/', 'shoes-man')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/mega-men-shoes.jpg'); ?>" alt="<?php esc_attr_e('Men’s shoes', 'dakabrand'); ?>"><span><?php esc_html_e('View all', 'dakabrand'); ?></span></a>
                    </div>
                    <div class="fashion-mega-menu" data-mega-panel="men-accessories" aria-label="<?php esc_attr_e('Men accessory categories', 'dakabrand'); ?>">
                        <div class="fashion-mega-menu__groups"><div><strong><?php esc_html_e('Shop by category', 'dakabrand'); ?></strong><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/', 'bags')); ?>">Bags</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/accessories-man/', 'watches-man')); ?>">Watches</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/', 'belts-man')); ?>">Belts</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/accessories-man/', 'sunglasses-man')); ?>">Sunglasses</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/accessories-man/', 'hats-man')); ?>">Hats</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/accessories-man/', 'jewelry-man')); ?>">Jewelry</a></div></div>
                        <a class="fashion-mega-menu__feature" href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/', 'accessories-man')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/man-product-hermes-190.jpg'); ?>" alt="<?php esc_attr_e('Men’s accessories', 'dakabrand'); ?>"><span><?php esc_html_e('View all', 'dakabrand'); ?></span></a>
                    </div>
                    <div class="fashion-mega-menu" data-mega-panel="men-preorder" aria-label="<?php esc_attr_e('Men preorder categories', 'dakabrand'); ?>">
                        <div class="fashion-mega-menu__groups"><div><strong><?php esc_html_e('Shop by category', 'dakabrand'); ?></strong><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/', 'clothing-man')); ?>">Clothing</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/', 'shoes-man')); ?>">Shoes</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/', 'accessories-man')); ?>">Accessories</a><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/', 'bags')); ?>">Bags</a></div></div>
                        <a class="fashion-mega-menu__feature" href="<?php echo esc_url(home_url('/shop/?stock_status=onbackorder%3Aonbackorder')); ?>"><img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/man-editorial-secondary.jpg'); ?>" alt="<?php esc_attr_e('Men’s preorder collection', 'dakabrand'); ?>"><span><?php esc_html_e('View all', 'dakabrand'); ?></span></a>
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
<p class="stock-mode-badge" data-stock-mode-badge role="status" aria-live="polite" hidden></p>

<div class="offcanvas offcanvas-start mobile-navigation" tabindex="-1" id="mobile-navigation" aria-labelledby="mobile-navigation-title">
    <div class="offcanvas-header">
        <h2 class="offcanvas-title" id="mobile-navigation-title"><?php esc_html_e('Menu', 'dakabrand'); ?></h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="<?php esc_attr_e('Close navigation', 'dakabrand'); ?>"></button>
    </div>
    <div class="offcanvas-body">
        <?php echo staticbridge_language_switcher('language-switcher--mobile'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        <nav aria-label="<?php esc_attr_e('Mobile navigation', 'dakabrand'); ?>">
            <ul class="mobile-navigation__menu">
                <li class="menu-item-has-children">
                    <a href="<?php echo esc_url(staticbridge_department_page_url('woman')); ?>"><?php esc_html_e('Women', 'dakabrand'); ?></a>
                    <ul class="sub-menu">
                        <li><a href="<?php echo esc_url(home_url('/product-category/women/new-collection-women/')); ?>"><?php esc_html_e('New In', 'dakabrand'); ?></a></li>
                        <li class="menu-item-has-children"><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'clothing')); ?>"><?php esc_html_e('Clothing', 'dakabrand'); ?></a><ul class="sub-menu"><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/clothing/', 't-shirts-women')); ?>">T-Shirts</a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/clothing/', 'jackets-women')); ?>"><?php esc_html_e('Jackets', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/clothing/', 'jeans-women')); ?>"><?php esc_html_e('Jeans', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/clothing/', 'outfits-sets')); ?>"><?php esc_html_e('Outfits & Sets', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/clothing/', 'pants')); ?>"><?php esc_html_e('Pants', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/clothing/', 'dresses')); ?>"><?php esc_html_e('Dresses', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/clothing/', 'shirts-women')); ?>"><?php esc_html_e('Shirts', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/clothing/', 'swimwear-2')); ?>"><?php esc_html_e('Swimwear', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/clothing/', 'bikini')); ?>"><?php esc_html_e('Bikini', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/clothing/', 'blouse')); ?>"><?php esc_html_e('Blouse', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/clothing/', 'body')); ?>"><?php esc_html_e('Body', 'dakabrand'); ?></a></li></ul></li>
                        <li class="menu-item-has-children"><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'shoes')); ?>"><?php esc_html_e('Shoes', 'dakabrand'); ?></a><ul class="sub-menu"><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'mules-slides-slippers-women')); ?>">Mules, Slides &amp; Slippers</a></li></ul></li>
                        <li class="menu-item-has-children"><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'accessories')); ?>"><?php esc_html_e('Accessories', 'dakabrand'); ?></a><ul class="sub-menu"><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'handbags')); ?>"><?php esc_html_e('Bags', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/', 'belts-women')); ?>"><?php esc_html_e('Belts', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/accessories/', 'watches-women')); ?>"><?php esc_html_e('Watches', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/accessories/', 'sunglasses-women')); ?>"><?php esc_html_e('Sunglasses', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/accessories/', 'hats-women')); ?>"><?php esc_html_e('Hats', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/accessories/', 'scarves')); ?>"><?php esc_html_e('Scarves', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/women/accessories/', 'jewelry-women')); ?>"><?php esc_html_e('Jewelry', 'dakabrand'); ?></a></li></ul></li>
                        <li><a href="<?php echo esc_url(home_url('/shop/?stock_status=onbackorder%3Aonbackorder')); ?>"><?php esc_html_e('Preorder', 'dakabrand'); ?></a></li>
                        <li><a href="<?php echo esc_url(home_url('/shop/?sale=1')); ?>"><?php esc_html_e('Big Offer', 'dakabrand'); ?></a></li>
                    </ul>
                </li>
                <li class="menu-item-has-children">
                    <a href="<?php echo esc_url(staticbridge_department_page_url('man')); ?>"><?php esc_html_e('Man', 'dakabrand'); ?></a>
                    <ul class="sub-menu">
                        <li><a href="<?php echo esc_url(home_url('/product-category/man/new-collection-man/')); ?>"><?php esc_html_e('New In', 'dakabrand'); ?></a></li>
                        <li class="menu-item-has-children"><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/', 'clothing-man')); ?>"><?php esc_html_e('Clothing', 'dakabrand'); ?></a><ul class="sub-menu"><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/clothing-man/', 'hoodies-man')); ?>"><?php esc_html_e('Hoodies', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/clothing-man/', 'jackets-man')); ?>"><?php esc_html_e('Jackets', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/clothing-man/', 'jeans-man')); ?>"><?php esc_html_e('Jeans', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/clothing-man/', 'shirts-man')); ?>"><?php esc_html_e('Shirts', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/clothing-man/', 'pants-man')); ?>"><?php esc_html_e('Pants', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/clothing-man/', 't-shirts-man')); ?>">T-Shirt</a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/clothing-man/', 'swimwear')); ?>"><?php esc_html_e('Swimwear', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/clothing-man/', 'track-suits-man')); ?>"><?php esc_html_e('Track Suits', 'dakabrand'); ?></a></li></ul></li>
                        <li class="menu-item-has-children"><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/', 'shoes-man')); ?>"><?php esc_html_e('Shoes', 'dakabrand'); ?></a><ul class="sub-menu"><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/', 'mules-slides-slippers')); ?>">Mules, Slides &amp; Slippers</a></li></ul></li>
                        <li class="menu-item-has-children"><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/', 'accessories-man')); ?>"><?php esc_html_e('Accessories', 'dakabrand'); ?></a><ul class="sub-menu"><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/', 'bags')); ?>"><?php esc_html_e('Bags', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/', 'belts-man')); ?>"><?php esc_html_e('Belts', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/accessories-man/', 'watches-man')); ?>"><?php esc_html_e('Watches', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/accessories-man/', 'sunglasses-man')); ?>"><?php esc_html_e('Sunglasses', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/accessories-man/', 'hats-man')); ?>"><?php esc_html_e('Hats', 'dakabrand'); ?></a></li><li><a href="<?php echo esc_url(staticbridge_catalog_category_url('/product-category/man/accessories-man/', 'jewelry-man')); ?>"><?php esc_html_e('Jewelry', 'dakabrand'); ?></a></li></ul></li>
                        <li><a href="<?php echo esc_url(home_url('/shop/?stock_status=onbackorder%3Aonbackorder')); ?>"><?php esc_html_e('Preorder', 'dakabrand'); ?></a></li>
                        <li><a href="<?php echo esc_url(home_url('/shop/?sale=1')); ?>"><?php esc_html_e('Big Offer', 'dakabrand'); ?></a></li>
                    </ul>
                </li>
            </ul>
        </nav>
    </div>
</div>
<?php endif; ?>
