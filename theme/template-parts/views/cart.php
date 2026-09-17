<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<main id="main" class="site-main site-shell cart-page" data-static-view="cart" data-cart-page>
    <header class="cart-page__header">
        <p class="cart-page__eyebrow"><?php esc_html_e('Shopping bag', 'dakabrand'); ?></p>
        <h1><?php esc_html_e('Your cart', 'dakabrand'); ?> <span data-cart-page-count aria-live="polite"></span></h1>
    </header>

    <p class="cart-page__status" data-cart-page-status role="status" aria-live="polite" hidden></p>

    <div class="cart-page__layout">
        <section class="cart-page__items" aria-label="<?php esc_attr_e('Cart items', 'dakabrand'); ?>" data-cart-page-items></section>

        <aside class="cart-page__summary" data-cart-page-summary hidden aria-label="<?php esc_attr_e('Order summary', 'dakabrand'); ?>">
            <h2><?php esc_html_e('Order summary', 'dakabrand'); ?></h2>
            <div class="cart-page__subtotal"><span><?php esc_html_e('Subtotal', 'dakabrand'); ?></span><strong data-cart-page-subtotal></strong></div>
            <p><?php esc_html_e('Prices and availability are provisional. Items are not reserved.', 'dakabrand'); ?></p>
            <a class="cart-page__checkout" href="<?php echo esc_url(home_url('/checkout/')); ?>"><?php esc_html_e('Proceed to checkout', 'dakabrand'); ?></a>
            <a class="cart-page__continue" href="<?php echo esc_url(home_url('/shop/')); ?>"><?php esc_html_e('Continue shopping', 'dakabrand'); ?></a>
        </aside>
    </div>
</main>
