(function () {
    'use strict';

    var config = window.StaticBridgeConfig || {};
    var messages = config.messages || {};

    function message(key, fallback) {
        return messages[key] || fallback;
    }

    function format(template) {
        var values = Array.prototype.slice.call(arguments, 1);
        return String(template).replace(/%s/g, function () { return String(values.shift() || ''); });
    }

    function enhanceImageFallbacks() {
        var fallbackImage = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 600"%3E%3Crect width="600" height="600" fill="%23f3f1ed"/%3E%3Ctext x="300" y="315" fill="%23cfcfcf" font-family="Arial,sans-serif" font-size="64" font-weight="700" letter-spacing="12" text-anchor="middle"%3EDAKA%3C/text%3E%3C/svg%3E';
        var managedImages = '.product-gallery__main, .product-gallery__thumb img, .product-lightbox__image, .catalog-card__image-link img, .storefront-product-card__image-link img, .product-card__link img';
        var themedContainers = '.site-brand, .custom-logo-link, .fashion-mega-menu__feature, .home-gateway__image-link, .man-hero__media, .man-editorial';

        document.addEventListener('error', function (event) {
            var image = event.target;
            if (!image || image.tagName !== 'IMG' || (image.closest && image.closest(managedImages))) return;
            if (image.dataset && image.dataset.fallbackApplied === 'true') return;

            var container = image.closest ? image.closest(themedContainers) : null;
            if (container && container.classList) {
                image.classList.add('is-image-error');
                container.classList.add('has-image-error');
                return;
            }

            if (image.dataset) image.dataset.fallbackApplied = 'true';
            image.src = fallbackImage;
        }, true);
    }

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
            element.setAttribute('aria-label', format(message('cartItem', '%s items in cart'), count));
        });
    }

    function languageStorageKey() {
        return config.languageStorageKey || 'staticbridge_language_v1';
    }

    function selectedLocale() {
        try {
            var locale = localStorage.getItem(languageStorageKey());
            return locale === 'sq_AL' || locale === 'en_US' ? locale : 'en_US';
        } catch (error) {
            return 'en_US';
        }
    }

    function languageMessages(locale) {
        var catalogues = config.languageCatalogues || {};
        return locale === 'sq_AL' && catalogues.sq_AL ? catalogues.sq_AL : {};
    }

    function replaceText(text, messages) {
        var leading = text.match(/^\s*/)[0];
        var trailing = text.match(/\s*$/)[0];
        var source = text.trim();
        return Object.prototype.hasOwnProperty.call(messages, source)
            ? leading + messages[source] + trailing
            : text;
    }

    function applyLanguage(locale, messages) {
        var walker = document.body && typeof document.createTreeWalker === 'function' && typeof NodeFilter !== 'undefined'
            ? document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT) : null;
        var node;
        var attributes = ['aria-label', 'placeholder', 'title', 'value'];

        if (walker) {
            while ((node = walker.nextNode())) {
                if (!node.parentElement || ['SCRIPT', 'STYLE'].indexOf(node.parentElement.tagName) !== -1) continue;
                node.nodeValue = replaceText(node.nodeValue, messages);
            }
        }

        document.querySelectorAll('*').forEach(function (element) {
            attributes.forEach(function (attribute) {
                if (element.hasAttribute(attribute)) {
                    element.setAttribute(attribute, replaceText(element.getAttribute(attribute), messages));
                }
            });
        });

        if (document.documentElement) document.documentElement.lang = locale === 'sq_AL' ? 'sq-AL' : 'en-US';
        config.locale = locale;
        config.messages = Object.assign({}, config.messages || {}, messages);
        document.querySelectorAll('[data-language-locale]').forEach(function (button) {
            if (button.getAttribute('data-language-locale') === locale) {
                button.setAttribute('aria-current', 'true');
            } else {
                button.removeAttribute('aria-current');
            }
        });
        document.dispatchEvent(new CustomEvent('staticbridge:language-updated', {
            detail: { locale: locale, messages: messages }
        }));
    }

    function enhanceLanguageSwitcher() {
        var locale = selectedLocale();

        applyLanguage(locale, languageMessages(locale));

        document.querySelectorAll('[data-language-locale]').forEach(function (button) {
            button.addEventListener('click', function () {
                var nextLocale = button.getAttribute('data-language-locale');
                if (nextLocale !== 'en_US' && nextLocale !== 'sq_AL') return;

                try {
                    localStorage.setItem(languageStorageKey(), nextLocale);
                    // Start from the generated English document on each change;
                    // this also restores English after an Albanian selection.
                    window.location.reload();
                } catch (error) {
                    // Still switch for this page when browser storage is blocked.
                    applyLanguage(nextLocale, languageMessages(nextLocale));
                }
            });
        });
    }

    function enhanceStockMode() {
        var storageKey = config.stockModeStorageKey || 'staticbridge_stock_mode_v1';
        var statuses = {
            preorder: 'onbackorder:onbackorder',
            available: 'instock:instock,outofstock:outofstock'
        };
        var excludedPaths = [
            '/cart', '/checkout', '/my-account', '/contact-us', '/privacy-policy',
            '/terms-conditions', '/refund-returns', '/wp-admin', '/wp-login.php'
        ];

        function normalizedStatus(value) {
            return typeof value === 'string' && value.trim() ? value.trim() : '';
        }

        function modeForStatus(value) {
            var status = normalizedStatus(value);
            var selectedStatuses;
            if (!status) return null;
            selectedStatuses = status.split(',').map(function (token) {
                return token.split(':')[0].trim();
            }).filter(Boolean);
            // "Preorder Only" must be exclusive. A mixed selection (including
            // preorder alongside in/out-of-stock) is an available catalog view.
            return selectedStatuses.length === 1 && selectedStatuses[0] === 'onbackorder'
                ? 'preorder'
                : 'available';
        }

        function savedStatus() {
            try {
                return normalizedStatus(localStorage.getItem(storageKey));
            } catch (error) {
                return '';
            }
        }

        function saveStatus(value) {
            var status = normalizedStatus(value);
            try {
                if (status) localStorage.setItem(storageKey, status);
                else localStorage.removeItem(storageKey);
            } catch (error) {
                // Browsing still works when storage is unavailable.
            }
            refreshBadge();
            document.dispatchEvent(new CustomEvent('staticbridge:stock-mode-updated', {
                detail: { status: status, mode: modeForStatus(status) }
            }));
            return status;
        }

        function saveMode(mode) {
            return saveStatus(statuses[mode] || '');
        }

        function currentStatus() {
            var params = new URLSearchParams(window.location.search);
            return normalizedStatus(params.get('stock_status')) || savedStatus();
        }

        function refreshBadge() {
            var status = currentStatus();
            var mode = modeForStatus(status);
            document.querySelectorAll('[data-stock-mode-badge]').forEach(function (badge) {
                badge.hidden = mode !== 'preorder';
                badge.textContent = mode === 'preorder'
                    ? message('preorderOnly', 'Preorder Only')
                    : '';
            });
        }

        function isExcludedDestination(url) {
            var path = url.pathname.replace(/\/+$/, '') || '/';
            return path === '/' || excludedPaths.some(function (excluded) {
                return path === excluded || path.indexOf(excluded + '/') === 0;
            });
        }

        function inheritedUrl(value) {
            var url;
            var status = savedStatus();
            try {
                url = new URL(value, window.location.origin);
            } catch (error) {
                return null;
            }
            if (url.origin !== window.location.origin || !status || url.searchParams.has('stock_status') || isExcludedDestination(url)) {
                return url;
            }
            url.searchParams.set('stock_status', status);
            return url;
        }

        function linkFromEvent(event) {
            var target = event.target;
            return target && target.closest ? target.closest('a[href]') : null;
        }

        document.querySelectorAll('[data-stock-mode]').forEach(function (link) {
            link.addEventListener('click', function () {
                var destination;
                saveMode(link.getAttribute('data-stock-mode'));
                destination = inheritedUrl(link.href);
                if (destination && destination.href !== link.href) link.href = destination.href;
            });
        });

        document.addEventListener('click', function (event) {
            var link = linkFromEvent(event);
            var url;
            if (!link || link.getAttribute('data-stock-mode') || link.hasAttribute('download') || (link.target && link.target !== '_self')) return;
            url = inheritedUrl(link.href);
            if (url && url.href !== link.href) link.href = url.href;
        }, true);

        window.addEventListener('popstate', refreshBadge);
        refreshBadge();

        window.StaticBridgeStockMode = {
            availableStatus: statuses.available,
            preorderStatus: statuses.preorder,
            currentStatus: currentStatus,
            modeForStatus: modeForStatus,
            saveMode: saveMode,
            saveStatus: saveStatus,
            clear: function () { return saveStatus(''); },
            inheritUrl: inheritedUrl,
            refreshBadge: refreshBadge
        };
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
            button.setAttribute('aria-label', format(message('toggleSubmenu', 'Toggle %s submenu'), link.textContent.trim()));
            button.addEventListener('click', function () {
                var isOpen = item.classList.toggle('is-open');
                button.setAttribute('aria-expanded', String(isOpen));
            });
            item.insertBefore(button, submenu);
        });
    }

    function enhanceFashionNavigation() {
        var navigation = document.querySelector('[data-fashion-navigation]');
        var storageKey = 'mainCateg';

        if (!navigation) return;

        var collections = Array.from(navigation.querySelectorAll('[data-fashion-collection]'));
        var departments = Array.from(navigation.querySelectorAll('[data-fashion-department]'));

        function storedDepartment() {
            try {
                var value = localStorage.getItem(storageKey);
                return value === 'woman' || value === 'man' ? value : null;
            } catch (error) {
                return null;
            }
        }

        function pageDepartment() {
            var page = document.querySelector('[data-static-view="man"], [data-static-view="woman"]');
            return page ? page.getAttribute('data-static-view') : null;
        }

        function saveDepartment(value) {
            try {
                localStorage.setItem(storageKey, value);
            } catch (error) {
                // Navigation still works when browser storage is unavailable.
            }
        }

        function setDepartment(value) {
            var department = value === 'man' ? 'men' : 'women';

            departments.forEach(function (tab) {
                var isCurrent = tab.getAttribute('data-fashion-department') === department;
                tab.classList.toggle('is-current', isCurrent);
                tab.setAttribute('aria-selected', String(isCurrent));
            });

            collections.forEach(function (collection) {
                var isCurrent = collection.getAttribute('data-fashion-collection') === department;
                collection.classList.toggle('is-current', isCurrent);
                collection.hidden = !isCurrent;
            });
        }

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

        var currentPageDepartment = pageDepartment();
        var savedDepartment = storedDepartment();

        // A Man or Woman landing page is authoritative. It corrects stale
        // browser state without relying on its URL or product categories.
        if (currentPageDepartment) {
            saveDepartment(currentPageDepartment);
            setDepartment(currentPageDepartment);
        } else if (savedDepartment) {
            setDepartment(savedDepartment);
        }

        departments.forEach(function (tab) {
            tab.addEventListener('click', function () {
                var department = tab.getAttribute('data-fashion-department') === 'men' ? 'man' : 'woman';

                saveDepartment(department);
                setDepartment(department);
            });
        });
    }

    function translateStaticLabels() {
        var labels = config.staticLabels || {};
        document.querySelectorAll('.fashion-mega-menu a').forEach(function (link) {
            var source = link.textContent.trim();
            if (Object.prototype.hasOwnProperty.call(labels, source)) link.textContent = labels[source];
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

    enhanceImageFallbacks();
    enhanceStockMode();
    enhanceMobileNavigation();
    enhanceFashionNavigation();
    enhanceLanguageSwitcher();
    translateStaticLabels();
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
