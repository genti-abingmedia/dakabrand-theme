(function () {
    'use strict';

    var storageKey = 'staticbridge_recent_products_v1';

    function enhanceGallery(gallery) {
        var stage = gallery.querySelector('.product-gallery__stage');
        var main = gallery.querySelector('[data-gallery-main]');
        var thumbs = Array.from(gallery.querySelectorAll('[data-gallery-thumb]'));
        var counter = gallery.querySelector('[data-gallery-counter]');
        var index = 0;
        var touchStart = null;

        if (!stage || !main || thumbs.length < 2) return;

        function show(next) {
            var thumb;
            index = (next + thumbs.length) % thumbs.length;
            thumb = thumbs[index];
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

        function show(nextIndex) {
            var thumb;
            if (!thumbs.length) return;
            index = (nextIndex + thumbs.length) % thumbs.length;
            thumb = thumbs[index];
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
        var script = detail.querySelector('[data-staticbridge-product]');
        var product;
        if (!script) return;
        try { product = JSON.parse(script.textContent); } catch (error) { return; }
        if (!product || product.type !== 'variable' || !(product.options || []).length) return;

        var fields = Array.from(detail.querySelectorAll('[data-option-key]'));
        var price = detail.querySelector('[data-product-price]');
        var availability = detail.querySelector('[data-product-availability]');
        var button = detail.querySelector('[data-add-to-cart]');
        var choices = Array.from(detail.querySelectorAll('[data-option-choice]'));
        if (fields.length !== product.options.length || !price || !availability || !button) return;
        var initialPrice = price.innerHTML;
        var chooseLabel = product.options.length === 1
            ? 'Choose ' + product.options[0].label.toLowerCase()
            : 'Choose options';

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
            availability.textContent = !complete ? chooseLabel : !variation ? 'Unavailable' : available ? 'Available' : 'Out of stock';
            availability.classList.toggle('is-pending', !complete);
            availability.classList.toggle('is-unavailable', complete && !available);
            button.disabled = !available;
            button.textContent = !complete ? chooseLabel : !variation ? 'Unavailable' : available ? 'Add to cart' : 'Out of stock';

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

    function readRecent() {
        try {
            var entries = JSON.parse(localStorage.getItem(storageKey) || '[]');
            return Array.isArray(entries) ? entries : [];
        } catch (error) { return []; }
    }

    function recordRecent(product) {
        try {
            var entries = readRecent().filter(function (item) { return item && item.id !== product.id; });
            entries.unshift(product);
            localStorage.setItem(storageKey, JSON.stringify(entries.slice(0, 8)));
        } catch (error) { /* Storage can be disabled; the product page still works. */ }
    }

    function renderRecent(section, entries) {
        var list = section.querySelector('[data-recent-products-list]');
        if (!list) return;
        entries.slice(0, 4).forEach(function (item) {
            if (!item || !item.name || !item.url || !item.image) return;
            var article = document.createElement('article');
            var link = document.createElement('a');
            var image = document.createElement('img');
            var title = document.createElement('h3');
            article.className = 'recent-product';
            link.href = item.url;
            image.src = item.image;
            image.alt = '';
            image.loading = 'lazy';
            title.textContent = item.name;
            link.append(image, title);
            article.appendChild(link);
            list.appendChild(article);
        });
        section.hidden = !list.children.length;
    }

    function enhanceRecent() {
        var detail = document.querySelector('.product-detail');
        var section = document.querySelector('[data-recent-products]');
        var script = detail && detail.querySelector('[data-staticbridge-product]');
        var product;
        if (!section || !script) return;
        try { product = JSON.parse(script.textContent); } catch (error) { return; }
        if (!product || !product.product_id || !product.permalink) return;
        var currentId = Number(product.product_id);
        var previous = readRecent().filter(function (item) { return item && item.id !== currentId; });
        renderRecent(section, previous);
        recordRecent({ id: currentId, name: product.name, url: product.permalink, image: product.image });
    }

    document.querySelectorAll('[data-product-gallery]').forEach(function (gallery) {
        enhanceGallery(gallery);
        enhanceLightbox(gallery);
    });
    var detail = document.querySelector('.product-detail');
    if (detail) {
        enhanceVariations(detail);
        enhanceQuantity(detail);
    }
    enhanceRecent();
}());
