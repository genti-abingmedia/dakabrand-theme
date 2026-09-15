<?php
if (!defined('ABSPATH')) {
    exit;
}

global $product;

if (!$product instanceof WC_Product) {
    $product = wc_get_product(get_the_ID());
}

if (!$product instanceof WC_Product) {
    return;
}

$payload = staticbridge_product_data($product);
?>
<main id="main" class="site-main site-shell" data-static-view="product">
    <article
        <?php wc_product_class('product-detail', $product); ?>
        data-product-id="<?php echo esc_attr((string) $product->get_id()); ?>"
        data-product-type="<?php echo esc_attr($product->get_type()); ?>"
    >
        <div class="product-detail__media">
            <?php echo wp_kses_post($product->get_image('woocommerce_single')); ?>
        </div>

        <div class="product-detail__summary">
            <h1><?php echo esc_html($product->get_name()); ?></h1>
            <div class="product-price" data-product-price>
                <?php echo wp_kses_post($product->get_price_html()); ?>
            </div>

            <?php if ($product->get_short_description()) : ?>
                <div class="product-summary">
                    <?php echo wp_kses_post(wpautop($product->get_short_description())); ?>
                </div>
            <?php endif; ?>

            <div data-product-options></div>

            <button
                type="button"
                class="add-to-cart-button"
                data-add-to-cart
                data-product-id="<?php echo esc_attr((string) $product->get_id()); ?>"
                <?php disabled(!$product->is_purchasable()); ?>
            >
                <?php esc_html_e('Add to cart', 'dakabrand'); ?>
            </button>
        </div>

        <div class="product-detail__description">
            <?php echo wp_kses_post(apply_filters('the_content', $product->get_description())); ?>
        </div>

        <script type="application/json" data-staticbridge-product>
            <?php echo wp_json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
        </script>
    </article>
</main>
