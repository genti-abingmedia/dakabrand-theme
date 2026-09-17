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
