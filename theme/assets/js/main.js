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

    function enhanceFashionNavigation() {
        var navigation = document.querySelector('[data-fashion-navigation]');

        if (!navigation) return;

        var collections = Array.from(navigation.querySelectorAll('[data-fashion-collection]'));

        function closeMegaMenus(collection) {
            (collection ? [collection] : collections).forEach(function (item) {
                item.querySelectorAll('[data-mega-panel], [data-mega-trigger]').forEach(function (element) {
                    element.classList.remove('is-open', 'is-mega-active');
                });
            });
        }

        function openMegaMenu(collection, name) {
            closeMegaMenus(collection);
            collection.querySelectorAll('[data-mega-trigger="' + name + '"]').forEach(function (trigger) {
                trigger.classList.add('is-mega-active');
            });
            collection.querySelectorAll('[data-mega-panel="' + name + '"]').forEach(function (panel) {
                panel.classList.add('is-open');
            });
        }

        collections.forEach(function (collection) {
            collection.querySelectorAll('[data-mega-trigger]').forEach(function (trigger) {
                var name = trigger.getAttribute('data-mega-trigger');
                trigger.addEventListener('mouseenter', function () { openMegaMenu(collection, name); });
                trigger.addEventListener('focus', function () { openMegaMenu(collection, name); });
            });
            collection.addEventListener('mouseleave', function () { closeMegaMenus(collection); });
            collection.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeMegaMenus(collection);
                    collection.querySelector('[data-mega-trigger]')?.focus();
                }
            });
        });
    }

    function enhanceSiteSearch() {
        var search = document.querySelector('[data-site-search]');
        if (!search) return;

        search.addEventListener('toggle', function () {
            if (search.open) search.querySelector('input[name="keyword"]').focus();
        });
        document.addEventListener('click', function (event) {
            if (search.open && !search.contains(event.target)) search.open = false;
        });
        search.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                search.open = false;
                search.querySelector('summary').focus();
            }
        });
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

    function enhanceMobileTabs() {
        var path = window.location.pathname.replace(/\/+$/, '') || '/';
        var active = 'shop';

        if (path === '/') {
            active = 'home';
        } else if (path === '/woman' || path.indexOf('/product-category/women') === 0) {
            active = 'women';
        } else if (path === '/man' || path.indexOf('/product-category/man') === 0) {
            active = 'man';
        } else if (path.indexOf('/cart') === 0) {
            active = 'cart';
        }

        document.querySelectorAll('[data-mobile-tab]').forEach(function (tab) {
            var isActive = tab.getAttribute('data-mobile-tab') === active;
            tab.classList.toggle('is-active', isActive);
            if (isActive) {
                tab.setAttribute('aria-current', 'page');
            } else {
                tab.removeAttribute('aria-current');
            }
        });
    }

    function enhanceGatewaySwitcher() {
        var gateway = document.querySelector('.home-gateway');
        var switcher = document.querySelector('[data-gateway-switcher]');
        var panels;
        var links;
        var reduceMotion;

        if (!gateway || !switcher) {
            return;
        }

        panels = Array.from(gateway.querySelectorAll('[data-gateway-panel]'));
        links = Array.from(switcher.querySelectorAll('a'));
        reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function selectPanel(panel) {
            links.forEach(function (link) {
                if (link.getAttribute('href') === '#' + panel.id) {
                    link.setAttribute('aria-current', 'true');
                } else {
                    link.removeAttribute('aria-current');
                }
            });
        }

        links.forEach(function (link) {
            link.addEventListener('click', function (event) {
                var panel = document.querySelector(link.getAttribute('href'));
                event.preventDefault();
                if (panel) {
                    panel.scrollIntoView({
                        behavior: reduceMotion ? 'auto' : 'smooth',
                        block: 'nearest',
                        inline: 'start'
                    });
                    selectPanel(panel);
                }
            });
        });

        if ('IntersectionObserver' in window) {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting && entry.intersectionRatio >= 0.6) {
                        selectPanel(entry.target);
                    }
                });
            }, { root: gateway, threshold: [0.6] });

            panels.forEach(function (panel) {
                observer.observe(panel);
            });
        }
    }

    enhanceMobileNavigation();
    enhanceFashionNavigation();
    enhanceSiteSearch();
    enhanceNewsletter();
    enhanceMobileTabs();
    enhanceGatewaySwitcher();
    refreshCartCount();

    window.addEventListener('storage', function (event) {
        if (event.key === config.cartStorageKey) {
            refreshCartCount();
        }
    });
    document.addEventListener('staticbridge:cart-updated', refreshCartCount);
    document.dispatchEvent(new CustomEvent('staticbridge:ready', { detail: config }));
}());
