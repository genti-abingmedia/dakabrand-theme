<?php
/**
 * Run against disposable local WordPress only, in both configuration states:
 * docker compose exec -T wordpress php < tests/test_product_visibility.php
 * docker compose exec -T -e DAKABRAND_TEST_BF_VISIBLE=1 wordpress php < tests/test_product_visibility.php
 */
define('DAKABRAND_SHOW_BLACK_FRIDAY_PRODUCTS', '1' === getenv('DAKABRAND_TEST_BF_VISIBLE'));
require '/var/www/html/wp-load.php';

if ('local' !== wp_get_environment_type()) {
    throw new RuntimeException('This test requires the disposable local site.');
}

function visibility_check(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo "PASS $message\n";
}

$created_terms = array();
$products = array();
$saved_query = $GLOBALS['wp_query'];
$show = DAKABRAND_SHOW_BLACK_FRIDAY_PRODUCTS;
try {
    $term = get_term_by('slug', 'black-friday', 'product_cat');
    if (!$term) {
        $result = wp_insert_term('Black Friday', 'product_cat', array('slug' => 'black-friday'));
        if (is_wp_error($result)) {
            throw new RuntimeException($result->get_error_message());
        }
        $created_terms[] = $result['term_id'];
        $term = get_term($result['term_id'], 'product_cat');
    }
    $suffix = strtolower(wp_generate_password(8, false));
    foreach (array('regular', 'child') as $kind) {
        $args = array('slug' => "visibility-$kind-$suffix");
        if ('child' === $kind) {
            $args['parent'] = $term->term_id;
        }
        $result = wp_insert_term("Visibility $kind $suffix", 'product_cat', $args);
        if (is_wp_error($result)) {
            throw new RuntimeException($result->get_error_message());
        }
        $created_terms[] = $result['term_id'];
        $terms[$kind] = (int) $result['term_id'];
    }

    foreach (array('visible', 'staged', 'child') as $kind) {
        $product = new WC_Product_Simple();
        $product->set_name("Visibility $kind $suffix");
        $product->set_status('publish');
        $product->set_regular_price('10');
        $categories = array($terms['regular']);
        if ('staged' === $kind) {
            $categories[] = (int) $term->term_id;
        } elseif ('child' === $kind) {
            $categories[] = $terms['child'];
        }
        $product->set_category_ids($categories);
        $product->save();
        $products[$kind] = $product;
    }
    $ids = array_map(static fn ($product) => $product->get_id(), $products);
    $query = new WP_Query(array(
        'post_type' => 'product', 'post__in' => array_values($ids),
        'posts_per_page' => 10, 'fields' => 'ids',
        'tax_query' => array('relation' => 'OR',
            array('taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => array($terms['regular'])),
            array('taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => array($terms['child'])),
        ),
    ));
    visibility_check(count($query->posts) === ($show ? 3 : 1), 'listings preserve OR filters and exclude staged categories before pagination');
    visibility_check(in_array($ids['visible'], $query->posts, true), 'ordinary products remain listed');
    visibility_check($products['staged']->is_visible() === $show, 'multi-category product visibility follows config');
    visibility_check($products['child']->is_purchasable() === $show, 'child-category purchase follows config');
    visibility_check($products['visible']->is_purchasable(), 'ordinary products remain purchasable');
    $variation = new WC_Product_Variation();
    $variation->set_parent_id($ids['staged']);
    visibility_check(staticbridge_is_hidden_product($variation) === !$show, 'variation inherits staged parent visibility');

    if (!$show) {
        $GLOBALS['wp_query'] = new WP_Query(array('post_type' => 'product', 'p' => $ids['staged']));
        ob_start();
        staticbridge_render_document('product');
        $html = ob_get_clean();
        visibility_check(is_404(), 'direct static-renderer calls mark staged product pages as 404');
        visibility_check(false === strpos($html, 'data-staticbridge-product'), 'hidden product payload is absent from generated HTML');
        visibility_check(false !== strpos($html, 'noindex'), 'hidden product response is noindex');
    }
} finally {
    $GLOBALS['wp_query'] = $saved_query;
    foreach ($products as $product) {
        $product->delete(true);
    }
    foreach (array_reverse($created_terms) as $id) {
        wp_delete_term($id, 'product_cat');
    }
}
