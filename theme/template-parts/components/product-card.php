<?php
if (!defined('ABSPATH')) {
    exit;
}

$card_product = wc_get_product(get_the_ID());

if (!$card_product instanceof WC_Product || !$card_product->is_visible()) {
    return;
}

$card_permalink = get_permalink($card_product->get_id());
$card_regular_price = (float) $card_product->get_regular_price();
$card_sale_price = (float) $card_product->get_sale_price();
$card_price = (float) $card_product->get_price();
$card_on_sale = $card_product->is_on_sale() && $card_regular_price > $card_sale_price && $card_sale_price > 0;
$card_stock_status = $card_product->get_stock_status();
$card_categories = wc_get_product_category_list($card_product->get_id(), ', ');
$card_image_id = $card_product->get_image_id();
?>
<article
    class="catalog-card"
    data-product-card
    data-product-id="<?php echo esc_attr((string) $card_product->get_id()); ?>"
    data-product-type="<?php echo esc_attr($card_product->get_type()); ?>"
>
    <div class="catalog-card__media">
        <a class="catalog-card__image-link<?php echo $card_image_id ? '' : ' has-image-error'; ?>" href="<?php echo esc_url($card_permalink); ?>" aria-label="<?php echo esc_attr($card_product->get_name()); ?>">
            <?php if ($card_image_id) : ?>
                <?php echo wp_kses_post($card_product->get_image('woocommerce_thumbnail', array('loading' => 'eager', 'decoding' => 'async'))); ?>
            <?php endif; ?>
        </a>
        <?php if ($card_on_sale) : ?>
            <span class="catalog-card__badge"><?php echo esc_html((string) round((($card_regular_price - $card_sale_price) / $card_regular_price) * 100) . '%'); ?></span>
        <?php endif; ?>
        <?php if ('instock' === $card_stock_status) : ?>
            <span class="catalog-card__stock"><?php esc_html_e('In stock', 'dakabrand'); ?></span>
        <?php elseif ('outofstock' === $card_stock_status) : ?>
            <span class="catalog-card__stock"><?php esc_html_e('Out of stock', 'dakabrand'); ?></span>
        <?php elseif ('onbackorder' === $card_stock_status) : ?>
            <span class="catalog-card__stock"><?php esc_html_e('15 days preorder', 'dakabrand'); ?></span>
        <?php endif; ?>
    </div>

    <a class="catalog-card__details" href="<?php echo esc_url($card_permalink); ?>">
        <h2 class="catalog-card__name"><?php echo esc_html($card_product->get_name()); ?></h2>
        <?php if ($card_categories) : ?>
            <span class="product-card__category screen-reader-text"><?php echo wp_kses_post($card_categories); ?></span>
        <?php endif; ?>
        <?php if ($card_price > 0) : ?>
            <span class="catalog-card__price">
                <?php if ($card_on_sale) : ?>
                    <s><?php echo wp_kses_post(wc_price($card_regular_price)); ?></s>
                    <strong><?php echo wp_kses_post(wc_price($card_sale_price)); ?></strong>
                <?php else : ?>
                    <span><?php echo wp_kses_post(wc_price($card_price)); ?></span>
                <?php endif; ?>
            </span>
        <?php endif; ?>
    </a>
    <?php if ($card_product->is_type('simple')) : ?>
        <script type="application/json" data-staticbridge-product><?php echo wp_json_encode(staticbridge_product_data($card_product), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
    <?php endif; ?>
</article>
