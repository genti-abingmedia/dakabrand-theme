(function () {
    'use strict';
    var smoke = document.querySelector('[data-halloween-smoke]');
    function syncVisibility() {
        if (smoke) smoke.toggleAttribute('data-paused', document.hidden);
    }
    document.addEventListener('visibilitychange', syncVisibility);
    syncVisibility();

    var visitor = document.querySelector('[data-halloween-visitor]');
    if (!visitor || !window.matchMedia) return;
    var reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    var image = visitor.querySelector('img');
    var nextTimer, hideTimer, exitTimer, entranceTimer;
    var failed = false;
    var returnFocus;
    var overlays = 'dialog[open], .offcanvas.show, .offcanvas.showing, .offcanvas.hiding, .modal.show, .catalog-filters.is-open, .product-lightbox:not([hidden]), .site-search[open]';

    function randomDelay(minimum, maximum) {
        return minimum + Math.floor(Math.random() * (maximum - minimum));
    }

    function clearTimers() {
        clearTimeout(nextTimer);
        clearTimeout(hideTimer);
        clearTimeout(exitTimer);
        clearTimeout(entranceTimer);
    }

    function blocked() {
        var focused = document.activeElement;
        return document.hidden || reduced.matches || failed || document.querySelector(overlays) ||
            (focused && focused.matches('input, textarea, select, [contenteditable="true"]'));
    }

    function schedule(initial) {
        clearTimeout(nextTimer);
        if (!document.hidden && !reduced.matches && !failed) {
            nextTimer = setTimeout(peek, initial ? randomDelay(2000, 6000) : randomDelay(30000, 45000));
        }
    }

    function dismiss(immediate) {
        clearTimers();
        if (document.activeElement === visitor && returnFocus && returnFocus.isConnected) {
            returnFocus.focus({ preventScroll: true });
        }
        visitor.classList.remove('is-peeking');
        visitor.inert = true;
        if (immediate) visitor.hidden = true;
        else exitTimer = setTimeout(function () { visitor.hidden = true; }, 350);
        schedule(false);
    }

    function peek() {
        if (blocked() || !image.complete || !image.naturalWidth) {
            schedule(false);
            return;
        }
        // Keep the mobile toolbar clear; desktop can use all four edges.
        var edges = window.innerWidth < 992 ? ['left', 'right', 'top'] : ['left', 'right', 'top', 'bottom'];
        var edge = edges[Math.floor(Math.random() * edges.length)];
        visitor.setAttribute('data-edge', edge);
        visitor.style.setProperty('--peek-position', (edge === 'left' || edge === 'right'
            ? randomDelay(32, 58) : randomDelay(22, 68)) + '%');
        visitor.hidden = false;
        visitor.inert = true;
        // Allow the off-screen state to paint before transitioning in.
        entranceTimer = setTimeout(function () {
            if (blocked()) { dismiss(true); return; }
            visitor.classList.add('is-peeking');
            visitor.inert = false;
        }, 40);
        hideTimer = setTimeout(function () { dismiss(false); }, 5000);
    }

    visitor.inert = true;
    visitor.addEventListener('focus', function (event) { returnFocus = event.relatedTarget; });
    visitor.addEventListener('click', function () { dismiss(false); });
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !visitor.hidden) dismiss(false);
    });
    document.addEventListener('visibilitychange', function () { dismiss(true); });
    if (reduced.addEventListener) reduced.addEventListener('change', function () { dismiss(true); });
    else if (reduced.addListener) reduced.addListener(function () { dismiss(true); });
    image.addEventListener('error', function () { failed = true; dismiss(true); });
    if (image.complete && !image.naturalWidth) failed = true;
    window.addEventListener('pagehide', function () { clearTimers(); visitor.hidden = true; });
    window.addEventListener('pageshow', function (event) { if (event.persisted) dismiss(true); });
    window.addEventListener('resize', function () { dismiss(true); });

    if (window.MutationObserver) {
        var observer = new MutationObserver(function () {
            if (document.querySelector(overlays)) dismiss(true);
        });
        document.querySelectorAll('dialog, .offcanvas, .modal, .catalog-filters, .product-lightbox, .site-search').forEach(function (overlay) {
            observer.observe(overlay, { attributes: true, attributeFilter: ['class', 'open', 'hidden'] });
        });
    }
    schedule(true);
}());
