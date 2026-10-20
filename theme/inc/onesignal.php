<?php

if (!defined('ABSPATH')) {
    exit;
}

/** Shared by live WordPress pages and generated storefront documents. */
function staticbridge_render_onesignal_head(): void
{
    // Keep the worker on the storefront origin, even when assets use a CDN.
    $worker_directory = rtrim((string) wp_parse_url(get_template_directory_uri(), PHP_URL_PATH), '/') . '/assets/onesignal/';
    $config = array(
        'appId' => 'f792e89f-ecbc-43f2-b487-f538c65467b0',
        'safari_web_id' => 'web.onesignal.auto.01d22b73-bccb-4a84-9633-69c73285f3f4',
        'serviceWorkerPath' => ltrim($worker_directory, '/') . 'OneSignalSDKWorker.js',
        'serviceWorkerParam' => array('scope' => $worker_directory),
        'promptOptions' => array(
            'slidedown' => array(
                'prompts' => array(
                    array(
                        'type' => 'push',
                        'autoPrompt' => true,
                        'text' => array(
                            'actionMessage' => 'Stay In The Fashion Loop - Subscribe To Our Notifications Now',
                            'acceptButton' => 'Yes',
                            'cancelButton' => 'No',
                        ),
                        'delay' => array('pageViews' => 1, 'timeDelay' => 1),
                    ),
                ),
            ),
        ),
    );
    ?>
    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
    <script>
        window.OneSignalDeferred = window.OneSignalDeferred || [];
        window.OneSignalDeferred.push(async function (OneSignal) {
            try {
                await OneSignal.init(<?php echo wp_json_encode($config, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>);
            } catch (error) {
                console.error('OneSignal initialization failed:', error);
            }
        });
    </script>
    <?php
}
