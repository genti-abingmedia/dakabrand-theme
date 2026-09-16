<?php
if (!defined('ABSPATH')) {
    exit;
}

$is_category = is_tax('product_cat');
$archive_title = $is_category ? single_term_title('', false) : __('Shop', 'dakabrand');
$archive_description = $is_category ? term_description() : '';
?>
<main id="main" class="site-main site-shell catalog" data-static-view="product-archive" data-catalog>
    <header class="catalog-heading">
        <p class="catalog-heading__eyebrow"><?php esc_html_e('DakaBrand collection', 'dakabrand'); ?></p>
        <h1 data-catalog-heading data-default-heading="<?php echo esc_attr($archive_title); ?>"><?php echo esc_html($archive_title); ?></h1>
        <?php if ($archive_description) : ?>
            <div class="catalog-heading__description"><?php echo wp_kses_post($archive_description); ?></div>
        <?php endif; ?>
    </header>

    <div class="catalog-toolbar" data-catalog-toolbar hidden>
        <div class="catalog-toolbar__summary">
            <button class="catalog-filter-toggle" type="button" data-catalog-open-filters aria-controls="catalog-filters" aria-expanded="false">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M6 12h12M9 18h6"/></svg>
                <?php esc_html_e('Filters', 'dakabrand'); ?>
            </button>
            <p class="catalog-count" data-catalog-count aria-live="polite"></p>
        </div>
        <label class="catalog-sort">
            <span><?php esc_html_e('Sort by', 'dakabrand'); ?></span>
            <select data-catalog-sort>
                <option value=""><?php esc_html_e('Latest', 'dakabrand'); ?></option>
                <option value="price-asc"><?php esc_html_e('Price: low to high', 'dakabrand'); ?></option>
                <option value="price-desc"><?php esc_html_e('Price: high to low', 'dakabrand'); ?></option>
            </select>
        </label>
    </div>

    <div class="catalog-active" data-catalog-active hidden></div>
    <div class="catalog-layout">
        <div class="catalog-backdrop" data-catalog-backdrop hidden></div>
        <aside class="catalog-filters" id="catalog-filters" data-catalog-filters aria-label="<?php esc_attr_e('Product filters', 'dakabrand'); ?>" tabindex="-1">
            <div class="catalog-filters__header">
                <h2><?php esc_html_e('Filter', 'dakabrand'); ?></h2>
                <button type="button" data-catalog-close-filters aria-label="<?php esc_attr_e('Close filters', 'dakabrand'); ?>">&times;</button>
            </div>
            <div data-catalog-facets></div>
            <div class="catalog-filters__footer">
                <button type="button" data-catalog-clear><?php esc_html_e('Clear all', 'dakabrand'); ?></button>
                <button type="button" data-catalog-close-filters><?php esc_html_e('Show products', 'dakabrand'); ?></button>
            </div>
        </aside>

        <section class="catalog-results" aria-label="<?php esc_attr_e('Products', 'dakabrand'); ?>">
            <div class="catalog-status" data-catalog-status role="status" aria-live="polite">
                <span class="catalog-status__loader" aria-hidden="true"></span>
                <?php esc_html_e('Loading products…', 'dakabrand'); ?>
            </div>
            <div class="catalog-grid" data-catalog-grid aria-busy="true"></div>
            <nav class="catalog-pagination" data-catalog-pagination aria-label="<?php esc_attr_e('Product pages', 'dakabrand'); ?>" hidden></nav>
        </section>
    </div>
    <noscript><p class="catalog-noscript"><?php esc_html_e('Enable JavaScript to browse this catalog.', 'dakabrand'); ?> <a href="https://dakabrand.uk/shop/"><?php esc_html_e('Shop at DakaBrand', 'dakabrand'); ?></a></p></noscript>
</main>
