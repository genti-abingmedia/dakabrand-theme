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
            <button type="button" class="product-gallery__arrow product-gallery__arrow--prev" data-gallery-prev aria-label="<?php esc_attr_e('Previous image', 'dakabrand'); ?>">&#8592;</button>
            <button type="button" class="product-gallery__arrow product-gallery__arrow--next" data-gallery-next aria-label="<?php esc_attr_e('Next image', 'dakabrand'); ?>">&#8594;</button>
            <span class="product-gallery__counter" data-gallery-counter aria-live="polite">1 / <?php echo esc_html((string) count($image_ids)); ?></span>
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
      </section>
      <div class="product-detail__summary">
        <?php if ($category_names) : ?><p class="product-detail__category"><?php echo wp_kses_post($category_names); ?></p><?php endif; ?>
        <h1><?php echo esc_html($product->get_name()); ?></h1>
        <div class="product-price" data-product-price><?php echo wp_kses_post($product->get_price_html()); ?></div>
        <p class="product-detail__availability"><?php echo esc_html($product->is_in_stock() ? __('Available', 'dakabrand') : __('Out of stock', 'dakabrand')); ?></p>
        <?php if ($product->get_short_description()) : ?><div class="product-summary"><?php echo wp_kses_post(wpautop($product->get_short_description())); ?></div><?php endif; ?>
        <div data-product-options></div>
        <button type="button" class="add-to-cart-button" data-add-to-cart data-product-id="<?php echo esc_attr((string) $product->get_id()); ?>" <?php disabled(!$product->is_purchasable()); ?>><?php esc_html_e('Add to cart', 'dakabrand'); ?></button>
        <?php if ($product->get_sku()) : ?><p class="product-detail__sku"><?php esc_html_e('SKU', 'dakabrand'); ?> <span><?php echo esc_html($product->get_sku()); ?></span></p><?php endif; ?>
      </div>
      <script type="application/json" data-staticbridge-product><?php echo wp_json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
    </article>

    <div class="product-detail__lower">
      <section class="product-detail__description" aria-labelledby="product-details-title">
        <p class="product-section__eyebrow"><?php esc_html_e('The details', 'dakabrand'); ?></p><h2 id="product-details-title"><?php esc_html_e('Product details', 'dakabrand'); ?></h2>
        <?php if ($product->get_description()) : ?><?php echo wp_kses_post(apply_filters('the_content', $product->get_description())); ?><?php else : ?><p><?php esc_html_e('More product details are coming soon.', 'dakabrand'); ?></p><?php endif; ?>
      </section>
      <section class="product-detail__delivery" aria-labelledby="product-delivery-title">
        <p class="product-section__eyebrow"><?php esc_html_e('Good to know', 'dakabrand'); ?></p><h2 id="product-delivery-title"><?php esc_html_e('Delivery & returns', 'dakabrand'); ?></h2>
        <?php if ($policy_pages) : ?>
          <p><?php esc_html_e('See our current store policies for delivery and returns information.', 'dakabrand'); ?></p>
          <?php foreach ($policy_pages as $policy_page) : ?><a class="product-detail__policy-link" href="<?php echo esc_url(get_permalink($policy_page)); ?>"><?php echo esc_html(get_the_title($policy_page)); ?> <span aria-hidden="true">&#8599;</span></a><?php endforeach; ?>
        <?php else : ?><p><?php esc_html_e('Delivery and returns details are not available here yet. Please contact the store before ordering.', 'dakabrand'); ?></p><?php endif; ?>
      </section>
    </div>

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
