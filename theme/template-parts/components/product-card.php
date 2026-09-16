<?php
if (!defined('ABSPATH')) {
    exit;
}

$card_product = wc_get_product(get_the_ID());

if (!$card_product instanceof WC_Product || !$card_product->is_visible()) {
    return;
}

$card_categories = wc_get_product_category_list($card_product->get_id(), ', ');
?>
<article
    class="product-card"
    data-product-card
    data-product-id="<?php echo esc_attr((string) $card_product->get_id()); ?>"
    data-product-type="<?php echo esc_attr($card_product->get_type()); ?>"
>
    <a class="product-card__link" href="<?php echo esc_url(get_permalink($card_product->get_id())); ?>">
        <?php echo wp_kses_post($card_product->get_image('woocommerce_thumbnail')); ?>
        <?php if ($card_categories) : ?>
            <p class="product-card__category"><?php echo wp_kses_post($card_categories); ?></p>
        <?php endif; ?>
        <h2><?php echo esc_html($card_product->get_name()); ?></h2>
    </a>

    <div class="product-price" data-product-price>
        <?php echo wp_kses_post($card_product->get_price_html()); ?>
    </div>

    <?php if ($card_product->is_type('simple')) : ?>
        <button
            type="button"
            data-add-to-cart
            data-product-id="<?php echo esc_attr((string) $card_product->get_id()); ?>"
            <?php disabled(!$card_product->is_purchasable()); ?>
        >
            <?php esc_html_e('Add to cart', 'dakabrand'); ?>
        </button>
    <?php else : ?>
        <a href="<?php echo esc_url(get_permalink($card_product->get_id())); ?>">
            <?php esc_html_e('Choose options', 'dakabrand'); ?>
        </a>
    <?php endif; ?>
    <?php if ($card_product->is_type('simple')) : ?>
        <script type="application/json" data-staticbridge-product><?php echo wp_json_encode(staticbridge_product_data($card_product), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
    <?php endif; ?>
</article>
