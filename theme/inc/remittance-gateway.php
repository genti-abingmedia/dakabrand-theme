<?php

if (!defined('ABSPATH') || !class_exists('WC_Payment_Gateway')) {
    return;
}

/**
 * An offline payment method for payments made through Western Union,
 * MoneyGram, or Ria. Orders remain on hold until payment is confirmed.
 */
class StaticBridge_Remittance_Gateway extends WC_Payment_Gateway
{
    public function __construct()
    {
        $this->id = 'staticbridge_remittance';
        $this->method_title = __('Western Union / MoneyGram / Ria', 'dakabrand');
        $this->method_description = __('Manual money-transfer payment confirmed by customer support.', 'dakabrand');
        $this->has_fields = false;
        $this->enabled = 'yes';
        $this->title = __('Western union / Moneygram / Ria', 'dakabrand');
        $this->order_button_text = __('Place order', 'dakabrand');
        $this->supports = array('products');
    }

    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            return array('result' => 'failure');
        }

        $order->update_status('on-hold', __('Awaiting Western Union / MoneyGram / Ria payment confirmation.', 'dakabrand'));
        wc_reduce_stock_levels($order_id);
        WC()->cart->empty_cart();

        return array(
            'result'   => 'success',
            'redirect' => $this->get_return_url($order),
        );
    }
}

function staticbridge_add_remittance_gateway(array $gateways): array
{
    $gateways[] = 'StaticBridge_Remittance_Gateway';

    return $gateways;
}
add_filter('woocommerce_payment_gateways', 'staticbridge_add_remittance_gateway');

/**
 * Cash on delivery is shown by default and is available only for deliveries
 * within Albania once a delivery address has been entered.
 *
 * WooCommerce's Store API rebuilds the payment-method list after the checkout
 * address changes, so applying this filter keeps both the custom checkout UI
 * and the order endpoint subject to the same restriction.
 *
 * @param array<string, WC_Payment_Gateway> $available_gateways
 * @return array<string, WC_Payment_Gateway>
 */
function staticbridge_limit_cash_on_delivery_to_albania(array $available_gateways): array
{
    if (!isset($available_gateways['cod'])) {
        return $available_gateways;
    }

    // Do not affect the gateway-management screen in wp-admin.
    if (is_admin() && !wp_doing_ajax()) {
        return $available_gateways;
    }

    $shipping_country = '';
    $shipping_address = '';
    if (function_exists('WC') && WC()->customer) {
        $shipping_country = (string) WC()->customer->get_shipping_country();
        $shipping_address = (string) WC()->customer->get_shipping_address_1();
    }

    // WooCommerce initializes an empty customer from the store base country.
    // Do not mistake that default for a country the shopper actually chose.
    // The Store API refreshes this list after the complete delivery address is
    // submitted, at which point COD is removed outside Albania.
    if ('' !== trim($shipping_address) && 'AL' !== strtoupper(trim($shipping_country))) {
        unset($available_gateways['cod']);
    }

    return $available_gateways;
}
add_filter('woocommerce_available_payment_gateways', 'staticbridge_limit_cash_on_delivery_to_albania', 20);
