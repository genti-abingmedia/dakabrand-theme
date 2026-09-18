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

            <section class="checkout-form__section checkout-form__section--delivery" aria-label="<?php esc_attr_e('Delivery address', 'dakabrand'); ?>">
                <label class="checkout-form__different"><input type="checkbox" name="ship_to_different_address" value="1" data-ship-different> <span><?php esc_html_e('Ship to a different address?', 'dakabrand'); ?></span></label>
                <div class="checkout-form__shipping-fields" data-shipping-fields hidden>
                    <div class="checkout-form__grid">
                        <p class="checkout-form__wide"><label for="shipping-name"><?php esc_html_e('Name / Surname', 'dakabrand'); ?> <span aria-hidden="true">*</span></label><input id="shipping-name" name="shipping_name" autocomplete="shipping name" disabled required></p>
                        <p class="checkout-form__wide"><label for="shipping-country"><?php esc_html_e('Country / Region', 'dakabrand'); ?> <span aria-hidden="true">*</span></label><select id="shipping-country" name="shipping_country" autocomplete="shipping country" data-country-fields="<?php echo esc_attr(wp_json_encode($checkout_country_fields)); ?>" disabled required>
                            <option value=""><?php esc_html_e('Select a country / region…', 'dakabrand'); ?></option>
                            <?php foreach (WC()->countries->get_shipping_countries() as $code => $country) : ?>
                                <option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($country); ?></option>
                            <?php endforeach; ?>
                        </select></p>
                        <p class="checkout-form__wide"><label for="shipping-address-1"><?php esc_html_e('Street address', 'dakabrand'); ?> <span aria-hidden="true">*</span></label><input id="shipping-address-1" name="shipping_address_1" autocomplete="shipping street-address" disabled required></p>
                        <p class="checkout-form__wide"><label for="shipping-city"><?php esc_html_e('City', 'dakabrand'); ?> <span aria-hidden="true">*</span></label><input id="shipping-city" name="shipping_city" autocomplete="shipping address-level2" disabled required></p>
                        <p class="checkout-form__wide" data-shipping-postcode hidden><label for="shipping-postcode"><?php esc_html_e('Postcode', 'dakabrand'); ?> <span aria-hidden="true" data-required-marker>*</span></label><input id="shipping-postcode" name="shipping_postcode" autocomplete="shipping postal-code" disabled></p>
                        <p class="checkout-form__wide" data-shipping-state hidden><label for="shipping-state"><?php esc_html_e('State / Region', 'dakabrand'); ?> <span aria-hidden="true" data-required-marker>*</span></label><input id="shipping-state" name="shipping_state" autocomplete="shipping address-level1" disabled><select name="shipping_state" aria-label="<?php esc_attr_e('State / Region', 'dakabrand'); ?>" disabled hidden></select></p>
                    </div>
                </div>
            </section>

        </form>

        <aside class="checkout-order" data-checkout-page-summary hidden aria-label="<?php esc_attr_e('Order summary', 'dakabrand'); ?>">
            <h2><?php esc_html_e('Order summary', 'dakabrand'); ?></h2>
            <div data-checkout-page-items></div>
            <div class="checkout-order__tools" role="group" aria-label="<?php esc_attr_e('Order options', 'dakabrand'); ?>">
                <button type="button" data-checkout-open="note"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 20 4.5-1 11-11a2 2 0 0 0-3-3l-11 11L4 20Zm10-13 3 3"/></svg><?php esc_html_e('Note', 'dakabrand'); ?></button>
                <button type="button" data-checkout-open="shipping"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 6h12v10H2V6Zm12 3h4l3 3v4h-7V9ZM5 19a2 2 0 1 0 4 0 2 2 0 0 0-4 0Zm11 0a2 2 0 1 0 4 0 2 2 0 0 0-4 0ZM2 16h19"/></svg><?php esc_html_e('Shipping', 'dakabrand'); ?></button>
                <button type="button" data-checkout-open="coupon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18v5a2 2 0 0 0 0 4v4H3v-4a2 2 0 0 0 0-4V6Zm8 0v13"/></svg><?php esc_html_e('Coupon', 'dakabrand'); ?></button>
            </div>
            <div class="checkout-order__subtotal"><span><?php esc_html_e('Subtotal', 'dakabrand'); ?></span><strong data-checkout-page-subtotal></strong></div>
            <div class="checkout-order__subtotal"><span><?php esc_html_e('Shipping', 'dakabrand'); ?></span><strong data-checkout-shipping-total>—</strong></div>
            <div data-checkout-adjustments></div>
            <div class="checkout-order__subtotal checkout-order__total"><span><?php esc_html_e('Total', 'dakabrand'); ?></span><strong data-checkout-order-total>—</strong></div>
            <section class="checkout-order__shipping" data-checkout-shipping-methods aria-labelledby="shipping-method-title" hidden>
                <div class="checkout-order__section-heading"><h2 id="shipping-method-title"><?php esc_html_e('Shipping method', 'dakabrand'); ?></h2><span><?php esc_html_e('Select one', 'dakabrand'); ?></span></div>
                <div data-checkout-shipping-rates role="group" aria-labelledby="shipping-method-title">
                    <p><?php esc_html_e('Enter your address to see shipping options.', 'dakabrand'); ?></p>
                </div>
            </section>
            <section class="checkout-form__section checkout-form__section--payment" aria-labelledby="payment-title">
                <h2 id="payment-title"><?php esc_html_e('Payment information', 'dakabrand'); ?></h2>
                <div class="checkout-payment-methods">
                    <div class="checkout-payment-method">
                        <span><?php esc_html_e('Western union / Moneygram / Ria', 'dakabrand'); ?></span>
                        <span class="checkout-payment-method__western-union" aria-label="Western Union">WESTERN<br>UNION</span>
                    </div>
                </div>
                <p class="checkout-payment-note"><?php esc_html_e('Please note that orders paid via “Western union / Moneygram / Ria” will be processed upon payment confirmation. If you have any questions or need assistance, please contact our customer support at Whatsapp: +355683885286', 'dakabrand'); ?></p>
            </section>
            <button class="checkout-form__submit" type="submit" form="checkout-form" disabled><?php esc_html_e('Place order', 'dakabrand'); ?></button>
            <p><?php esc_html_e('Prices and availability are provisional. Items are not reserved.', 'dakabrand'); ?></p>
            <a href="<?php echo esc_url(home_url('/cart/')); ?>"><?php esc_html_e('Edit cart', 'dakabrand'); ?></a>
        </aside>
    </div>
    <dialog class="checkout-dialog" data-checkout-dialog="note" aria-labelledby="checkout-note-title">
        <form method="dialog" class="checkout-dialog__content" data-note-form>
            <button class="checkout-dialog__close" type="button" data-checkout-close aria-label="<?php esc_attr_e('Close', 'dakabrand'); ?>">×</button>
            <h2 id="checkout-note-title"><?php esc_html_e('Add note for seller', 'dakabrand'); ?></h2>
            <textarea name="customer_note" rows="5" placeholder="<?php esc_attr_e('Notes about your order, e.g. special notes for delivery.', 'dakabrand'); ?>"></textarea>
            <button class="checkout-dialog__action" type="submit"><?php esc_html_e('Save', 'dakabrand'); ?></button>
            <button class="checkout-dialog__cancel" type="button" data-checkout-close><?php esc_html_e('Cancel', 'dakabrand'); ?></button>
        </form>
    </dialog>
    <dialog class="checkout-dialog" data-checkout-dialog="shipping" aria-labelledby="checkout-estimate-title">
        <form method="dialog" class="checkout-dialog__content" data-estimate-form>
            <button class="checkout-dialog__close" type="button" data-checkout-close aria-label="<?php esc_attr_e('Close', 'dakabrand'); ?>">×</button>
            <h2 id="checkout-estimate-title"><?php esc_html_e('Estimate shipping rates', 'dakabrand'); ?></h2>
            <label for="estimate-country" class="screen-reader-text"><?php esc_html_e('Country / Region', 'dakabrand'); ?></label>
            <select id="estimate-country" name="estimate_country" required><option value=""><?php esc_html_e('Select a country / region…', 'dakabrand'); ?></option><?php foreach (WC()->countries->get_shipping_countries() as $code => $country) : ?><option value="<?php echo esc_attr($code); ?>"><?php echo esc_html($country); ?></option><?php endforeach; ?></select>
            <label for="estimate-state" class="screen-reader-text"><?php esc_html_e('State / Region', 'dakabrand'); ?></label>
            <select id="estimate-state" name="estimate_state" hidden disabled></select>
            <input id="estimate-state-text" name="estimate_state_text" aria-label="<?php esc_attr_e('State / Region', 'dakabrand'); ?>" hidden disabled>
            <label for="estimate-city" class="screen-reader-text"><?php esc_html_e('City', 'dakabrand'); ?></label><input id="estimate-city" name="estimate_city" placeholder="<?php esc_attr_e('City', 'dakabrand'); ?>" required>
            <label for="estimate-postcode" class="screen-reader-text"><?php esc_html_e('Postcode', 'dakabrand'); ?></label><input id="estimate-postcode" name="estimate_postcode" placeholder="<?php esc_attr_e('Postcode', 'dakabrand'); ?>">
            <p class="checkout-dialog__error" data-dialog-error role="alert" hidden></p>
            <button class="checkout-dialog__action" type="submit"><?php esc_html_e('Calculate shipping rates', 'dakabrand'); ?></button>
            <button class="checkout-dialog__cancel" type="button" data-checkout-close><?php esc_html_e('Cancel', 'dakabrand'); ?></button>
        </form>
    </dialog>
    <dialog class="checkout-dialog" data-checkout-dialog="coupon" aria-labelledby="checkout-coupon-title">
        <form method="dialog" class="checkout-dialog__content" data-coupon-form>
            <button class="checkout-dialog__close" type="button" data-checkout-close aria-label="<?php esc_attr_e('Close', 'dakabrand'); ?>">×</button>
            <h2 id="checkout-coupon-title"><?php esc_html_e('Select or input Coupon', 'dakabrand'); ?></h2>
            <p><?php esc_html_e('If you have a coupon code, please apply it below.', 'dakabrand'); ?></p>
            <label for="checkout-coupon-code" class="screen-reader-text"><?php esc_html_e('Coupon code', 'dakabrand'); ?></label><input id="checkout-coupon-code" name="code" placeholder="<?php esc_attr_e('Coupon code', 'dakabrand'); ?>" required>
            <p class="checkout-dialog__error" data-dialog-error role="alert" hidden></p>
            <button class="checkout-dialog__action" type="submit"><?php esc_html_e('Apply coupon', 'dakabrand'); ?></button>
            <button class="checkout-dialog__cancel" type="button" data-checkout-close><?php esc_html_e('Cancel', 'dakabrand'); ?></button>
        </form>
    </dialog>
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
