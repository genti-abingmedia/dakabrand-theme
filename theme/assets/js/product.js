(function () {
    'use strict';

    var config = typeof window === 'undefined' ? {} : (window.StaticBridgeConfig || {});
    var messages = config.messages || {};
    var locale = String(config.locale || 'en_US');
    var contactApi = 'https://filter.gliterin.net/public/filter';
    var localhostFallbackStorefront = 'https://dakabrand.uk/';

    function message(key, fallback) { return messages[key] || fallback; }

    // Gliterin must query the storefront the visitor opened.  A remote filter
    // cannot reach a developer's localhost site, so localhost uses production.
    function contactSource() {
        var location = window.location || {};
        var isLocalhost = ['localhost', '127.0.0.1'].includes(location.hostname);
        var storefront = isLocalhost ? localhostFallbackStorefront : location.origin;
        return String(storefront || localhostFallbackStorefront).replace(/\/+$/, '') + '/shop/?page=1&limit=1';
    }

    function format(template) {
        var values = Array.prototype.slice.call(arguments, 1);
        var sequentialIndex = 0;
        return String(template).replace(/%(?:(\d+)\$)?s/g, function (match, position) {
            var index = position ? Number(position) - 1 : sequentialIndex++;
            return typeof values[index] === 'undefined' ? '' : String(values[index]);
        });
    }

    function discountPercentage(regular, current, sale) {
        regular = Number(regular);
        current = Number(current);
        sale = Number(sale);
        return Number.isFinite(regular) && Number.isFinite(current) && Number.isFinite(sale) &&
            regular > 0 && sale > 0 && current > 0 && current < regular
            ? Math.round((1 - current / regular) * 100) : 0;
    }

    function deliveryRange(today, preorder, requestedLocale) {
        var start = new Date(today.getFullYear(), today.getMonth(), today.getDate() + (preorder ? 22 : 7));
        var end = new Date(today.getFullYear(), today.getMonth(), today.getDate() + (preorder ? 29 : 14));
        var normalizedLocale = String(requestedLocale || 'en_US').replace('_', '-');
        if (requestedLocale && normalizedLocale !== 'en-US' && typeof Intl !== 'undefined' && Intl.DateTimeFormat) {
            var formatter = new Intl.DateTimeFormat(normalizedLocale, { day: 'numeric', month: 'short', year: 'numeric' });
            return formatter.format(start) + ' – ' + formatter.format(end);
        }
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        function format(date) { return date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear(); }
        return format(start) + ' – ' + format(end);
    }

    function whatsappUrl(phone, message) {
        var digits = String(phone || '').replace(/\D/g, '');
        return digits ? 'https://wa.me/' + digits + '?text=' + encodeURIComponent(message) : '';
    }

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = { discountPercentage: discountPercentage, deliveryRange: deliveryRange, whatsappUrl: whatsappUrl };
        return;
    }

    function productData(detail) {
        var script = detail.querySelector('[data-staticbridge-product]');
        if (!script) return null;
        try { return JSON.parse(script.textContent); } catch (error) { return null; }
    }

    function updatePurchaseDetails(detail, item, available) {
        var badge = detail.querySelector('[data-product-discount]');
        var preorder = detail.querySelector('[data-product-preorder]');
        var delivery = detail.querySelector('[data-product-delivery]');
        var range = detail.querySelector('[data-product-delivery-range]');
        var percent = item ? discountPercentage(item.regular_price, item.price, item.sale_price) : 0;
        var isPreorder = Boolean(item && item.stock_status === 'onbackorder');
        // Keep the server-rendered promotion visible when no selectable
        // variation is available (for example, every size is out of stock).
        if (badge && percent) {
            badge.hidden = false;
            badge.textContent = percent + '%';
        }
        if (preorder) preorder.hidden = !isPreorder;
        if (delivery) delivery.hidden = !available;
        if (range && available) range.textContent = deliveryRange(new Date(), isPreorder, locale);
        if (detail.dataset) detail.dataset.deliveryPreorder = isPreorder ? 'true' : 'false';
    }

    function enhanceGallery(gallery) {
        var stage = gallery.querySelector('.product-gallery__stage');
        var main = gallery.querySelector('[data-gallery-main]');
        var thumbs = Array.from(gallery.querySelectorAll('[data-gallery-thumb]'));
        var counter = gallery.querySelector('[data-gallery-counter]');
        var index = 0;
        var touchStart = null;

        function setImageState(image, failed) {
            var container = image && image.parentNode;
            if (!image || !image.classList) return;
            image.classList.toggle('is-image-error', failed);
            if (image === main && stage && stage.classList) stage.classList.toggle('has-image-error', failed);
            if (container && container.classList) container.classList.toggle('has-image-error', failed);
        }

        function watchImage(image) {
            if (!image || typeof image.addEventListener !== 'function') return;
            image.addEventListener('error', function () { setImageState(image, true); });
            image.addEventListener('load', function () {
                if (!image.dataset || image.dataset.imageFallback !== 'true') setImageState(image, false);
            });
            if (image.complete && image.naturalWidth === 0) setImageState(image, true);
        }

        watchImage(main);
        thumbs.forEach(function (thumb) { watchImage(thumb.querySelector('img')); });

        if (!stage || !main || thumbs.length < 2) return;

        function show(next) {
            var thumb;
            index = (next + thumbs.length) % thumbs.length;
            thumb = thumbs[index];
            setImageState(main, false);
            main.src = thumb.dataset.imageSrc;
            if (thumb.dataset.imageSrcset) main.srcset = thumb.dataset.imageSrcset;
            else main.removeAttribute('srcset');
            main.alt = thumb.dataset.imageAlt;
            thumbs.forEach(function (item, position) {
                item.classList.toggle('is-active', position === index);
                item.setAttribute('aria-pressed', String(position === index));
            });
            if (counter) counter.textContent = (index + 1) + ' / ' + thumbs.length;
            if (gallery.dataset) gallery.dataset.galleryIndex = String(index);
            if (typeof gallery.dispatchEvent === 'function' && typeof CustomEvent === 'function') {
                gallery.dispatchEvent(new CustomEvent('staticbridge:gallery-change', { detail: { index: index } }));
            }
        }

        thumbs.forEach(function (thumb, position) {
            thumb.addEventListener('click', function () { show(position); });
        });
        gallery.querySelector('[data-gallery-prev]').addEventListener('click', function () { show(index - 1); });
        gallery.querySelector('[data-gallery-next]').addEventListener('click', function () { show(index + 1); });
        stage.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                event.preventDefault();
                show(index + (event.key === 'ArrowRight' ? 1 : -1));
            }
        });
        stage.addEventListener('touchstart', function (event) {
            touchStart = event.changedTouches[0].clientX;
        }, { passive: true });
        stage.addEventListener('touchend', function (event) {
            if (touchStart === null) return;
            var distance = event.changedTouches[0].clientX - touchStart;
            touchStart = null;
            if (Math.abs(distance) > 45) show(index + (distance < 0 ? 1 : -1));
        }, { passive: true });
    }

    function enhanceLightbox(gallery) {
        var lightbox = gallery.querySelector('[data-product-lightbox]');
        var opener = gallery.querySelector('[data-gallery-open]');
        var main;
        var thumbs;
        var close;
        var previous;
        var next;
        var index = 0;
        var openerBeforeOpen;
        if (!lightbox || !opener) return;

        main = lightbox.querySelector('[data-lightbox-main]');
        thumbs = Array.from(lightbox.querySelectorAll('[data-lightbox-thumb]'));
        close = lightbox.querySelector('[data-lightbox-close]');
        previous = lightbox.querySelector('[data-lightbox-prev]');
        next = lightbox.querySelector('[data-lightbox-next]');
        if (!main || !close) return;

        function setImageState(failed) {
            main.classList.toggle('is-image-error', failed);
            lightbox.classList.toggle('has-image-error', failed);
        }

        main.addEventListener('error', function () { setImageState(true); });
        main.addEventListener('load', function () { setImageState(false); });
        if (main.complete && main.naturalWidth === 0) setImageState(true);

        function show(nextIndex) {
            var thumb;
            if (!thumbs.length) return;
            index = (nextIndex + thumbs.length) % thumbs.length;
            thumb = thumbs[index];
            setImageState(false);
            main.src = thumb.dataset.imageSrc;
            if (thumb.dataset.imageSrcset) main.srcset = thumb.dataset.imageSrcset;
            else main.removeAttribute('srcset');
            main.alt = thumb.dataset.imageAlt;
            thumbs.forEach(function (item, position) {
                item.classList.toggle('is-active', position === index);
                item.setAttribute('aria-pressed', String(position === index));
            });
            thumb.scrollIntoView({ block: 'nearest', inline: 'nearest' });
        }

        function open() {
            openerBeforeOpen = document.activeElement;
            index = Number(gallery.dataset && gallery.dataset.galleryIndex) || 0;
            lightbox.hidden = false;
            document.body.classList.add('has-product-lightbox');
            show(index);
            close.focus();
        }

        function hide() {
            lightbox.hidden = true;
            document.body.classList.remove('has-product-lightbox');
            (openerBeforeOpen || opener).focus();
        }

        opener.addEventListener('click', open);
        close.addEventListener('click', hide);
        if (previous) previous.addEventListener('click', function () { show(index - 1); });
        if (next) next.addEventListener('click', function () { show(index + 1); });
        thumbs.forEach(function (thumb, position) { thumb.addEventListener('click', function () { show(position); }); });
        lightbox.addEventListener('click', function (event) { if (event.target === lightbox) hide(); });
        lightbox.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') { event.preventDefault(); hide(); }
            else if (event.key === 'ArrowLeft' && thumbs.length > 1) { event.preventDefault(); show(index - 1); }
            else if (event.key === 'ArrowRight' && thumbs.length > 1) { event.preventDefault(); show(index + 1); }
        });
        gallery.addEventListener('staticbridge:gallery-change', function (event) {
            if (!lightbox.hidden && event.detail) show(event.detail.index);
        });
    }

    function enhanceVariations(detail) {
        var product = productData(detail);
        if (!product || product.type !== 'variable' || !(product.options || []).length) return;

        var fields = Array.from(detail.querySelectorAll('[data-option-key]'));
        var price = detail.querySelector('[data-product-price]');
        var availability = detail.querySelector('[data-product-availability]');
        var button = detail.querySelector('[data-add-to-cart]');
        var choices = Array.from(detail.querySelectorAll('[data-option-choice]'));
        if (fields.length !== product.options.length || !price || !button) return;
        var initialPrice = price.innerHTML;
        var chooseLabel = product.options.length === 1
            ? format(message('choose', 'Choose %s'), product.options[0].label.toLowerCase())
            : message('chooseOptions', 'Choose options');

        function update() {
            var selected = {};
            fields.forEach(function (field) { if (field.value) selected[field.dataset.optionKey] = field.value; });
            var complete = Object.keys(selected).length === product.options.length;
            var variation = complete ? (product.variations || []).find(function (entry) {
                return product.options.every(function (option) {
                    var required = String((entry.attributes || {})[option.key] || '');
                    return !required || required === selected[option.key];
                });
            }) : null;
            var available = variation && variation.purchasable === true &&
                (variation.stock_status === 'instock' || variation.stock_status === 'onbackorder');

            price.innerHTML = variation && variation.price_html ? variation.price_html : initialPrice;
            if (availability) {
                availability.textContent = !complete ? chooseLabel : !variation ? message('unavailable', 'Unavailable') : available ? message('available', 'Available') : message('outOfStock', 'Out of stock');
                availability.classList.toggle('is-pending', !complete);
                availability.classList.toggle('is-unavailable', complete && !available);
            }
            button.disabled = !available;
            button.textContent = !complete ? chooseLabel : !variation ? message('unavailable', 'Unavailable') : available ? message('addToCart', 'Add to cart') : message('outOfStock', 'Out of stock');
            updatePurchaseDetails(detail, variation, available);

            choices.forEach(function (choice) {
                var key = choice.dataset.optionGroup;
                var value = choice.dataset.optionValue;
                var selectable = (product.variations || []).some(function (entry) {
                    var entryAvailable = entry.purchasable === true &&
                        (entry.stock_status === 'instock' || entry.stock_status === 'onbackorder');
                    return entryAvailable && product.options.every(function (option) {
                        var required = String((entry.attributes || {})[option.key] || '');
                        var expected = option.key === key ? String(value || '') : String(selected[option.key] || '');
                        return !required || !expected || required === expected;
                    });
                });
                var isSelected = String(selected[key] || '') === String(value || '');
                choice.disabled = !selectable;
                choice.classList.toggle('is-selected', isSelected);
                choice.setAttribute('aria-pressed', String(isSelected));
            });
        }

        fields.forEach(function (field) { field.addEventListener('change', update); });
        update();
    }

    function enhancePurchaseDetails(detail) {
        var product = productData(detail);
        var delivery = detail.querySelector('[data-product-delivery]');
        if (!product || !product.type) return;
        if (product.type !== 'variable') {
            var available = product.purchasable === true &&
                (product.stock_status === 'instock' || product.stock_status === 'onbackorder');
            updatePurchaseDetails(detail, product, available);
        }
        if (delivery && window.setInterval) {
            window.setInterval(function () {
                var range = detail.querySelector('[data-product-delivery-range]');
                if (range && !delivery.hidden) range.textContent = deliveryRange(new Date(), detail.dataset.deliveryPreorder === 'true', locale);
            }, 60000);
        }
    }

    function enhanceViewerCount(detail) {
        var count = detail.querySelector('[data-product-viewer-count]');
        var current = Number(count && count.textContent);
        var minimum = Number(count && count.dataset.viewerMin);
        var maximum = Number(count && count.dataset.viewerMax);
        if (!count || !Number.isFinite(current) || !Number.isFinite(minimum) || !Number.isFinite(maximum) || minimum > maximum || !window.setInterval) return;

        window.setInterval(function () {
            var next = current;
            while (next === current) next = Math.floor(Math.random() * (maximum - minimum + 1)) + minimum;
            current = next;
            count.classList.remove('is-changing');
            void count.offsetWidth;
            count.textContent = String(current);
            count.classList.add('is-changing');
        }, 10000);
    }

    function enhanceProductActions(detail) {
        var product = productData(detail);
        var whatsapp = detail.querySelector('[data-product-whatsapp]');
        var fallback = detail.querySelector('[data-product-contact-fallback]');
        var ask = detail.querySelector('[data-product-ask]');
        var share = detail.querySelector('[data-product-share]');
        var questionDialog = detail.querySelector('[data-question-dialog]');
        var shareDialog = detail.querySelector('[data-share-dialog]');
        var questionForm = detail.querySelector('[data-question-form]');
        var shareInput = detail.querySelector('[data-share-url]');
        var copy = detail.querySelector('[data-share-copy]');
        var copyStatus = detail.querySelector('[data-share-status]');
        if (!product || !whatsapp || !fallback || !ask || !share || !questionDialog || !shareDialog ||
            !questionForm || !shareInput || !copy || !copyStatus ||
            typeof questionDialog.showModal !== 'function' || typeof shareDialog.showModal !== 'function') return;

        var phone = '';
        var productLink = String(product.permalink || window.location.href);
        var opener = null;

        function open(dialog, trigger) {
            opener = trigger;
            dialog.showModal();
        }
        [questionDialog, shareDialog].forEach(function (dialog) {
            dialog.querySelector('[data-dialog-close]').addEventListener('click', function () { dialog.close(); });
            dialog.addEventListener('click', function (event) { if (event.target === dialog) dialog.close(); });
            dialog.addEventListener('close', function () { if (opener) opener.focus(); });
        });
        ask.addEventListener('click', function () { open(questionDialog, ask); });
        share.addEventListener('click', function () { open(shareDialog, share); });
        questionForm.addEventListener('submit', function (event) {
            event.preventDefault();
            var field = questionForm.querySelector('textarea');
            var question = field.value.trim();
            if (!question) { field.reportValidity(); return; }
            var url = whatsappUrl(phone, format(message('questionAboutProduct', 'Question about %1$s (%2$s): %3$s'), product.name, productLink, question));
            if (!url) return;
            questionDialog.close();
            window.location.assign(url);
        });
        copy.addEventListener('click', function () {
            var value = shareInput.value;
            Promise.resolve().then(function () {
                if (!navigator.clipboard || !navigator.clipboard.writeText) throw new Error('Clipboard unavailable');
                return navigator.clipboard.writeText(value);
            }).then(function () { copyStatus.textContent = message('linkCopied', 'Link copied'); }, function () {
                shareInput.focus();
                shareInput.select();
                try {
                    copyStatus.textContent = document.execCommand && document.execCommand('copy')
                        ? message('linkCopied', 'Link copied') : message('copyLinkFallback', 'Select and copy the link above');
                } catch (error) { copyStatus.textContent = message('copyLinkFallback', 'Select and copy the link above'); }
            });
        });

        fetch(contactApi + '?url=' + encodeURIComponent(contactSource()))
            .then(function (response) { if (!response.ok) throw new Error('Contact lookup failed'); return response.json(); })
            .then(function (payload) {
                phone = String(payload && payload.result && payload.result.whatsapp_number || '').replace(/\D/g, '');
                if (!phone) throw new Error('Contact number unavailable');
                whatsapp.href = whatsappUrl(phone, format(message('productInterest', 'I AM INTERESTED IN THE PRODUCT: %1$s with SKU: %2$s Link: %3$s'), product.name, product.sku || '', productLink));
                whatsapp.target = '_blank';
                whatsapp.rel = 'noopener noreferrer';
                whatsapp.hidden = false;
                ask.hidden = false;
            })
            .catch(function () { fallback.hidden = false; });
    }

    function enhanceQuantity(detail) {
        var field = detail.querySelector('[data-product-quantity]');
        var decrease = detail.querySelector('[data-product-quantity-decrease]');
        var increase = detail.querySelector('[data-product-quantity-increase]');
        if (!field || !decrease || !increase || typeof field.addEventListener !== 'function' ||
            typeof decrease.addEventListener !== 'function' || typeof increase.addEventListener !== 'function') return;

        function value() {
            var quantity = Math.floor(Number(field.value));
            return Number.isFinite(quantity) && quantity > 0 ? quantity : 1;
        }
        function sync() { field.value = String(value()); }
        decrease.addEventListener('click', function () { field.value = String(Math.max(1, value() - 1)); });
        increase.addEventListener('click', function () { field.value = String(value() + 1); });
        field.addEventListener('change', sync);
        field.addEventListener('blur', sync);
        sync();
    }

    document.querySelectorAll('[data-product-gallery]').forEach(function (gallery) {
        enhanceGallery(gallery);
        enhanceLightbox(gallery);
    });
    var detail = document.querySelector('.product-detail');
    if (detail) {
        enhanceVariations(detail);
        enhanceQuantity(detail);
        enhancePurchaseDetails(detail);
        enhanceViewerCount(detail);
        enhanceProductActions(detail);
    }
}());
