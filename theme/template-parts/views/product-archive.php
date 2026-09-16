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
        <h1 data-catalog-heading data-default-heading="<?php echo esc_attr($archive_title); ?>"><?php echo esc_html($archive_title); ?></h1>
        <nav class="catalog-breadcrumb" aria-label="<?php esc_attr_e('Breadcrumb', 'dakabrand'); ?>">
            <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Home', 'dakabrand'); ?></a>
            <span aria-hidden="true">›</span>
            <a href="<?php echo esc_url(home_url('/shop/')); ?>"><?php esc_html_e('Products', 'dakabrand'); ?></a>
            <?php if ($is_category) : ?>
                <span aria-hidden="true">›</span>
                <span aria-current="page"><?php echo esc_html($archive_title); ?></span>
            <?php endif; ?>
        </nav>
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
        <div class="catalog-toolbar__views" aria-label="<?php esc_attr_e('Catalog display options', 'dakabrand'); ?>">
            <button class="catalog-view-button" type="button" data-catalog-toggle-filters aria-pressed="false" title="<?php esc_attr_e('Hide filters', 'dakabrand'); ?>">
                <span class="screen-reader-text" data-catalog-toggle-filters-label><?php esc_html_e('Hide filters', 'dakabrand'); ?></span>
                <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/icons/sidebar-left-svgrepo-com.svg'); ?>" alt="" aria-hidden="true">
            </button>
            <div class="catalog-view-buttons" role="group" aria-label="<?php esc_attr_e('Product layout', 'dakabrand'); ?>">
                <button class="catalog-view-button" type="button" data-catalog-view="large" aria-pressed="true" title="<?php esc_attr_e('Large grid: 4 columns', 'dakabrand'); ?>">
                    <span class="screen-reader-text"><?php esc_html_e('Large grid', 'dakabrand'); ?></span>
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/icons/grid1-svgrepo-com.svg'); ?>" alt="" aria-hidden="true">
                </button>
                <button class="catalog-view-button" type="button" data-catalog-view="small" aria-pressed="false" title="<?php esc_attr_e('Small grid: 5 columns', 'dakabrand'); ?>">
                    <span class="screen-reader-text"><?php esc_html_e('Small grid', 'dakabrand'); ?></span>
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/icons/grid-svgrepo-com.svg'); ?>" alt="" aria-hidden="true">
                </button>
                <button class="catalog-view-button" type="button" data-catalog-view="list" aria-pressed="false" title="<?php esc_attr_e('List view', 'dakabrand'); ?>">
                    <span class="screen-reader-text"><?php esc_html_e('List view', 'dakabrand'); ?></span>
                    <img src="<?php echo esc_url(get_template_directory_uri() . '/assets/images/icons/list-ul-alt-svgrepo-com.svg'); ?>" alt="" aria-hidden="true">
                </button>
            </div>
        </div>
        <div class="catalog-sort" data-catalog-sort>
            <button class="catalog-sort__trigger" type="button" data-catalog-sort-trigger aria-haspopup="listbox" aria-expanded="false" aria-controls="catalog-sort-options">
                <span class="catalog-sort__label" data-catalog-sort-label><?php esc_html_e('Sort by', 'dakabrand'); ?></span>
                <span class="catalog-sort__value" data-catalog-sort-value><?php esc_html_e('Latest', 'dakabrand'); ?></span>
                <svg viewBox="0 0 16 16" aria-hidden="true"><path d="m4 6 4 4 4-4"/></svg>
            </button>
            <div class="catalog-sort__menu" id="catalog-sort-options" data-catalog-sort-menu role="listbox" aria-label="<?php esc_attr_e('Sort products', 'dakabrand'); ?>" hidden>
                <button type="button" role="option" data-catalog-sort-option data-value="" aria-selected="true"><?php esc_html_e('Latest', 'dakabrand'); ?></button>
                <button type="button" role="option" data-catalog-sort-option data-value="price-asc" aria-selected="false"><?php esc_html_e('Price: low to high', 'dakabrand'); ?></button>
                <button type="button" role="option" data-catalog-sort-option data-value="price-desc" aria-selected="false"><?php esc_html_e('Price: high to low', 'dakabrand'); ?></button>
            </div>
        </div>
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
