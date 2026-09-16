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
    array('DB-LOCAL-MAN-2', 'Local Minimal Watch', 'watches', '129', '', 2),
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

// The gallery-rich Weekender has two illustrative size variations.
$weekender_sku = 'DB-LOCAL-MAN-1';
$weekender_id = wc_get_product_id_by_sku($weekender_sku);
if ($weekender_id) {
    $previous_weekender = wc_get_product($weekender_id);
    if (!$previous_weekender instanceof WC_Product_Simple && !$previous_weekender instanceof WC_Product_Variable) {
        fwrite(STDERR, "Weekender SKU exists but is not a supported product type.\n");
        exit(1);
    }
    if ($previous_weekender instanceof WC_Product_Simple) {
        wp_set_object_terms($weekender_id, 'variable', 'product_type');
        wc_delete_product_transients($weekender_id);
    }
    $weekender = new WC_Product_Variable($weekender_id);
} else {
    $weekender = new WC_Product_Variable();
}

$weekender_size = new WC_Product_Attribute();
$weekender_size->set_name('Size');
$weekender_size->set_options(array('Standard', 'Large'));
$weekender_size->set_visible(true);
$weekender_size->set_variation(true);

$weekender->set_name('Local Leather Weekender');
$weekender->set_slug('db-local-man-1');
$weekender->set_sku($weekender_sku);
$weekender->set_status('publish');
$weekender->set_catalog_visibility('visible');
$weekender->set_category_ids(array($category_ids['man'], $category_ids['big-offer']));
$weekender->set_manage_stock(false);
$weekender->set_attributes(array($weekender_size));
$weekender->set_short_description('A dark brown leather weekender with rolled handles, a detachable shoulder strap and a zip-top opening. Choose Standard or Large. Illustrative local demo product.');
$weekender->set_description('<p>The Local Leather Weekender is an illustrative product created to test the DakaBrand product page. Its compact duffel shape is designed for short trips and everyday carry.</p><h3>Features</h3><ul><li>Dark brown leather exterior</li><li>Standard and Large size choices</li><li>Rolled top handles and detachable shoulder strap</li><li>Zip-top opening with brass-tone hardware</li></ul><h3>Care</h3><p>Wipe gently with a dry, soft cloth. Keep away from prolonged moisture and direct heat. Store unfilled when not in use.</p><p>This listing and its imagery are for local development only.</p>');
$weekender_id = $weekender->save();

foreach (array(
    array('Standard', 'STD', '189', '149', 4),
    array('Large', 'L', '219', '179', 2),
) as [$size, $suffix, $regular_price, $sale_price, $quantity]) {
    $variation_sku = $weekender_sku . '-' . $suffix;
    $variation_id = wc_get_product_id_by_sku($variation_sku);
    $variation = $variation_id ? wc_get_product($variation_id) : new WC_Product_Variation();
    if (!$variation instanceof WC_Product_Variation) {
        fwrite(STDERR, sprintf("Variation SKU %s exists but is not a variation.\n", $variation_sku));
        exit(1);
    }
    $variation->set_parent_id($weekender_id);
    $variation->set_sku($variation_sku);
    $variation->set_attributes(array('size' => $size));
    $variation->set_status('publish');
    $variation->set_regular_price($regular_price);
    $variation->set_sale_price($sale_price);
    $variation->set_manage_stock(true);
    $variation->set_stock_quantity($quantity);
    $variation->set_stock_status('instock');
    $variation->save();
}
WC_Product_Variable::sync($weekender_id);
wc_delete_product_transients($weekender_id);

// Keep a second variable fixture for testing a sold-out size.
$sneaker_sku = 'DB-LOCAL-MAN-3';
$sneaker_id = wc_get_product_id_by_sku($sneaker_sku);
if ($sneaker_id) {
    $previous_sneaker = wc_get_product($sneaker_id);
    if (!$previous_sneaker instanceof WC_Product_Simple && !$previous_sneaker instanceof WC_Product_Variable) {
        fwrite(STDERR, "Sneaker SKU exists but is not a supported product type.\n");
        exit(1);
    }
    if ($previous_sneaker instanceof WC_Product_Simple) {
        wp_set_object_terms($sneaker_id, 'variable', 'product_type');
        wc_delete_product_transients($sneaker_id);
    }
    $sneaker = new WC_Product_Variable($sneaker_id);
} else {
    $sneaker = new WC_Product_Variable();
}

$size_attribute = new WC_Product_Attribute();
$size_attribute->set_name('Size');
$size_attribute->set_options(array('EU 40', 'EU 41', 'EU 42'));
$size_attribute->set_visible(true);
$size_attribute->set_variation(true);

$sneaker->set_name('Local Everyday Sneaker');
$sneaker->set_slug('db-local-man-3');
$sneaker->set_sku($sneaker_sku);
$sneaker->set_status('publish');
$sneaker->set_catalog_visibility('visible');
$sneaker->set_category_ids(array($category_ids['man']));
$sneaker->set_manage_stock(false);
$sneaker->set_attributes(array($size_attribute));
$sneaker->set_short_description('An illustrative everyday sneaker with selectable EU sizes. Local development fixture.');
$sneaker->set_description('<p>This local demo sneaker is used to verify size selection, variation pricing and availability on the product page.</p><p>Illustrative development listing only.</p>');
$sneaker_id = $sneaker->save();

foreach (array(
    array('EU 40', '99', 2),
    array('EU 41', '109', 5),
    array('EU 42', '119', 0),
) as [$size, $price, $quantity]) {
    $variation_sku = $sneaker_sku . '-' . str_replace('EU ', '', $size);
    $variation_id = wc_get_product_id_by_sku($variation_sku);
    $variation = $variation_id ? wc_get_product($variation_id) : new WC_Product_Variation();
    if (!$variation instanceof WC_Product_Variation) {
        fwrite(STDERR, sprintf("Variation SKU %s exists but is not a variation.\n", $variation_sku));
        exit(1);
    }
    $variation->set_parent_id($sneaker_id);
    $variation->set_sku($variation_sku);
    $variation->set_attributes(array('size' => $size));
    $variation->set_status('publish');
    $variation->set_regular_price($price);
    $variation->set_manage_stock(true);
    $variation->set_stock_quantity($quantity);
    $variation->set_stock_status($quantity > 0 ? 'instock' : 'outofstock');
    $variation->save();
}
WC_Product_Variable::sync($sneaker_id);
wc_delete_product_transients($sneaker_id);

// The gallery is local-only. Theme files are mounted read-only; WordPress copies
// each source into its own uploads volume and reuses it on subsequent seed runs.
$weekender = wc_get_product($weekender_id);
if ($weekender instanceof WC_Product_Variable) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $gallery_ids = array();
    foreach (array('front', 'side', 'top', 'detail') as $view) {
        $filename = 'weekender-' . $view . '.jpg';
        $source = __DIR__ . '/assets/' . $filename;
        if (!is_readable($source)) {
            fwrite(STDERR, sprintf("Missing local gallery image: %s\n", $filename));
            exit(1);
        }

        $existing = get_posts(array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 1,
            'fields' => 'ids',
            'meta_key' => '_staticbridge_local_asset',
            'meta_value' => $filename,
        ));
        if ($existing && is_readable(get_attached_file((int) $existing[0]))) {
            $gallery_ids[] = (int) $existing[0];
            continue;
        }

        $temporary = wp_tempnam($filename);
        if (!$temporary || !copy($source, $temporary)) {
            fwrite(STDERR, sprintf("Could not prepare local gallery image: %s\n", $filename));
            exit(1);
        }
        $attachment_id = media_handle_sideload(array(
            'name' => $filename,
            'tmp_name' => $temporary,
        ), $weekender_id, 'Local Leather Weekender — ' . $view . ' view');
        if (is_wp_error($attachment_id)) {
            @unlink($temporary);
            fwrite(STDERR, sprintf("Could not import local gallery image %s: %s\n", $filename, $attachment_id->get_error_message()));
            exit(1);
        }
        update_post_meta($attachment_id, '_staticbridge_local_asset', $filename);
        update_post_meta($attachment_id, '_wp_attachment_image_alt', 'Local Leather Weekender — ' . $view . ' view');
        $gallery_ids[] = $attachment_id;
    }
    $weekender->set_image_id($gallery_ids[0]);
    $weekender->set_gallery_image_ids(array_slice($gallery_ids, 1));
    $weekender->save();
}

WP_CLI::success('Local demo catalog is ready.');
