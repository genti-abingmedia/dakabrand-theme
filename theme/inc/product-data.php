<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Return the public, static-safe product data consumed by frontend JavaScript.
 */
function staticbridge_product_data(WC_Product $product): array
{
    $variations = array();

    if ($product->is_type('variable')) {
        foreach ($product->get_children() as $variation_id) {
            $variation = wc_get_product($variation_id);

            if (!$variation instanceof WC_Product_Variation) {
                continue;
            }

            $variations[] = array(
                'variation_id' => $variation->get_id(),
                'attributes'   => $variation->get_variation_attributes(),
                'price'        => $variation->get_price(),
                'stock_status' => $variation->get_stock_status(),
                'purchasable'  => $variation->is_purchasable(),
            );
        }
    }

    return array(
        'product_id'   => $product->get_id(),
        'slug'         => $product->get_slug(),
        'sku'          => $product->get_sku(),
        'type'         => $product->get_type(),
        'price'        => $product->get_price(),
        'regular_price'=> $product->get_regular_price(),
        'sale_price'   => $product->get_sale_price(),
        'stock_status' => $product->get_stock_status(),
        'purchasable'  => $product->is_purchasable(),
        'variations'   => $variations,
    );
}

