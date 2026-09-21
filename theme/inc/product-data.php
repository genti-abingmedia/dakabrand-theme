<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Return the storefront label for a product attribute.
 *
 * The imported shoe-size taxonomy is named "Atlete". Keep its stored name
 * intact while presenting the customer-facing label in the active language.
 */
function staticbridge_product_attribute_label(string $attribute_name): string
{
    $label = (string) wc_attribute_label($attribute_name);
    $taxonomy_name = preg_replace('/^(?:attribute_|pa_)/', '', $attribute_name);
    $is_shoe_size = 'atlete' === sanitize_title($label)
        || 'atlete' === sanitize_title((string) $taxonomy_name);

    if (!$is_shoe_size) {
        return $label;
    }

    return 'sq_AL' === staticbridge_requested_locale()
        ? __('Numri', 'dakabrand')
        : __('Size', 'dakabrand');
}

/**
 * Return the public, static-safe product data consumed by frontend JavaScript.
 */
function staticbridge_product_data(WC_Product $product): array
{
    $variations = array();
    $options = array();
    $plain_price = static function ($amount): string {
        return html_entity_decode(wp_strip_all_tags(wc_price($amount)), ENT_QUOTES, get_bloginfo('charset'));
    };
    $minimum_price = $plain_price($product->is_type('variable') ? $product->get_variation_price('min', true) : $product->get_price());
    $maximum_price = $product->is_type('variable') ? $plain_price($product->get_variation_price('max', true)) : $minimum_price;
    $price_text = $minimum_price === $maximum_price ? $minimum_price : $minimum_price . ' – ' . $maximum_price;

    if ($product->is_type('variable')) {
        foreach ($product->get_variation_attributes() as $attribute_name => $values) {
            $choices = array();
            foreach ($values as $value) {
                $label = (string) $value;
                if (taxonomy_exists($attribute_name)) {
                    $term = get_term_by('slug', $value, $attribute_name);
                    if ($term instanceof WP_Term) {
                        $label = $term->name;
                    }
                }
                $choices[] = array('value' => (string) $value, 'label' => $label);
            }
            $options[] = array(
                'key'     => wc_variation_attribute_name($attribute_name),
                'label'   => staticbridge_product_attribute_label($attribute_name),
                'choices' => $choices,
            );
        }

        foreach ($product->get_children() as $variation_id) {
            $variation = wc_get_product($variation_id);

            if (!$variation instanceof WC_Product_Variation) {
                continue;
            }

            $variations[] = array(
                'variation_id' => $variation->get_id(),
                'attributes'   => $variation->get_variation_attributes(),
                'price'        => $variation->get_price(),
                'regular_price'=> $variation->get_regular_price(),
                'sale_price'   => $variation->get_sale_price(),
                'price_html'   => wp_kses_post($variation->get_price_html()),
                'stock_status' => $variation->get_stock_status(),
                'purchasable'  => $variation->is_purchasable(),
            );
        }
    }

    return array(
        'product_id'   => $product->get_id(),
        'name'         => $product->get_name(),
        'permalink'    => get_permalink($product->get_id()),
        'image'        => wp_get_attachment_image_url($product->get_image_id(), 'woocommerce_thumbnail') ?: wc_placeholder_img_src(),
        'category'     => wp_strip_all_tags(wc_get_product_category_list($product->get_id(), ', ')),
        'price_text'   => $price_text,
        'currency'     => get_woocommerce_currency(),
        'slug'         => $product->get_slug(),
        'sku'          => $product->get_sku(),
        'type'         => $product->get_type(),
        'price'        => $product->get_price(),
        'regular_price'=> $product->get_regular_price(),
        'sale_price'   => $product->get_sale_price(),
        'stock_status' => $product->get_stock_status(),
        'purchasable'  => $product->is_purchasable(),
        'options'      => $options,
        'variations'   => $variations,
    );
}
