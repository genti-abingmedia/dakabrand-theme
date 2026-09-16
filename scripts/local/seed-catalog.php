<?php

if (!defined('ABSPATH') || !class_exists('WooCommerce')) {
    fwrite(STDERR, "WooCommerce must be active before seeding the catalog.\n");
    exit(1);
}

$category_ids = array();
foreach (array(
    'women' => array('name' => 'Women', 'parent' => ''),
    'man' => array('name' => 'Man', 'parent' => ''),
    'watches' => array('name' => 'Watches', 'parent' => ''),
    'big-offer' => array('name' => 'Big Offer', 'parent' => 'man'),
    'big-offer-women' => array('name' => 'Big Offer Women', 'parent' => 'women'),
) as $slug => $category) {
    $existing = get_term_by('slug', $slug, 'product_cat');
    if ($existing instanceof WP_Term) {
        $category_ids[$slug] = $existing->term_id;
        continue;
    }

    $parent_id = $category['parent'] ? ($category_ids[$category['parent']] ?? 0) : 0;
    $created = wp_insert_term($category['name'], 'product_cat', array(
        'slug' => $slug,
        'parent' => $parent_id,
    ));

    if (is_wp_error($created)) {
        fwrite(STDERR, sprintf("Could not create category %s: %s\n", $slug, $created->get_error_message()));
        exit(1);
    }
    $category_ids[$slug] = (int) $created['term_id'];
}

$products = array(
    array('DB-LOCAL-MAN-1', 'Local Leather Weekender', 'man', '189', '149', 4),
    array('DB-LOCAL-MAN-2', 'Local Minimal Watch', 'watches', '129', '', 2),
    array('DB-LOCAL-MAN-3', 'Local Everyday Sneaker', 'man', '99', '', 8),
    array('DB-LOCAL-WOMEN-1', 'Local Structured Bag', 'women', '219', '179', 3),
    array('DB-LOCAL-WOMEN-2', 'Local Silk Scarf', 'women', '79', '', 6),
    array('DB-LOCAL-WOMEN-3', 'Local Evening Clutch', 'women', '159', '129', 2),
);

foreach ($products as [$sku, $name, $category_slug, $regular_price, $sale_price, $stock]) {
    $product_id = wc_get_product_id_by_sku($sku);
    $product = $product_id ? wc_get_product($product_id) : new WC_Product_Simple();

    if (!$product instanceof WC_Product_Simple) {
        fwrite(STDERR, sprintf("SKU %s exists but is not a simple product.\n", $sku));
        exit(1);
    }

    $categories = array($category_ids[$category_slug]);
    if ('watches' === $category_slug) {
        $categories[] = $category_ids['man'];
    }
    if ('' !== $sale_price) {
        $categories[] = str_starts_with($sku, 'DB-LOCAL-WOMEN')
            ? $category_ids['big-offer-women']
            : $category_ids['big-offer'];
    }

    $product->set_name($name);
    $product->set_slug(strtolower(str_replace('_', '-', $sku)));
    $product->set_sku($sku);
    $product->set_status('publish');
    $product->set_catalog_visibility('visible');
    $product->set_regular_price($regular_price);
    $product->set_sale_price($sale_price);
    $product->set_manage_stock(true);
    $product->set_stock_quantity($stock);
    $product->set_stock_status('instock');
    $product->set_category_ids(array_values(array_unique($categories)));
    $product->set_short_description('Local development fixture. Safe to change or delete.');
    $product->save();
}

WP_CLI::success('Local demo catalog is ready.');
