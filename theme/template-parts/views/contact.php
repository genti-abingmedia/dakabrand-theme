<?php
if (!defined('ABSPATH')) {
    exit;
}

$maps_query = 'DAKA Brand - Clothing Shop in Tirana, Rruga Muhamet Gjollesha, Tiranë 1001, Albania';
$maps_url = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($maps_query);
$contact_endpoint = trim((string) apply_filters('staticbridge_contact_endpoint', ''));
$contact_form_id = staticbridge_wpforms_form_id('contact');
?>
<main id="main" class="site-main site-shell information-page information-page--contact" data-static-view="contact">
    <header class="information-page__header">
        <h1><?php esc_html_e('Contact Us', 'dakabrand'); ?></h1>
    </header>

    <div class="contact-page__map">
        <iframe title="<?php esc_attr_e('Map showing Daka Brand in Tiranë', 'dakabrand'); ?>" src="<?php echo esc_url('https://maps.google.com/maps?q=' . rawurlencode($maps_query) . '&output=embed'); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
        <div class="contact-page__map-label" aria-hidden="true"><strong>DAKA Brand - Clothing Shop in Tirana</strong><span>Rruga Muhamet Gjollesha, Tiranë 1001, Albania</span></div>
    </div>

    <div class="contact-page__layout">
        <section class="contact-page__message" aria-labelledby="contact-heading">
            <h2 id="contact-heading"><?php esc_html_e('Get in touch with us', 'dakabrand'); ?></h2>
            <?php if ($contact_form_id) : ?>
            <div class="contact-form contact-form--wpforms">
                <?php wpforms_display($contact_form_id, false, false); ?>
            </div>
            <?php elseif ($contact_endpoint) : ?>
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
            <?php else : ?>
            <p role="status"><?php esc_html_e('Online messages are temporarily unavailable. Please use the contact details on this page.', 'dakabrand'); ?></p>
            <?php endif; ?>
        </section>

        <aside class="contact-page__details" aria-label="<?php esc_attr_e('Contact details', 'dakabrand'); ?>">
            <div>
                <h2><?php esc_html_e('Address', 'dakabrand'); ?></h2>
                <address>Rruga Muhamet Gjollesha<br>Tiranë 1001, Albania</address>
                <a href="<?php echo esc_url($maps_url); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e('See us on the map', 'dakabrand'); ?> <span aria-hidden="true">↗</span></a>
            </div>
            <div>
                <h2><?php esc_html_e('Information', 'dakabrand'); ?></h2>
                <p><a href="tel:+355683885286">+355 68 388 5286</a></p>
                <p>info@dakabrand.uk</p>
            </div>
        </aside>
    </div>
</main>
