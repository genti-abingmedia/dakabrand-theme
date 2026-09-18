<?php
if (!defined('ABSPATH')) {
    exit;
}

$maps_query = 'Rruga Muhamet Gjollesha Pallati 18, Tiranë, Albania';
$maps_url = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($maps_query);
?>
<main id="main" class="site-main site-shell information-page information-page--contact" data-static-view="contact">
    <header class="information-page__header">
        <h1><?php esc_html_e('Contact Us', 'dakabrand'); ?></h1>
    </header>

    <div class="contact-page__map">
        <iframe title="<?php esc_attr_e('Map showing Daka Brand in Tiranë', 'dakabrand'); ?>" src="<?php echo esc_url('https://maps.google.com/maps?q=' . rawurlencode($maps_query) . '&output=embed'); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        <div class="contact-page__map-label" aria-hidden="true"><strong>Daka Brand</strong><span>Rruga Muhamet Gjollesha Pallati 18, Tiranë</span></div>
    </div>

    <div class="contact-page__layout">
        <section class="contact-page__message" aria-labelledby="contact-heading">
            <h2 id="contact-heading"><?php esc_html_e('Get in touch with us', 'dakabrand'); ?></h2>
            <form class="contact-form" data-contact-form novalidate>
                <div class="contact-form__row">
                    <div class="contact-form__field">
                        <label for="contact-name"><?php esc_html_e('Name', 'dakabrand'); ?> <span aria-hidden="true">*</span></label>
                        <input id="contact-name" name="name" type="text" autocomplete="name" required maxlength="120">
                    </div>
                    <div class="contact-form__field">
                        <label for="contact-email"><?php esc_html_e('Email', 'dakabrand'); ?> <span aria-hidden="true">*</span></label>
                        <input id="contact-email" name="email" type="email" autocomplete="email" required maxlength="254">
                    </div>
                </div>
                <div class="contact-form__field">
                    <label for="contact-message"><?php esc_html_e('Comment or message', 'dakabrand'); ?> <span aria-hidden="true">*</span></label>
                    <textarea id="contact-message" name="message" rows="6" required maxlength="5000"></textarea>
                </div>
                <button type="submit" disabled><?php esc_html_e('Submit now', 'dakabrand'); ?></button>
                <p class="contact-form__status" data-contact-status role="status" aria-live="polite"></p>
                <noscript><p><?php esc_html_e('The contact form needs JavaScript. Please use the contact details on this page.', 'dakabrand'); ?></p></noscript>
            </form>
        </section>

        <aside class="contact-page__details" aria-label="<?php esc_attr_e('Contact details', 'dakabrand'); ?>">
            <div>
                <h2><?php esc_html_e('Address', 'dakabrand'); ?></h2>
                <address>Rruga Muhamet Gjollesha Pallati 18<br>Tiranë, Albania</address>
                <a href="<?php echo esc_url($maps_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('See us on the map', 'dakabrand'); ?> <span aria-hidden="true">↗</span></a>
            </div>
            <div>
                <h2><?php esc_html_e('Information', 'dakabrand'); ?></h2>
                <p>+391 (0)35 2568 4593</p>
                <p>info@dakabrand.uk</p>
            </div>
        </aside>
    </div>
</main>
