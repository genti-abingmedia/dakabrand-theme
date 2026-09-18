(function () {
    'use strict';

    var config = window.StaticBridgeContact || {};
    var messages = config.messages || {};

    document.querySelectorAll('[data-contact-form]').forEach(function (form) {
        var submit = form.querySelector('button[type="submit"]');
        var status = form.querySelector('[data-contact-status]');

        if (!submit || !status) return;

        // A failed or disabled script leaves the HTML control inert.
        submit.disabled = false;

        form.addEventListener('submit', async function (event) {
            var endpoint = typeof config.endpoint === 'string' ? config.endpoint.trim() : '';
            var response;
            var payload;

            event.preventDefault();
            status.textContent = '';
            status.classList.remove('is-error');

            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            if (!endpoint) {
                status.textContent = messages.unavailable || 'Online messages are temporarily unavailable.';
                status.classList.add('is-error');
                return;
            }

            submit.disabled = true;
            form.setAttribute('aria-busy', 'true');

            try {
                response = await fetch(endpoint, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        name: form.elements.name.value.trim(),
                        email: form.elements.email.value.trim(),
                        message: form.elements.message.value.trim()
                    })
                });
                payload = await response.json().catch(function () { return {}; });

                if (!response.ok) {
                    throw new Error(payload.message || messages.error || 'We could not send your message.');
                }

                form.reset();
                status.textContent = payload.message || messages.success || 'Thank you. Your message has been sent.';
            } catch (error) {
                status.textContent = error.message || messages.error || 'We could not send your message.';
                status.classList.add('is-error');
            } finally {
                submit.disabled = false;
                form.removeAttribute('aria-busy');
            }
        });
    });
}());
