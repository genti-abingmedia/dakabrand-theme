<?php
if (!defined('ABSPATH')) { exit; }

global $product;
if (!$product instanceof WC_Product) { $product = wc_get_product(get_the_ID()); }
if (!$product instanceof WC_Product) { return; }

$payload = staticbridge_product_data($product);
$image_ids = array_values(array_unique(array_filter(array_merge(array($product->get_image_id()), $product->get_gallery_image_ids()))));
$categories = $product->get_category_ids();
$category_names = wc_get_product_category_list($product->get_id(), ', ');
$related = $categories ? new WP_Query(array(
    'post_type' => 'product', 'post_status' => 'publish', 'posts_per_page' => 4,
    'post__not_in' => array($product->get_id()), 'ignore_sticky_posts' => true,
    'tax_query' => array(array('taxonomy' => 'product_cat', 'field' => 'term_id', 'terms' => $categories)),
)) : null;
$policy_pages = array();
foreach (array('shipping-policy', 'delivery', 'refund-policy', 'returns') as $slug) {
    $page = get_page_by_path($slug);
    if ($page instanceof WP_Post && 'publish' === $page->post_status) { $policy_pages[$page->ID] = $page; }
}
$visible_attributes = array_filter($product->get_attributes(), static function ($attribute) {
    return $attribute instanceof WC_Product_Attribute && $attribute->get_visible();
});
$review_count = $product->get_review_count();
$average_rating = $product->get_average_rating();
?>
<main id="main" class="site-main product-page" data-static-view="product">
  <div class="site-shell">
    <nav class="product-breadcrumb" aria-label="<?php esc_attr_e('Breadcrumb', 'dakabrand'); ?>">
      <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'dakabrand'); ?></a><span aria-hidden="true">/</span>
      <a href="<?php echo esc_url(wc_get_page_permalink('shop')); ?>"><?php esc_html_e('Shop', 'dakabrand'); ?></a><span aria-hidden="true">/</span>
      <span aria-current="page"><?php echo esc_html($product->get_name()); ?></span>
    </nav>
    <article <?php wc_product_class('product-detail', $product); ?> data-product-id="<?php echo esc_attr((string) $product->get_id()); ?>" data-product-type="<?php echo esc_attr($product->get_type()); ?>">
      <section class="product-detail__media" aria-label="<?php esc_attr_e('Product images', 'dakabrand'); ?>" data-product-gallery>
        <div class="product-gallery__stage" <?php if (count($image_ids) > 1) : ?>tabindex="0"<?php endif; ?> aria-label="<?php esc_attr_e('Product image gallery', 'dakabrand'); ?>">
          <?php if ($image_ids) : ?>
            <?php echo wp_get_attachment_image($image_ids[0], 'woocommerce_single', false, array('class' => 'product-gallery__main', 'data-gallery-main' => '', 'fetchpriority' => 'high', 'decoding' => 'sync', 'alt' => $product->get_name())); ?>
          <?php else : ?>
            <img class="product-gallery__main" data-gallery-main src="<?php echo esc_url(wc_placeholder_img_src()); ?>" alt="<?php echo esc_attr($product->get_name()); ?>">
          <?php endif; ?>
          <?php if (count($image_ids) > 1) : ?>
            <button type="button" class="product-gallery__arrow product-gallery__arrow--prev" data-gallery-prev aria-label="<?php esc_attr_e('Previous image', 'dakabrand'); ?>"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m14.5 5-7 7 7 7"/></svg></button>
            <button type="button" class="product-gallery__arrow product-gallery__arrow--next" data-gallery-next aria-label="<?php esc_attr_e('Next image', 'dakabrand'); ?>"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m9.5 5 7 7-7 7"/></svg></button>
            <span class="product-gallery__counter" data-gallery-counter aria-live="polite">1 / <?php echo esc_html((string) count($image_ids)); ?></span>
          <?php endif; ?>
          <?php if ($image_ids) : ?>
            <button type="button" class="product-gallery__expand" data-gallery-open aria-label="<?php esc_attr_e('View image fullscreen', 'dakabrand'); ?>"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 3H3v5m13-5h5v5M3 16v5h5m13-5v5h-5"/></svg></button>
          <?php endif; ?>
        </div>
        <?php if (count($image_ids) > 1) : ?>
          <div class="product-gallery__thumbs" aria-label="<?php esc_attr_e('Choose product image', 'dakabrand'); ?>">
            <?php foreach ($image_ids as $index => $image_id) : ?>
              <button type="button" class="product-gallery__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>" data-gallery-thumb
                data-image-src="<?php echo esc_url(wp_get_attachment_image_url($image_id, 'woocommerce_single')); ?>"
                data-image-srcset="<?php echo esc_attr((string) wp_get_attachment_image_srcset($image_id, 'woocommerce_single')); ?>"
                data-image-alt="<?php echo esc_attr($product->get_name() . ' — view ' . ($index + 1)); ?>"
                aria-label="<?php echo esc_attr(sprintf(__('Show image %1$d of %2$d', 'dakabrand'), $index + 1, count($image_ids))); ?>"
                aria-pressed="<?php echo 0 === $index ? 'true' : 'false'; ?>">
                <?php echo wp_get_attachment_image($image_id, 'woocommerce_thumbnail', false, array('alt' => '', 'loading' => 'lazy')); ?>
              </button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <?php if ($image_ids) : ?>
          <div class="product-lightbox" data-product-lightbox hidden role="dialog" aria-modal="true" aria-label="<?php esc_attr_e('Fullscreen product images', 'dakabrand'); ?>">
            <button type="button" class="product-lightbox__close" data-lightbox-close aria-label="<?php esc_attr_e('Close fullscreen image viewer', 'dakabrand'); ?>"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m5 5 14 14M19 5 5 19"/></svg></button>
            <?php if (count($image_ids) > 1) : ?>
              <button type="button" class="product-lightbox__arrow product-lightbox__arrow--prev" data-lightbox-prev aria-label="<?php esc_attr_e('Previous image', 'dakabrand'); ?>"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m14.5 5-7 7 7 7"/></svg></button>
              <button type="button" class="product-lightbox__arrow product-lightbox__arrow--next" data-lightbox-next aria-label="<?php esc_attr_e('Next image', 'dakabrand'); ?>"><svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="m9.5 5 7 7-7 7"/></svg></button>
            <?php endif; ?>
            <img class="product-lightbox__image" data-lightbox-main src="<?php echo esc_url(wp_get_attachment_image_url($image_ids[0], 'full')); ?>" alt="<?php echo esc_attr($product->get_name()); ?>">
            <?php if (count($image_ids) > 1) : ?>
              <div class="product-lightbox__thumbs" aria-label="<?php esc_attr_e('Choose product image', 'dakabrand'); ?>">
                <?php foreach ($image_ids as $index => $image_id) : ?>
                  <button type="button" class="product-lightbox__thumb<?php echo 0 === $index ? ' is-active' : ''; ?>" data-lightbox-thumb data-image-src="<?php echo esc_url(wp_get_attachment_image_url($image_id, 'full')); ?>" data-image-srcset="<?php echo esc_attr((string) wp_get_attachment_image_srcset($image_id, 'full')); ?>" data-image-alt="<?php echo esc_attr($product->get_name() . ' — view ' . ($index + 1)); ?>" aria-label="<?php echo esc_attr(sprintf(__('Show image %1$d of %2$d', 'dakabrand'), $index + 1, count($image_ids))); ?>" aria-pressed="<?php echo 0 === $index ? 'true' : 'false'; ?>"><?php echo wp_get_attachment_image($image_id, 'woocommerce_thumbnail', false, array('alt' => '', 'loading' => 'lazy')); ?></button>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </section>
      <div class="product-detail__summary">
        <?php if ($category_names) : ?><p class="product-detail__category"><?php echo wp_kses_post($category_names); ?></p><?php endif; ?>
        <h1><?php echo esc_html($product->get_name()); ?></h1>
        <?php if ($review_count && $average_rating) : ?>
          <p class="product-detail__rating" aria-label="<?php echo esc_attr(sprintf(_n('%1$s out of 5 stars from %2$s review', '%1$s out of 5 stars from %2$s reviews', $review_count, 'dakabrand'), $average_rating, $review_count)); ?>"><span aria-hidden="true">&#9733;</span> <?php echo esc_html($average_rating); ?> <span><?php echo esc_html(sprintf(_n('(%s review)', '(%s reviews)', $review_count, 'dakabrand'), number_format_i18n($review_count))); ?></span></p>
        <?php endif; ?>
        <div class="product-price" data-product-price><?php echo wp_kses_post($product->get_price_html()); ?></div>
        <p class="product-detail__availability" data-product-availability aria-live="polite"><?php echo esc_html($product->is_in_stock() ? __('Available', 'dakabrand') : __('Out of stock', 'dakabrand')); ?></p>
        <?php if ($product->get_short_description()) : ?><div class="product-summary"><?php echo wp_kses_post(wpautop($product->get_short_description())); ?></div><?php endif; ?>
        <div data-product-options></div>
        <div class="product-purchase">
          <div class="product-quantity" data-product-quantity-control>
            <span id="product-quantity-label"><?php esc_html_e('Quantity', 'dakabrand'); ?></span>
            <button type="button" data-product-quantity-decrease aria-label="<?php esc_attr_e('Decrease quantity', 'dakabrand'); ?>">&#8722;</button>
            <input type="number" inputmode="numeric" min="1" step="1" value="1" data-product-quantity aria-labelledby="product-quantity-label">
            <button type="button" data-product-quantity-increase aria-label="<?php esc_attr_e('Increase quantity', 'dakabrand'); ?>">+</button>
          </div>
          <button type="button" class="add-to-cart-button" data-add-to-cart data-product-id="<?php echo esc_attr((string) $product->get_id()); ?>" <?php disabled(!$product->is_purchasable()); ?>><?php esc_html_e('Add to cart', 'dakabrand'); ?></button>
        </div>
        <?php if ($product->get_sku()) : ?><p class="product-detail__sku"><?php esc_html_e('SKU', 'dakabrand'); ?> <span><?php echo esc_html($product->get_sku()); ?></span></p><?php endif; ?>
        <div class="product-detail__accordions">
          <details class="product-detail__accordion">
            <summary><?php esc_html_e('Product details', 'dakabrand'); ?></summary>
            <div class="product-detail__accordion-content">
              <?php if ($product->get_description()) : ?><?php echo wp_kses_post(apply_filters('the_content', $product->get_description())); ?><?php else : ?><p><?php esc_html_e('More product details are coming soon.', 'dakabrand'); ?></p><?php endif; ?>
              <?php if ($visible_attributes) : ?>
                <dl class="product-attributes">
                  <?php foreach ($visible_attributes as $attribute) : ?>
                    <div><dt><?php echo esc_html(wc_attribute_label($attribute->get_name())); ?></dt><dd><?php echo wp_kses_post($product->get_attribute($attribute->get_name())); ?></dd></div>
                  <?php endforeach; ?>
                </dl>
              <?php endif; ?>
            </div>
          </details>
          <details class="product-detail__accordion">
            <summary><?php esc_html_e('Delivery & returns', 'dakabrand'); ?></summary>
            <div class="product-detail__accordion-content">
              <?php if ($policy_pages) : ?>
                <p><?php esc_html_e('See our current store policies for delivery and returns information.', 'dakabrand'); ?></p>
                <?php foreach ($policy_pages as $policy_page) : ?><a class="product-detail__policy-link" href="<?php echo esc_url(get_permalink($policy_page)); ?>"><?php echo esc_html(get_the_title($policy_page)); ?> <span aria-hidden="true">&#8599;</span></a><?php endforeach; ?>
              <?php else : ?><p><?php esc_html_e('Delivery and returns details are not available here yet. Please contact the store before ordering.', 'dakabrand'); ?></p><?php endif; ?>
            </div>
          </details>
        </div>
      </div>
      <script type="application/json" data-staticbridge-product><?php echo wp_json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
    </article>

    <?php if ($related instanceof WP_Query && $related->have_posts()) : ?>
      <section class="product-recommendations" aria-labelledby="related-products-title">
        <div class="product-section__heading"><div><p class="product-section__eyebrow"><?php esc_html_e('Keep exploring', 'dakabrand'); ?></p><h2 id="related-products-title"><?php esc_html_e('Related products', 'dakabrand'); ?></h2></div></div>
        <div class="product-grid product-recommendations__grid">
          <?php while ($related->have_posts()) : $related->the_post(); get_template_part('template-parts/components/product-card'); endwhile; ?>
        </div>
      </section>
      <?php wp_reset_postdata(); ?>
    <?php endif; ?>
    <section class="product-recommendations product-recommendations--recent" data-recent-products hidden aria-labelledby="recent-products-title">
      <div class="product-section__heading"><div><p class="product-section__eyebrow"><?php esc_html_e('Continue browsing', 'dakabrand'); ?></p><h2 id="recent-products-title"><?php esc_html_e('Recently viewed', 'dakabrand'); ?></h2></div></div>
      <div class="product-grid product-recommendations__grid" data-recent-products-list></div>
    </section>
  </div>
</main>
