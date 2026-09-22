<?php
if (!defined('ABSPATH')) {
    exit;
}

$is_category = is_tax('product_cat');
$archive_title = $is_category ? single_term_title('', false) : __('Shop', 'dakabrand');
$archive_description = $is_category ? term_description() : '';
$shop_url = home_url('/shop/');
$breadcrumb_terms = [];

if ($is_category) {
    $current_term = get_queried_object();
    if ($current_term instanceof WP_Term) {
        foreach (array_reverse(get_ancestors($current_term->term_id, 'product_cat', 'taxonomy')) as $ancestor_id) {
            $ancestor = get_term($ancestor_id, 'product_cat');
            if ($ancestor instanceof WP_Term) {
                $breadcrumb_terms[] = $ancestor;
            }
        }
    }
}

$back_url = $is_category ? $shop_url : home_url('/');
$archive_page = max(1, (int) get_query_var('paged'), (int) get_query_var('page'));
$archive_query = $GLOBALS['wp_query'] ?? null;
$archive_total_pages = $archive_query instanceof WP_Query ? max(1, (int) $archive_query->max_num_pages) : 1;
if ($breadcrumb_terms) {
    $parent_url = get_term_link(end($breadcrumb_terms));
    if (!is_wp_error($parent_url)) {
        $back_url = $parent_url;
    }
}
?>
<main id="main" class="site-main site-shell catalog catalog--loading" data-static-view="product-archive" data-catalog aria-busy="true">
    <header class="catalog-heading">
        <nav class="catalog-breadcrumb" aria-label="<?php esc_attr_e('Breadcrumb', 'dakabrand'); ?>">
            <a class="catalog-breadcrumb__back" href="<?php echo esc_url($back_url); ?>"><?php esc_html_e('< Back', 'dakabrand'); ?></a>
            <?php if ($is_category) : ?>
                <?php if (!$breadcrumb_terms) : ?>
                    <a href="<?php echo esc_url($shop_url); ?>"><?php esc_html_e('Shop', 'dakabrand'); ?></a>
                <?php endif; ?>
                <?php foreach ($breadcrumb_terms as $ancestor) : ?>
                    <?php $ancestor_url = get_term_link($ancestor); ?>
                    <?php if (!is_wp_error($ancestor_url)) : ?>
                        <a href="<?php echo esc_url($ancestor_url); ?>"><?php echo esc_html($ancestor->name); ?></a>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
            <span aria-current="page"><?php echo esc_html($archive_title); ?></span>
        </nav>
        <h1 data-catalog-heading data-default-heading="<?php echo esc_attr($archive_title); ?>"><?php echo esc_html($archive_title); ?></h1>
        <?php if ($archive_description) : ?>
            <div class="catalog-heading__description"><?php echo wp_kses_post($archive_description); ?></div>
        <?php endif; ?>
    </header>

    <div class="catalog-loading" data-catalog-loading role="status" aria-live="polite">
        <span class="screen-reader-text"><?php esc_html_e('Loading products and filters…', 'dakabrand'); ?></span>
        <div class="catalog-loading__toolbar" aria-hidden="true">
            <span class="catalog-skeleton catalog-skeleton--label"></span>
            <span class="catalog-skeleton catalog-skeleton--controls"></span>
        </div>
        <div class="catalog-loading__layout" aria-hidden="true">
            <aside class="catalog-loading__filters">
                <span class="catalog-skeleton catalog-skeleton--filter-title"></span>
                <span class="catalog-skeleton catalog-skeleton--filter-option"></span>
                <span class="catalog-skeleton catalog-skeleton--filter-option"></span>
                <span class="catalog-skeleton catalog-skeleton--filter-title"></span>
                <span class="catalog-skeleton catalog-skeleton--filter-option"></span>
                <span class="catalog-skeleton catalog-skeleton--filter-option"></span>
            </aside>
            <div class="catalog-loading__grid">
                <?php for ($skeleton_card = 0; $skeleton_card < 8; $skeleton_card++) : ?>
                    <div class="catalog-loading__card">
                        <span class="catalog-skeleton catalog-skeleton--image"></span>
                        <span class="catalog-skeleton catalog-skeleton--name"></span>
                        <span class="catalog-skeleton catalog-skeleton--price"></span>
                    </div>
                <?php endfor; ?>
            </div>
        </div>
    </div>

    <div class="catalog-toolbar" data-catalog-toolbar hidden>
        <div class="catalog-toolbar__summary">
            <button class="catalog-filter-toggle" type="button" data-catalog-open-filters aria-controls="catalog-filters" aria-expanded="false">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18M6 12h12M9 18h6"/></svg>
                <?php esc_html_e('Filters', 'dakabrand'); ?>
            </button>
            <div class="catalog-filters__header">
                <h2><?php esc_html_e('Filter', 'dakabrand'); ?></h2>
            </div>
        </div>
        <div class="catalog-toolbar__views" aria-label="<?php esc_attr_e('Catalog display options', 'dakabrand'); ?>">
            <button class="catalog-view-button" type="button" data-catalog-toggle-filters aria-pressed="true" title="<?php esc_attr_e('Hide filters', 'dakabrand'); ?>">
                <span class="screen-reader-text" data-catalog-toggle-filters-label><?php esc_html_e('Hide filters', 'dakabrand'); ?></span>
                <span class="catalog-filter-icon" aria-hidden="true"></span>
            </button>
            <div class="catalog-view-buttons" role="group" aria-label="<?php esc_attr_e('Product layout', 'dakabrand'); ?>">
                <button class="catalog-view-button" type="button" data-catalog-view="large" aria-pressed="false" title="<?php esc_attr_e('3 columns, 15 products per page', 'dakabrand'); ?>">
                    <span class="screen-reader-text"><?php esc_html_e('3 columns, 15 products per page', 'dakabrand'); ?></span>
                    <svg viewBox="0 0 16 16" aria-hidden="true"><rect x="1" y="2" width="3" height="12"/><rect x="6" y="2" width="3" height="12"/><rect x="11" y="2" width="3" height="12"/></svg>
                </button>
                <button class="catalog-view-button" type="button" data-catalog-view="small" aria-pressed="true" title="<?php esc_attr_e('4 columns, 20 products per page', 'dakabrand'); ?>">
                    <span class="screen-reader-text"><?php esc_html_e('4 columns, 20 products per page', 'dakabrand'); ?></span>
                    <svg viewBox="0 0 16 16" aria-hidden="true"><rect x="0" y="2" width="2" height="12"/><rect x="4" y="2" width="2" height="12"/><rect x="8" y="2" width="2" height="12"/><rect x="12" y="2" width="2" height="12"/></svg>
                </button>
                <button class="catalog-view-button" type="button" data-catalog-view="dense" aria-pressed="false" title="<?php esc_attr_e('5 columns, 25 products per page', 'dakabrand'); ?>">
                    <span class="screen-reader-text"><?php esc_html_e('5 columns, 25 products per page', 'dakabrand'); ?></span>
                    <svg viewBox="0 0 16 16" aria-hidden="true"><rect x="1" y="2" width="2" height="12"/><rect x="4" y="2" width="2" height="12"/><rect x="7" y="2" width="2" height="12"/><rect x="10" y="2" width="2" height="12"/><rect x="13" y="2" width="2" height="12"/></svg>
                </button>
                <button class="catalog-view-button" type="button" data-catalog-view="list" aria-pressed="false" title="<?php esc_attr_e('List view', 'dakabrand'); ?>">
                    <span class="screen-reader-text"><?php esc_html_e('List view', 'dakabrand'); ?></span>
                    <svg viewBox="0 0 16 16" aria-hidden="true"><rect x="1" y="2" width="14" height="2"/><rect x="1" y="7" width="14" height="2"/><rect x="1" y="12" width="14" height="2"/></svg>
                </button>
            </div>
        </div>
        <div class="catalog-active" data-catalog-active hidden></div>
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
        <p class="catalog-count" data-catalog-count aria-live="polite"></p>
    </div>

    <div class="catalog-layout">
        <div class="catalog-backdrop" data-catalog-backdrop hidden></div>
        <aside class="catalog-filters" id="catalog-filters" data-catalog-filters aria-label="<?php esc_attr_e('Product filters', 'dakabrand'); ?>" tabindex="-1">
            <div class="catalog-filters__drawer-header">
                <h2><?php esc_html_e('Filter', 'dakabrand'); ?></h2>
                <button type="button" data-catalog-close-filters aria-label="<?php esc_attr_e('Close filters', 'dakabrand'); ?>">&times;</button>
            </div>
            <div class="catalog-active catalog-active--drawer" data-catalog-active hidden></div>
            <div data-catalog-facets></div>
            <div class="catalog-filters__footer">
                <button type="button" data-catalog-clear><?php esc_html_e('Clear all', 'dakabrand'); ?></button>
                <button type="button" data-catalog-close-filters><?php esc_html_e('Show products', 'dakabrand'); ?></button>
            </div>
        </aside>

        <section class="catalog-results" aria-label="<?php esc_attr_e('Products', 'dakabrand'); ?>">
            <div class="catalog-status" data-catalog-status role="status" aria-live="polite" hidden>
                <?php esc_html_e('Loading products…', 'dakabrand'); ?>
            </div>
            <div class="catalog-grid" data-catalog-grid aria-busy="false">
                <?php if (have_posts()) : ?>
                    <?php while (have_posts()) : the_post(); ?>
                        <?php get_template_part('template-parts/components/product-card'); ?>
                    <?php endwhile; ?>
                    <?php wp_reset_postdata(); ?>
                <?php else : ?>
                    <p><?php esc_html_e('No products found.', 'dakabrand'); ?></p>
                <?php endif; ?>
            </div>
            <?php if ($archive_total_pages > 1) : ?>
                <nav class="catalog-pagination" data-catalog-pagination aria-label="<?php esc_attr_e('Product pages', 'dakabrand'); ?>">
                    <?php
                    echo wp_kses_post(paginate_links(array(
                        'base' => str_replace(999999999, '%#%', esc_url(get_pagenum_link(999999999))),
                        'format' => '?paged=%#%',
                        'current' => $archive_page,
                        'total' => $archive_total_pages,
                        'type' => 'list',
                        'prev_text' => __('Previous', 'dakabrand'),
                        'next_text' => __('Next', 'dakabrand'),
                    )));
                    ?>
                </nav>
            <?php else : ?>
                <nav class="catalog-pagination" data-catalog-pagination aria-label="<?php esc_attr_e('Product pages', 'dakabrand'); ?>" hidden></nav>
            <?php endif; ?>
        </section>
    </div>
</main>
<noscript><style>.catalog--loading .catalog-loading{display:none!important}.catalog--loading .catalog-layout{display:grid!important}</style></noscript>
