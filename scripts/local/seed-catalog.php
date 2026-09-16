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
    if ('DB-LOCAL-MAN-1' === $sku) {
        $product->set_short_description('A dark brown leather weekender with rolled handles, a detachable shoulder strap and a zip-top opening. Illustrative local demo product.');
        $product->set_description('<p>The Local Leather Weekender is an illustrative product created to test the DakaBrand product page. Its compact duffel shape is designed for short trips and everyday carry.</p><h3>Features</h3><ul><li>Dark brown leather exterior</li><li>Rolled top handles and detachable shoulder strap</li><li>Zip-top opening with brass-tone hardware</li></ul><h3>Care</h3><p>Wipe gently with a dry, soft cloth. Keep away from prolonged moisture and direct heat. Store unfilled when not in use.</p><p>This listing and its imagery are for local development only.</p>');
    } else {
        $product->set_short_description('Local development fixture. Safe to change or delete.');
    }
    $product->save();
}

// The gallery is local-only. Theme files are mounted read-only; WordPress copies
// each source into its own uploads volume and reuses it on subsequent seed runs.
$weekender_id = wc_get_product_id_by_sku('DB-LOCAL-MAN-1');
$weekender = $weekender_id ? wc_get_product($weekender_id) : null;
if ($weekender instanceof WC_Product_Simple) {
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
