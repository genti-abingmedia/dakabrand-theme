<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<main id="main" class="site-main site-shell" data-static-view="404">
    <h1><?php esc_html_e('Page not found', 'dakabrand'); ?></h1>
    <p><?php esc_html_e('The requested page could not be found.', 'dakabrand'); ?></p>
    <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Return home', 'dakabrand'); ?></a>
</main>
