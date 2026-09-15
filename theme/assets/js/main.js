(function () {
    'use strict';

    var config = window.StaticBridgeConfig || {};

    function cartQuantity(cart) {
        var items;

        if (Array.isArray(cart)) {
            items = cart;
        } else if (cart && Array.isArray(cart.items)) {
            items = cart.items;
        } else {
            return 0;
        }

        return items.reduce(function (total, item) {
            var quantity = Number(item && item.quantity);
            return total + (Number.isFinite(quantity) && quantity > 0 ? quantity : 0);
        }, 0);
    }

    function refreshCartCount() {
        var count = 0;

        try {
            count = cartQuantity(JSON.parse(localStorage.getItem(config.cartStorageKey) || '[]'));
        } catch (error) {
            count = 0;
        }

        document.querySelectorAll('[data-cart-count]').forEach(function (element) {
            element.textContent = String(count);
            element.setAttribute('aria-label', count === 1 ? '1 item in cart' : count + ' items in cart');
        });
    }

    function enhanceMobileNavigation() {
        document.querySelectorAll('.mobile-navigation__menu .menu-item-has-children').forEach(function (item, index) {
            var submenu = item.querySelector(':scope > .sub-menu');
            var link = item.querySelector(':scope > a');
            var button;

            if (!submenu || !link) {
                return;
            }

            submenu.id = submenu.id || 'mobile-submenu-' + index;
            button = document.createElement('button');
            button.className = 'submenu-toggle';
            button.type = 'button';
            button.setAttribute('aria-expanded', 'false');
            button.setAttribute('aria-controls', submenu.id);
            button.setAttribute('aria-label', 'Toggle ' + link.textContent.trim() + ' submenu');
            button.addEventListener('click', function () {
                var isOpen = item.classList.toggle('is-open');
                button.setAttribute('aria-expanded', String(isOpen));
            });
            item.insertBefore(button, submenu);
        });
    }

    function enhanceStickyHeader() {
        var header = document.querySelector('[data-component="site-header"]');

        if (!header) {
            return;
        }

        function update() {
            header.classList.toggle('is-scrolled', window.scrollY > 12);
        }

        update();
        window.addEventListener('scroll', update, { passive: true });
    }

    function setNewsletterStatus(status, message, isError) {
        status.textContent = message;
        status.classList.toggle('is-error', Boolean(isError));
    }

    function enhanceNewsletter() {
        document.querySelectorAll('[data-newsletter-form]').forEach(function (form) {
            var status = form.querySelector('[data-newsletter-status]');
            var submit = form.querySelector('button[type="submit"]');
            var email = form.querySelector('input[type="email"]');
            var messages = config.newsletterMessages || {};

            form.addEventListener('submit', async function (event) {
                var response;
                var payload;

                event.preventDefault();
                setNewsletterStatus(status, '', false);

                if (!form.checkValidity()) {
                    form.reportValidity();
                    return;
                }

                if (!config.newsletterEndpoint) {
                    setNewsletterStatus(status, messages.unavailable || 'Newsletter signup is temporarily unavailable.', true);
                    return;
                }

                submit.disabled = true;
                form.setAttribute('aria-busy', 'true');

                try {
                    response = await fetch(config.newsletterEndpoint, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email: email.value.trim() })
                    });
                    payload = await response.json().catch(function () { return {}; });

                    if (!response.ok) {
                        throw new Error(payload.message || messages.error);
                    }

                    form.reset();
                    setNewsletterStatus(status, payload.message || messages.success || 'Thank you for subscribing.', false);
                } catch (error) {
                    setNewsletterStatus(status, error.message || messages.error || 'We could not complete your signup.', true);
                } finally {
                    submit.disabled = false;
                    form.removeAttribute('aria-busy');
                }
            });
        });
    }

    enhanceMobileNavigation();
    enhanceStickyHeader();
    enhanceNewsletter();
    refreshCartCount();

    window.addEventListener('storage', function (event) {
        if (event.key === config.cartStorageKey) {
            refreshCartCount();
        }
    });
    document.addEventListener('staticbridge:cart-updated', refreshCartCount);
    document.dispatchEvent(new CustomEvent('staticbridge:ready', { detail: config }));
}());
