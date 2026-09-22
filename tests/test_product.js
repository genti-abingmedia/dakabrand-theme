const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('theme/assets/js/product.js', 'utf8');
const details = require('../theme/assets/js/product.js');

function element() {
    return {
        dataset: {}, attributes: {}, children: [], handlers: {},
        classList: {
            values: new Set(),
            add(name) { this.values.add(name); },
            remove(name) { this.values.delete(name); },
            toggle(name, active) { if (active) this.values.add(name); else this.values.delete(name); },
            contains(name) { return this.values.has(name); }
        },
        setAttribute(name, value) { this.attributes[name] = value; },
        removeAttribute(name) { delete this.attributes[name]; },
        focus() { this.focused = true; },
        addEventListener(name, handler) { this.handlers[name] = handler; },
        scrollIntoView() {},
        append(...children) { this.children.push(...children); },
        appendChild(child) { this.children.push(child); },
        querySelector() { return null; },
        parentNode: null
    };
}

function run(galleries, detail, section, storage = {}) {
    const localStorage = {
        getItem(key) { return storage[key] || null; },
        setItem(key, value) { storage[key] = value; }
    };
    const document = {
        querySelectorAll(selector) { return selector === '[data-product-gallery]' ? galleries : []; },
        querySelector(selector) {
            if (selector === '.product-detail') return detail;
            if (selector === '[data-recent-products]') return section;
            return null;
        },
        createElement: element
    };
    vm.runInNewContext(source, {
        document, localStorage,
        window: { setTimeout(callback) { callback(); }, matchMedia() { return { matches: false }; } }
    });
    return storage;
}

function gallery(count) {
    const stage = element();
    const main = element();
    const counter = element();
    const prev = element();
    const next = element();
    const thumbs = Array.from({ length: count }, (_, index) => {
        const thumb = element();
        const image = element();
        image.parentNode = thumb;
        thumb.querySelector = selector => selector === 'img' ? image : null;
        thumb.dataset = { imageSrc: `/image-${index}.jpg`, imageSrcset: `/image-${index}-large.jpg 2x`, imageAlt: `View ${index + 1}` };
        return thumb;
    });
    const gallery = {
        querySelector(selector) {
            return ({ '.product-gallery__stage': stage, '[data-gallery-main]': main,
                '[data-gallery-counter]': counter, '[data-gallery-prev]': prev,
                '[data-gallery-next]': next })[selector];
        },
        querySelectorAll() { return thumbs; }
    };
    main.parentNode = stage;
    return { gallery, stage, main, counter, prev, next, thumbs };
}

test('gallery responds to thumbnails, arrows, keyboard, and swipe', () => {
    const g = gallery(3);
    run([g.gallery], null, null);
    g.thumbs[2].handlers.click();
    assert.equal(g.main.src, '/image-2.jpg');
    assert.equal(g.counter.textContent, '3 / 3');
    assert.equal(g.thumbs[2].attributes['aria-pressed'], 'true');
    g.next.handlers.click();
    assert.equal(g.main.src, '/image-0.jpg');
    g.stage.handlers.keydown({ key: 'ArrowLeft', preventDefault() {} });
    assert.equal(g.main.src, '/image-2.jpg');
    g.stage.handlers.touchstart({ changedTouches: [{ clientX: 120 }] });
    g.stage.handlers.touchend({ changedTouches: [{ clientX: 20 }] });
    assert.equal(g.main.src, '/image-0.jpg');
});

test('single-image gallery leaves its static image unchanged', () => {
    const g = gallery(1);
    run([g.gallery], null, null);
    assert.equal(Object.keys(g.stage.handlers).length, 0);
    assert.equal(g.main.src, undefined);
});

test('gallery replaces failed images with the DAKA fallback state', () => {
    const g = gallery(3);
    run([g.gallery], null, null);
    g.main.handlers.error();
    assert.equal(g.stage.classList.contains('has-image-error'), true);
    g.thumbs[1].querySelector('img').handlers.error();
    assert.equal(g.thumbs[1].classList.contains('has-image-error'), true);
    g.thumbs[1].handlers.click();
    assert.equal(g.stage.classList.contains('has-image-error'), false);
});

test('gallery detects an image that failed before its listeners were attached', () => {
    const g = gallery(3);
    g.main.complete = true;
    g.main.naturalWidth = 0;
    run([g.gallery], null, null);
    assert.equal(g.stage.classList.contains('has-image-error'), true);
});

test('variable product updates price, availability, and purchase state', () => {
    const select = element();
    select.dataset.optionKey = 'attribute_size';
    select.value = '';
    const price = element();
    price.innerHTML = '$99–$119';
    const availability = element();
    const discount = element();
    discount.hidden = false;
    discount.textContent = '20%';
    const preorder = element();
    const delivery = element();
    const range = element();
    const button = element();
    const size40 = element();
    size40.dataset = { optionGroup: 'attribute_size', optionValue: 'EU 40' };
    const size42 = element();
    size42.dataset = { optionGroup: 'attribute_size', optionValue: 'EU 42' };
    const script = { textContent: JSON.stringify({
        type: 'variable', options: [{ key: 'attribute_size', label: 'Size' }],
        variations: [
            { attributes: { attribute_size: 'EU 40' }, price: '80', regular_price: '100', sale_price: '80', price_html: '$80', stock_status: 'onbackorder', purchasable: true },
            { attributes: { attribute_size: 'EU 42' }, price_html: '$119', stock_status: 'outofstock', purchasable: true }
        ]
    }) };
    const detail = {
        dataset: {},
        querySelector(selector) {
            return ({ '[data-staticbridge-product]': script, '[data-product-price]': price,
                '[data-product-availability]': availability, '[data-add-to-cart]': button,
                '[data-product-discount]': discount, '[data-product-preorder]': preorder,
                '[data-product-delivery]': delivery, '[data-product-delivery-range]': range })[selector];
        },
        querySelectorAll(selector) {
            if (selector === '[data-option-key]') return [select];
            if (selector === '[data-option-choice]') return [size40, size42];
            return [];
        }
    };
    run([], detail, null);
    assert.equal(button.disabled, true);
    assert.equal(button.textContent, 'Choose size');
    assert.equal(discount.hidden, false);
    assert.equal(discount.textContent, '20%');
    assert.equal(delivery.hidden, true);
    assert.equal(size40.disabled, false);
    assert.equal(size42.disabled, true);
    select.value = 'EU 40';
    select.handlers.change();
    assert.equal(price.innerHTML, '$80');
    assert.equal(button.disabled, false);
    assert.equal(availability.textContent, 'Available');
    assert.equal(discount.textContent, '20%');
    assert.equal(discount.hidden, false);
    assert.equal(preorder.hidden, false);
    assert.equal(delivery.hidden, false);
    assert.equal(size40.classList.contains('is-selected'), true);
    select.value = 'EU 42';
    select.handlers.change();
    assert.equal(button.disabled, true);
    assert.equal(availability.textContent, 'Out of stock');
    assert.equal(discount.hidden, false);
    assert.equal(discount.textContent, '20%');
    assert.equal(preorder.hidden, true);
    assert.equal(delivery.hidden, true);
    select.value = '';
    select.handlers.change();
    assert.equal(price.innerHTML, '$99–$119');
});

test('sale badge uses the active displayed price and ignores invalid sales', () => {
    assert.equal(details.discountPercentage('300', '195', '195'), 35);
    assert.equal(details.discountPercentage('300', '300', '195'), 0);
    assert.equal(details.discountPercentage('300', '300', ''), 0);
});

test('delivery dates roll over months and include preorder delay', () => {
    const today = new Date(2026, 8, 16);
    assert.equal(details.deliveryRange(today, false), '23 Sep 2026 – 30 Sep 2026');
    assert.equal(details.deliveryRange(today, true), '8 Oct 2026 – 15 Oct 2026');
});

test('WhatsApp links sanitize the shared contact number and encode questions', () => {
    assert.equal(details.whatsappUrl('+44 7700 900123', 'Is this bag available?'),
        'https://wa.me/447700900123?text=Is%20this%20bag%20available%3F');
    assert.equal(details.whatsappUrl('', 'Question'), '');
});

function actionPage(fetchResult, origin = 'https://example.com') {
    const nodes = Object.fromEntries([
        '[data-product-whatsapp]', '[data-product-contact-fallback]', '[data-product-ask]',
        '[data-product-share]', '[data-question-form]', '[data-share-url]', '[data-share-copy]',
        '[data-share-status]'
    ].map(selector => [selector, element()]));
    ['[data-product-whatsapp]', '[data-product-contact-fallback]', '[data-product-ask]'].forEach(selector => {
        nodes[selector].hidden = true;
    });
    const textarea = element();
    textarea.value = 'Is the bag available?';
    nodes['[data-question-form]'].querySelector = () => textarea;
    nodes['[data-share-url]'].value = 'https://example.com/product/bag/';
    const dialogs = ['[data-question-dialog]', '[data-share-dialog]'];
    dialogs.forEach(selector => {
        const dialog = element();
        const close = element();
        dialog.querySelector = () => close;
        dialog.showModal = () => { dialog.open = true; };
        dialog.close = () => { dialog.open = false; if (dialog.handlers.close) dialog.handlers.close(); };
        nodes[selector] = dialog;
    });
    const product = { type: 'simple', product_id: 10, name: 'Bag', sku: 'BAG-1',
        permalink: 'https://example.com/product/bag/', price: '100', regular_price: '100',
        sale_price: '', stock_status: 'instock', purchasable: true };
    nodes['[data-staticbridge-product]'] = { textContent: JSON.stringify(product) };
    const detail = { dataset: {}, querySelector(selector) { return nodes[selector] || null; }, querySelectorAll() { return []; } };
    const destinations = [];
    const document = {
        querySelectorAll() { return []; },
        querySelector(selector) { return selector === '.product-detail' ? detail : null; }
    };
    const location = new URL(origin);
    const window = { location: {
        href: product.permalink, origin: location.origin, hostname: location.hostname,
        assign(url) { destinations.push(url); }
    } };
    const navigator = { clipboard: { writeText(value) { destinations.push('copied:' + value); return Promise.resolve(); } } };
    vm.runInNewContext(source, { document, window, navigator, fetch: fetchResult, localStorage: { getItem() { return null; } } });
    return { nodes, destinations, textarea };
}

test('question opens a WhatsApp message and share copies the product URL', async () => {
    const page = actionPage(async () => ({ ok: true, json: async () => ({ result: { whatsapp_number: '+44 7700 900123' } }) }));
    await new Promise(setImmediate);
    assert.equal(page.nodes['[data-product-whatsapp]'].hidden, false);
    page.nodes['[data-product-ask]'].handlers.click();
    assert.equal(page.nodes['[data-question-dialog]'].open, true);
    page.nodes['[data-question-form]'].handlers.submit({ preventDefault() {} });
    assert.match(page.destinations[0], /^https:\/\/wa\.me\/447700900123\?text=/);
    assert.match(decodeURIComponent(page.destinations[0]), /Is the bag available\?/);
    page.nodes['[data-product-share]'].handlers.click();
    page.nodes['[data-share-copy]'].handlers.click();
    await new Promise(setImmediate);
    assert.equal(page.nodes['[data-share-status]'].textContent, 'Link copied');
    assert.equal(page.destinations[1], 'copied:https://example.com/product/bag/');
});

test('contact failure reveals the contact page fallback', async () => {
    const page = actionPage(async () => { throw new Error('offline'); });
    await new Promise(setImmediate);
    assert.equal(page.nodes['[data-product-contact-fallback]'].hidden, false);
    assert.equal(page.nodes['[data-product-whatsapp]'].hidden, true);
});

test('contact lookup uses the page storefront, with production as the localhost fallback', async () => {
    const requested = [];
    const fetchContact = async url => {
        requested.push(url);
        return { ok: true, json: async () => ({ result: { whatsapp_number: '+44 7700 900123' } }) };
    };
    actionPage(fetchContact, 'https://preview.example.test');
    actionPage(fetchContact, 'http://localhost:8080');
    await new Promise(setImmediate);
    assert.match(decodeURIComponent(requested[0]), /url=https:\/\/preview\.example\.test\/shop\/\?page=1&limit=1/);
    assert.match(decodeURIComponent(requested[1]), /url=https:\/\/dakabrand\.uk\/shop\/\?page=1&limit=1/);
});
