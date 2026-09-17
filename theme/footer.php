<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<?php if (!staticbridge_is_immersive_front_page()) : ?>
<footer class="site-footer" data-component="site-footer">
    <section class="footer-benefits" aria-label="<?php esc_attr_e('Shopping benefits', 'dakabrand'); ?>">
        <div class="site-shell footer-benefits__grid">
            <div class="footer-benefit">
                <svg aria-hidden="true" viewBox="0 0 32 32"><path d="M2 8h18v15H2zM20 13h5l5 5v5H20z"/><circle cx="8" cy="25" r="3"/><circle cx="25" cy="25" r="3"/></svg>
                <div><h2><?php esc_html_e('Free Shipping', 'dakabrand'); ?></h2><p><?php esc_html_e('Free shipping for orders over £130.', 'dakabrand'); ?></p></div>
            </div>
            <div class="footer-benefit">
                <svg aria-hidden="true" viewBox="0 0 32 32"><path d="M16 3 27 7v8c0 7-4.5 11.5-11 14-6.5-2.5-11-7-11-14V7l11-4Z"/><path d="m11 16 3 3 7-7"/></svg>
                <div><h2><?php esc_html_e('Money Guarantee', 'dakabrand'); ?></h2><p><?php esc_html_e('Exchange eligible items within 30 days.', 'dakabrand'); ?></p></div>
            </div>
            <div class="footer-benefit">
                <svg aria-hidden="true" viewBox="0 0 32 32"><path d="M5 18v-3a11 11 0 0 1 22 0v3"/><path d="M5 17H2v8h6v-8H5Zm22 0h3v8h-6v-8h3ZM24 26c-2 3-5 3-8 3"/></svg>
                <div><h2><?php esc_html_e('Online Support', 'dakabrand'); ?></h2><p><?php esc_html_e('Available 24 hours a day, 7 days a week.', 'dakabrand'); ?></p></div>
            </div>
            <div class="footer-benefit">
                <svg aria-hidden="true" viewBox="0 0 32 32"><rect x="2" y="6" width="28" height="20" rx="2"/><path d="M2 12h28M7 21h6"/></svg>
                <div><h2><?php esc_html_e('Flexible Payment', 'dakabrand'); ?></h2><p><?php esc_html_e('Pay securely with multiple payment methods.', 'dakabrand'); ?></p></div>
            </div>
        </div>
    </section>

    <div class="site-footer__main">
        <div class="site-shell site-footer__grid">
            <section class="footer-newsletter" aria-labelledby="footer-newsletter-title">
                <h2 id="footer-newsletter-title"><?php esc_html_e('Stay in touch', 'dakabrand'); ?></h2>
                <p><?php esc_html_e('Sign up for our newsletter and receive 10% off your first order.', 'dakabrand'); ?></p>
                <form class="newsletter-form" data-newsletter-form novalidate>
                    <label class="screen-reader-text" for="footer-newsletter-email"><?php esc_html_e('Email address', 'dakabrand'); ?></label>
                    <div class="newsletter-form__field">
                        <input id="footer-newsletter-email" type="email" name="email" autocomplete="email" placeholder="<?php esc_attr_e('Enter your email', 'dakabrand'); ?>" required>
                        <button type="submit"><?php esc_html_e('Subscribe', 'dakabrand'); ?></button>
                    </div>
                    <p class="newsletter-form__status" data-newsletter-status aria-live="polite"></p>
                </form>
            </section>

            <section class="footer-column">
                <button class="footer-column__toggle" type="button" data-bs-toggle="collapse" data-bs-target="#footer-information" aria-expanded="false" aria-controls="footer-information"><?php esc_html_e('Information', 'dakabrand'); ?><span aria-hidden="true">+</span></button>
                <h2 class="footer-column__title"><?php esc_html_e('Information', 'dakabrand'); ?></h2>
                <div class="collapse d-lg-block" id="footer-information">
                    <?php
                    wp_nav_menu(array(
                        'theme_location' => has_nav_menu('footer_information') ? 'footer_information' : 'footer',
                        'container'      => false,
                        'fallback_cb'    => 'staticbridge_footer_information_fallback',
                        'menu_class'     => 'footer-menu',
                        'depth'          => 1,
                    ));
                    ?>
                </div>
            </section>

            <section class="footer-column">
                <button class="footer-column__toggle" type="button" data-bs-toggle="collapse" data-bs-target="#footer-services" aria-expanded="false" aria-controls="footer-services"><?php esc_html_e('Services', 'dakabrand'); ?><span aria-hidden="true">+</span></button>
                <h2 class="footer-column__title"><?php esc_html_e('Services', 'dakabrand'); ?></h2>
                <div class="collapse d-lg-block" id="footer-services">
                    <?php if (has_nav_menu('footer_services')) : ?>
                        <?php wp_nav_menu(array('theme_location' => 'footer_services', 'container' => false, 'fallback_cb' => false, 'menu_class' => 'footer-menu', 'depth' => 1)); ?>
                    <?php else : ?>
                        <ul class="footer-menu">
                            <li><a href="<?php echo esc_url(home_url('/contact-us/')); ?>"><?php esc_html_e('Contact Us', 'dakabrand'); ?></a></li>
                            <li><a href="<?php echo esc_url(home_url('/privacy-policy/')); ?>"><?php esc_html_e('Privacy Policy', 'dakabrand'); ?></a></li>
                            <li><a href="<?php echo esc_url(home_url('/terms-conditions/')); ?>"><?php esc_html_e('Terms & Conditions', 'dakabrand'); ?></a></li>
                            <li><a href="<?php echo esc_url(home_url('/refund_returns/')); ?>"><?php esc_html_e('Refund Policy', 'dakabrand'); ?></a></li>
                        </ul>
                    <?php endif; ?>
                </div>
            </section>

            <section class="footer-column footer-social">
                <h2><?php esc_html_e('Social Media', 'dakabrand'); ?></h2>
                <div class="footer-social__links">
                    <a href="https://instagram.com/daka__man" target="_blank" rel="noopener noreferrer" aria-label="Instagram">Instagram</a>
                    <a href="https://www.tiktok.com/@daka.brand" target="_blank" rel="noopener noreferrer" aria-label="TikTok">TikTok</a>
                </div>
            </section>
        </div>
    </div>

    <div class="site-footer__legal">
        <div class="site-shell">
            <p><?php esc_html_e('Web & App created by', 'dakabrand'); ?> <a href="https://gliterin.com/" target="_blank" rel="noopener noreferrer">gliterin.com</a> &copy; <?php echo esc_html(wp_date('Y')); ?> <?php echo esc_html(get_bloginfo('name')); ?></p>
        </div>
    </div>
</footer>
<?php endif; ?>

<nav class="mobile-tabs d-lg-none" aria-label="<?php esc_attr_e('Mobile app navigation', 'dakabrand'); ?>" data-mobile-tabs>
    <a href="<?php echo esc_url(home_url('/')); ?>" data-mobile-tab="home"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="m3 11 9-8 9 8v10h-6v-7H9v7H3V11Z"/></svg><span><?php esc_html_e('Home', 'dakabrand'); ?></span></a>
    <a href="<?php echo esc_url(home_url('/woman/')); ?>" data-mobile-tab="women"><svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M7 21c0-4 2-7 5-7s5 3 5 7M12 12v9M9 18h6"/></svg><span><?php esc_html_e('Women', 'dakabrand'); ?></span></a>
    <a class="mobile-tabs__primary" href="<?php echo esc_url(home_url('/shop/')); ?>" data-mobile-tab="shop"><span class="mobile-tabs__primary-icon"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 8h16l-1 13H5L4 8ZM9 9V6a3 3 0 0 1 6 0v3"/></svg></span><span><?php esc_html_e('Shop', 'dakabrand'); ?></span></a>
    <a href="<?php echo esc_url(home_url('/man/')); ?>" data-mobile-tab="man"><svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="10" cy="10" r="5"/><path d="m14 6 6-4M16 2h4v4M10 15v7M7 19h6"/></svg><span><?php esc_html_e('Man', 'dakabrand'); ?></span></a>
    <a href="<?php echo esc_url(home_url('/cart/')); ?>" data-mobile-tab="cart" data-cart-link><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M5 8h14l-1 13H6L5 8ZM9 9V6a3 3 0 0 1 6 0v3"/></svg><span><?php esc_html_e('Cart', 'dakabrand'); ?></span><span class="cart-count" data-cart-count aria-live="polite">0</span></a>
</nav>
<aside class="offcanvas offcanvas-end cart-drawer" tabindex="-1" id="cart-drawer" aria-labelledby="cart-drawer-title" data-cart-drawer>
    <div class="offcanvas-header cart-drawer__header">
        <h2 class="offcanvas-title" id="cart-drawer-title"><?php esc_html_e('Your cart', 'dakabrand'); ?> <span data-cart-drawer-count></span></h2>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="<?php esc_attr_e('Close cart', 'dakabrand'); ?>"></button>
    </div>
    <div class="offcanvas-body cart-drawer__body">
        <p class="cart-drawer__status" data-cart-status role="status" aria-live="polite" hidden></p>
        <div data-cart-items></div>
    </div>
    <div class="cart-drawer__footer" data-cart-footer hidden>
        <div class="cart-drawer__subtotal"><span><?php esc_html_e('Subtotal', 'dakabrand'); ?></span><strong data-cart-subtotal></strong></div>
        <p><?php esc_html_e('Prices and availability are provisional. Items are not reserved.', 'dakabrand'); ?></p>
        <a class="cart-drawer__checkout" href="<?php echo esc_url(home_url('/checkout/')); ?>"><?php esc_html_e('Proceed to checkout', 'dakabrand'); ?></a>
        <button type="button" data-bs-dismiss="offcanvas"><?php esc_html_e('Continue shopping', 'dakabrand'); ?></button>
    </div>
</aside>
<?php wp_footer(); ?>
</body>
</html>
