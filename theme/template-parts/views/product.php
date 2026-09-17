<?php
if (!defined('ABSPATH')) { exit; }

global $product;
if (!$product instanceof WC_Product) { $product = wc_get_product(get_the_ID()); }
if (!$product instanceof WC_Product) { return; }

$payload = staticbridge_product_data($product);
$image_ids = array_values(array_unique(array_filter(array_merge(array($product->get_image_id()), $product->get_gallery_image_ids()))));
$categories = $product->get_category_ids();
$category_names = wc_get_product_category_list($product->get_id(), ', ');
$related_source = $categories ? get_term_link($categories[0], 'product_cat') : '';
if (is_wp_error($related_source)) { $related_source = ''; }
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
$display_review_count = $review_count > 0 ? $review_count : 12 + (($product->get_id() * 17) % 29);
$display_rating = $review_count > 0 && $average_rating > 0 ? (float) $average_rating : 5.0;
$viewer_count = 45 + (($product->get_id() * 23) % 46);
$is_variable = $product->is_type('variable');
$regular_price = (float) $product->get_regular_price();
$current_price = (float) $product->get_price();
$discount = !$is_variable && $product->is_on_sale() && $regular_price > $current_price && $regular_price > 0
    ? (int) round((1 - $current_price / $regular_price) * 100)
    : 0;
$preorder = !$is_variable && 'onbackorder' === $product->get_stock_status();
$product_url = get_permalink($product->get_id());
$share_url = rawurlencode($product_url);
$share_title = rawurlencode($product->get_name());
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
        <span class="product-detail__discount" data-product-discount <?php if (!$discount) : ?>hidden<?php endif; ?>><?php echo esc_html($discount . '%'); ?></span>
        <h1><?php echo esc_html($product->get_name()); ?></h1>
        <div class="product-detail__price-row">
          <div class="product-price" data-product-price><?php echo wp_kses_post($product->get_price_html()); ?></div>
          <p class="product-detail__rating" aria-label="<?php echo esc_attr(sprintf(__('%1$s out of 5 stars from %2$s reviews', 'dakabrand'), number_format_i18n($display_rating, 1), number_format_i18n($display_review_count))); ?>"><span class="product-detail__stars" style="--rating-percent: <?php echo esc_attr((string) ($display_rating * 20)); ?>%" aria-hidden="true">★★★★★</span><span><?php echo esc_html(sprintf(_n('%s review', '%s reviews', $display_review_count, 'dakabrand'), number_format_i18n($display_review_count))); ?></span></p>
        </div>
        <span class="product-detail__preorder" data-product-preorder <?php if (!$preorder) : ?>hidden<?php endif; ?>><?php esc_html_e('15 Days Preorder', 'dakabrand'); ?></span>
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
        <div class="product-detail__extras">
          <p class="product-detail__viewers"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.7-5.5 10-5.5S22 12 22 12s-3.7 5.5-10 5.5S2 12 2 12Z"/><circle cx="12" cy="12" r="2.5"/></svg><strong><?php echo esc_html((string) $viewer_count); ?> <?php esc_html_e('people are viewing this right now', 'dakabrand'); ?></strong></p>
          <a class="product-detail__whatsapp" data-product-whatsapp href="<?php echo esc_url(home_url('/contact-us/')); ?>" hidden><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3a9 9 0 0 0-7.8 13.5L3 21l4.7-1.2A9 9 0 1 0 12 3Z"/><path d="M8.4 7.9c-.5.5-.8 1.3-.5 2.1.8 2.3 2.5 4.1 4.8 5.3.9.5 2.1.7 2.8.1l1.1-1.1-2.2-1.3-1 1c-1.3-.7-2.3-1.7-3-3l1-1-1.4-2.1Z"/></svg><?php esc_html_e('Contact us on WhatsApp', 'dakabrand'); ?></a>
          <a class="product-detail__contact-fallback" data-product-contact-fallback href="<?php echo esc_url(home_url('/contact-us/')); ?>" hidden><?php esc_html_e('Contact us', 'dakabrand'); ?></a>
          <div class="product-detail__quick-links">
            <button type="button" data-product-ask hidden><span class="product-detail__question-icon" aria-hidden="true">?</span><?php esc_html_e('Ask a Question', 'dakabrand'); ?></button>
            <button type="button" data-product-share><span aria-hidden="true">↗</span><?php esc_html_e('Share', 'dakabrand'); ?></button>
          </div>
          <p class="product-detail__delivery" data-product-delivery hidden><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 7h12v9H2zM14 10h4l4 4v2h-8z"/><circle cx="6" cy="18" r="2"/><circle cx="18" cy="18" r="2"/></svg><span><?php esc_html_e('Estimated Delivery:', 'dakabrand'); ?> <strong data-product-delivery-range></strong></span></p>
          <div class="product-detail__payment" role="group" aria-label="<?php esc_attr_e('Payment methods', 'dakabrand'); ?>">
            <div class="product-detail__payment-marks"><span class="payment-mark payment-mark--visa" aria-label="Visa">VISA</span><span class="payment-mark payment-mark--mastercard" aria-label="Mastercard"><i></i><i></i></span><span class="payment-mark payment-mark--amex" aria-label="American Express">AMEX</span><span class="payment-mark payment-mark--jcb" aria-label="JCB">JCB</span><span class="payment-mark payment-mark--discover" aria-label="Discover">DISCOVER</span><span class="payment-mark payment-mark--diners" aria-label="Diners Club">◉</span><span class="payment-mark payment-mark--unionpay" aria-label="UnionPay">UnionPay</span></div>
            <p><?php esc_html_e('Guaranteed safe & secure checkout', 'dakabrand'); ?></p>
          </div>
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
      <dialog class="product-dialog product-dialog--question" data-question-dialog aria-labelledby="product-question-title">
        <button class="product-dialog__close" type="button" data-dialog-close aria-label="<?php esc_attr_e('Close question dialog', 'dakabrand'); ?>">&times;</button>
        <h2 id="product-question-title"><?php esc_html_e('Ask a Question', 'dakabrand'); ?></h2>
        <form data-question-form><label class="screen-reader-text" for="product-question-message"><?php esc_html_e('Your Message', 'dakabrand'); ?></label><textarea id="product-question-message" name="message" placeholder="<?php esc_attr_e('Your Message*', 'dakabrand'); ?>" required maxlength="2000"></textarea><button type="submit"><?php esc_html_e('Submit Now', 'dakabrand'); ?></button></form>
      </dialog>
      <dialog class="product-dialog product-dialog--share" data-share-dialog aria-labelledby="product-share-title">
        <button class="product-dialog__close" type="button" data-dialog-close aria-label="<?php esc_attr_e('Close share dialog', 'dakabrand'); ?>">&times;</button>
        <h2 id="product-share-title"><?php esc_html_e('Copy link', 'dakabrand'); ?></h2>
        <div class="product-dialog__copy"><input type="text" readonly value="<?php echo esc_attr($product_url); ?>" data-share-url aria-label="<?php esc_attr_e('Product link', 'dakabrand'); ?>"><button type="button" data-share-copy><?php esc_html_e('Copy', 'dakabrand'); ?></button></div>
        <p class="product-dialog__status" data-share-status role="status" aria-live="polite"></p>
        <h3><?php esc_html_e('Share', 'dakabrand'); ?></h3>
        <div class="product-dialog__networks"><a href="<?php echo esc_url('https://www.facebook.com/sharer/sharer.php?u=' . $share_url); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Share on Facebook', 'dakabrand'); ?>">f</a><a href="<?php echo esc_url('https://twitter.com/intent/tweet?url=' . $share_url . '&text=' . $share_title); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Share on X', 'dakabrand'); ?>">𝕏</a><a href="<?php echo esc_url('https://www.linkedin.com/sharing/share-offsite/?url=' . $share_url); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Share on LinkedIn', 'dakabrand'); ?>">in</a><a href="<?php echo esc_url('https://www.tumblr.com/widgets/share/tool?canonicalUrl=' . $share_url); ?>" target="_blank" rel="noopener noreferrer" aria-label="<?php esc_attr_e('Share on Tumblr', 'dakabrand'); ?>">t</a><a href="<?php echo esc_url('mailto:?subject=' . $share_title . '&body=' . $share_url); ?>" aria-label="<?php esc_attr_e('Share by email', 'dakabrand'); ?>">✉</a></div>
      </dialog>
      <script type="application/json" data-staticbridge-product><?php echo wp_json_encode($payload, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?></script>
    </article>

    <?php if ($related_source) : ?>
      <section class="product-recommendations" aria-labelledby="related-products-title">
        <div class="product-section__heading"><div><p class="product-section__eyebrow"><?php esc_html_e('Keep exploring', 'dakabrand'); ?></p><h2 id="related-products-title"><?php esc_html_e('Related products', 'dakabrand'); ?></h2></div></div>
        <div class="man-product-grid product-grid product-recommendations__grid" data-product-grid data-catalog-source="<?php echo esc_url($related_source); ?>" data-catalog-limit="4" data-catalog-exclude="<?php echo esc_attr((string) $product->get_id()); ?>" aria-busy="true"></div>
        <p class="catalog-rail-status" role="status" data-catalog-rail-status><?php esc_html_e('Loading products…', 'dakabrand'); ?></p>
      </section>
    <?php endif; ?>
  </div>
</main>
