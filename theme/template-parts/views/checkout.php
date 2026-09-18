<?php
if (!defined('ABSPATH')) {
    exit;
}

$checkout_country_fields = array();
foreach (WC()->countries->get_shipping_countries() as $country_code => $country_name) {
    $fields = WC()->countries->get_address_fields($country_code, 'billing_');
    $states = WC()->countries->get_states($country_code);
    $checkout_country_fields[$country_code] = array(
        'postcode' => !empty($fields['billing_postcode']) && empty($fields['billing_postcode']['hidden']) ? !empty($fields['billing_postcode']['required']) : null,
        'state'    => !empty($fields['billing_state']) && empty($fields['billing_state']['hidden']) ? !empty($fields['billing_state']['required']) : null,
        'states'   => is_array($states) ? $states : array(),
    );
}
?>
<main id="main" class="site-main site-shell checkout-page" data-static-view="checkout" data-checkout-page>
    <header class="checkout-page__header">
        <p class="checkout-page__eyebrow"><?php esc_html_e('Secure checkout', 'dakabrand'); ?></p>
        <h1><?php esc_html_e('Checkout', 'dakabrand'); ?> <span data-checkout-page-count aria-live="polite"></span></h1>
    </header>

    <p class="checkout-page__status" data-checkout-page-status role="status" aria-live="polite" hidden></p>

    <div class="checkout-page__layout">
        <form id="checkout-form" class="checkout-form" data-checkout-form>
            <section class="checkout-form__section" aria-labelledby="billing-details-title">
                <h2 id="billing-details-title"><?php esc_html_e('Billing details', 'dakabrand'); ?></h2>
                <div class="checkout-form__grid">
                    <p class="checkout-form__wide"><label for="billing-name"><?php esc_html_e('Name / Surname', 'dakabrand'); ?> <span aria-hidden="true">*</span></label><input id="billing-name" name="billing_name" autocomplete="name" placeholder="<?php esc_attr_e('Name / Surname', 'dakabrand'); ?>" pattern=".*\S\s+\S.*" title="<?php esc_attr_e('Enter your first name and surname', 'dakabrand'); ?>" required></p>
                    <p class="checkout-form__wide"><label for="billing-country"><?php esc_html_e('Country / Region', 'dakabrand'); ?> <span aria-hidden="true">*</span></label><select id="billing-country" name="billing_country" autocomplete="country" data-country-fields="<?php echo esc_attr(wp_json_encode($checkout_country_fields)); ?>" required>
                        <option value=""><?php esc_html_e('Select a country / region…', 'dakabrand'); ?></option>
                        <?php foreach (WC()->countries->get_shipping_countries() as $code => $country) : ?>
                            <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($country); ?></option>
                        <?php endforeach; ?>
                    </select></p>
                    <p class="checkout-form__wide"><label for="billing-address-1"><?php esc_html_e('Street address', 'dakabrand'); ?> <span aria-hidden="true">*</span></label><input id="billing-address-1" name="billing_address_1" autocomplete="street-address" placeholder="<?php esc_attr_e('House number and street name', 'dakabrand'); ?>" required></p>
                    <p class="checkout-form__wide"><label for="billing-city"><?php esc_html_e('City', 'dakabrand'); ?> <span aria-hidden="true">*</span></label><input id="billing-city" name="billing_city" autocomplete="address-level2" placeholder="<?php esc_attr_e('City', 'dakabrand'); ?>" required></p>
                    <p class="checkout-form__wide" data-checkout-postcode hidden><label for="billing-postcode"><?php esc_html_e('Postcode', 'dakabrand'); ?> <span aria-hidden="true" data-required-marker>*</span></label><input id="billing-postcode" name="billing_postcode" autocomplete="postal-code" disabled></p>
                    <p class="checkout-form__wide" data-checkout-state hidden><label for="billing-state"><?php esc_html_e('State / Region', 'dakabrand'); ?> <span aria-hidden="true" data-required-marker>*</span></label><input id="billing-state" name="billing_state" autocomplete="address-level1" disabled><select name="billing_state" aria-label="<?php esc_attr_e('State / Region', 'dakabrand'); ?>" disabled hidden></select></p>
                    <p><label for="billing-phone"><?php esc_html_e('Phone', 'dakabrand'); ?> <span aria-hidden="true">*</span></label><input id="billing-phone" name="billing_phone" type="tel" autocomplete="tel" required></p>
                    <p><label for="billing-email"><?php esc_html_e('Email address', 'dakabrand'); ?> <span aria-hidden="true">*</span></label><input id="billing-email" name="billing_email" type="email" autocomplete="email" required></p>
                </div>
            </section>

        </form>

        <aside class="checkout-order" data-checkout-page-summary hidden aria-label="<?php esc_attr_e('Order summary', 'dakabrand'); ?>">
            <h2><?php esc_html_e('Order summary', 'dakabrand'); ?></h2>
            <div data-checkout-page-items></div>
            <div class="checkout-order__subtotal"><span><?php esc_html_e('Subtotal', 'dakabrand'); ?></span><strong data-checkout-page-subtotal></strong></div>
            <div class="checkout-order__subtotal"><span><?php esc_html_e('Shipping', 'dakabrand'); ?></span><strong data-checkout-shipping-total>—</strong></div>
            <div data-checkout-adjustments></div>
            <div class="checkout-order__subtotal checkout-order__total"><span><?php esc_html_e('Total', 'dakabrand'); ?></span><strong data-checkout-order-total>—</strong></div>
            <section class="checkout-order__shipping" aria-labelledby="shipping-method-title">
                <div class="checkout-order__section-heading"><h2 id="shipping-method-title"><?php esc_html_e('Shipping method', 'dakabrand'); ?></h2><span><?php esc_html_e('Select one', 'dakabrand'); ?></span></div>
                <div data-checkout-shipping-rates role="group" aria-labelledby="shipping-method-title">
                    <p><?php esc_html_e('Enter your address to see shipping options.', 'dakabrand'); ?></p>
                </div>
            </section>
            <section class="checkout-form__section checkout-form__section--payment" aria-labelledby="payment-title">
                <h2 id="payment-title"><?php esc_html_e('Payment information', 'dakabrand'); ?></h2>
                <fieldset class="checkout-payment-methods">
                    <legend class="screen-reader-text"><?php esc_html_e('Choose a payment method', 'dakabrand'); ?></legend>
                    <label class="checkout-payment-method">
                        <input type="radio" name="checkout_payment_option" value="cod" form="checkout-form">
                        <span><?php esc_html_e('Cash on delivery', 'dakabrand'); ?></span>
                    </label>
                    <label class="checkout-payment-method">
                        <input type="radio" name="checkout_payment_option" value="remittance" form="checkout-form" checked>
                        <span><?php esc_html_e('Western union / Moneygram / Ria', 'dakabrand'); ?></span>
                        <span class="checkout-payment-method__western-union" aria-label="Western Union">WESTERN<br>UNION</span>
                    </label>
                </fieldset>
                <p class="checkout-payment-note"><?php esc_html_e('Please note that orders paid via “Western union / Moneygram / Ria” will be processed upon payment confirmation. If you have any questions or need assistance, please contact our customer support at Whatsapp: +355683885286', 'dakabrand'); ?></p>
            </section>
            <button class="checkout-form__submit" type="submit" form="checkout-form" disabled><?php esc_html_e('Place order', 'dakabrand'); ?></button>
            <p><?php esc_html_e('Prices and availability are provisional. Items are not reserved.', 'dakabrand'); ?></p>
            <a href="<?php echo esc_url(home_url('/cart/')); ?>"><?php esc_html_e('Edit cart', 'dakabrand'); ?></a>
        </aside>
    </div>
    <section class="checkout-confirmation" data-checkout-confirmation hidden role="status" aria-live="polite">
        <div class="checkout-confirmation__mark" aria-hidden="true">
            <svg viewBox="0 0 64 64" focusable="false"><circle cx="32" cy="32" r="30"/><path d="m19 33 8 8 18-19"/></svg>
        </div>
        <p class="checkout-confirmation__eyebrow"><?php esc_html_e('Order confirmed', 'dakabrand'); ?></p>
        <h2><?php esc_html_e('Thank you for your order!', 'dakabrand'); ?></h2>
        <p class="checkout-confirmation__message"><?php esc_html_e('Your order has been placed and will be processed as soon as possible.', 'dakabrand'); ?></p>
        <p class="checkout-confirmation__number" data-checkout-confirmation-number></p>
        <section class="checkout-confirmation__receipt" aria-labelledby="checkout-confirmation-details-title">
            <div class="checkout-confirmation__receipt-heading"><h3 id="checkout-confirmation-details-title"><?php esc_html_e('Order details', 'dakabrand'); ?></h3><span><?php esc_html_e('Receipt', 'dakabrand'); ?></span></div>
            <div class="checkout-confirmation__items" data-checkout-confirmation-items></div>
            <div class="checkout-confirmation__totals" data-checkout-confirmation-totals></div>
            <dl class="checkout-confirmation__meta">
                <div><dt><?php esc_html_e('Payment', 'dakabrand'); ?></dt><dd data-checkout-confirmation-payment></dd></div>
                <div><dt><?php esc_html_e('Delivery', 'dakabrand'); ?></dt><dd data-checkout-confirmation-delivery></dd></div>
            </dl>
        </section>
        <a class="checkout-confirmation__cta" href="<?php echo esc_url(home_url('/shop/')); ?>"><?php esc_html_e('Continue shopping', 'dakabrand'); ?></a>
    </section>
</main>
