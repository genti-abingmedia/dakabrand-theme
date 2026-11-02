<?php

if (!defined('ABSPATH')) {
    exit;
}

/** Include child categories so staging a whole collection is consistent. */
function staticbridge_hidden_product_category_slugs(): array
{
    if (DAKABRAND_SHOW_BLACK_FRIDAY_PRODUCTS) {
        return array();
    }
    static $cached_slugs = null;
    if (null !== $cached_slugs) {
        return $cached_slugs;
    }

    $slugs = array('black-friday');
    $term = get_term_by('slug', 'black-friday', 'product_cat');
    if ($term instanceof WP_Term) {
        $children = get_term_children($term->term_id, 'product_cat');
        if (!is_wp_error($children)) {
            foreach ($children as $id) {
                $child = get_term($id, 'product_cat');
                if ($child instanceof WP_Term) {
                    $slugs[] = $child->slug;
                }
            }
        }
    }
    $cached_slugs = array_values(array_unique($slugs));
    return $cached_slugs;
}

function staticbridge_is_hidden_product(WC_Product $product): bool
{
    $slugs = staticbridge_hidden_product_category_slugs();
    $id = $product->get_parent_id() ?: $product->get_id();
    return $slugs && has_term($slugs, 'product_cat', $id);
}

/** Filter before pagination, preserving existing OR/AND taxonomy conditions. */
function staticbridge_exclude_staged_products(WP_Query $query): void
{
    if (is_admin() || $query->is_singular() || DAKABRAND_SHOW_BLACK_FRIDAY_PRODUCTS) {
        return;
    }
    $post_types = (array) $query->get('post_type');
    if (!in_array('product', $post_types, true)
        && !($query->is_main_query() && $query->is_search())) {
        return;
    }
    $existing = $query->get('tax_query');
    $tax_query = array('relation' => 'AND');
    if (is_array($existing) && $existing) {
        $tax_query[] = $existing;
    }
    $tax_query[] = array(
        'taxonomy' => 'product_cat',
        'field' => 'slug',
        'terms' => array('black-friday'),
        'operator' => 'NOT IN',
        'include_children' => true,
    );
    $query->set('tax_query', $tax_query);
}
add_action('pre_get_posts', 'staticbridge_exclude_staged_products', 99);

function staticbridge_staged_product_is_visible(bool $visible, int $product_id): bool
{
    $product = wc_get_product($product_id);
    return $visible && (!$product instanceof WC_Product || !staticbridge_is_hidden_product($product));
}
add_filter('woocommerce_product_is_visible', 'staticbridge_staged_product_is_visible', 10, 2);

/** Also reject saved cart lines and variations until the collection launches. */
function staticbridge_staged_product_is_purchasable(bool $purchasable, WC_Product $product): bool
{
    return $purchasable && !staticbridge_is_hidden_product($product);
}
add_filter('woocommerce_is_purchasable', 'staticbridge_staged_product_is_purchasable', 10, 2);
add_filter('woocommerce_variation_is_purchasable', 'staticbridge_staged_product_is_purchasable', 10, 2);
