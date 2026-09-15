<?php
if (!defined('ABSPATH')) {
    exit;
}

$card_product = wc_get_product(get_the_ID());

if (!$card_product instanceof WC_Product || !$card_product->is_visible()) {
    return;
}
?>
<article
    class="product-card"
    data-product-card
    data-product-id="<?php echo esc_attr((string) $card_product->get_id()); ?>"
    data-product-type="<?php echo esc_attr($card_product->get_type()); ?>"
>
    <a class="product-card__link" href="<?php echo esc_url(get_permalink($card_product->get_id())); ?>">
        <?php echo wp_kses_post($card_product->get_image('woocommerce_thumbnail')); ?>
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
</article>
