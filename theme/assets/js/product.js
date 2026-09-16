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
            main.classList.add('is-changing');
            main.src = thumb.dataset.imageSrc;
            if (thumb.dataset.imageSrcset) main.srcset = thumb.dataset.imageSrcset;
            else main.removeAttribute('srcset');
            main.alt = thumb.dataset.imageAlt;
            thumbs.forEach(function (item, position) {
                item.classList.toggle('is-active', position === index);
                item.setAttribute('aria-pressed', String(position === index));
            });
            if (counter) counter.textContent = (index + 1) + ' / ' + thumbs.length;
            thumb.scrollIntoView({
                block: 'nearest',
                inline: 'nearest',
                behavior: window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth'
            });
            window.setTimeout(function () { main.classList.remove('is-changing'); }, 180);
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

    document.querySelectorAll('[data-product-gallery]').forEach(enhanceGallery);
    enhanceRecent();
}());
