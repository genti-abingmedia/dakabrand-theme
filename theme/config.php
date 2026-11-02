<?php

if (!defined('ABSPATH')) {
    exit;
}

// Set to true when the Black Friday products are ready for the storefront.
// Regenerate static HTML and purge storefront/filter caches after changing it.
if (!defined('DAKABRAND_SHOW_BLACK_FRIDAY_PRODUCTS')) {
    define('DAKABRAND_SHOW_BLACK_FRIDAY_PRODUCTS', false);
}
