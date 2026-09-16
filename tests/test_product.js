const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('theme/assets/js/product.js', 'utf8');

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
        addEventListener(name, handler) { this.handlers[name] = handler; },
        scrollIntoView() {},
        append(...children) { this.children.push(...children); },
        appendChild(child) { this.children.push(child); },
        querySelector() { return null; }
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

test('recently viewed excludes current product and stays hidden for first visit', () => {
    const script = { textContent: JSON.stringify({ product_id: 10, name: 'Weekender', permalink: '/weekender/', image: '/weekender.jpg' }) };
    const detail = { querySelector() { return script; } };
    const list = element();
    const section = { hidden: true, querySelector() { return list; } };
    const storage = run([], detail, section);
    assert.equal(section.hidden, true);
    assert.equal(JSON.parse(storage.staticbridge_recent_products_v1).length, 1);

    const other = { id: 11, name: 'Other bag', url: '/other/', image: '/other.jpg' };
    storage.staticbridge_recent_products_v1 = JSON.stringify([other, { id: 10, name: 'Old', url: '/old/', image: '/old.jpg' }]);
    run([], detail, section, storage);
    assert.equal(section.hidden, false);
    assert.equal(list.children.length, 1);
    assert.equal(list.children[0].children[0].href, '/other/');
    assert.deepEqual(JSON.parse(storage.staticbridge_recent_products_v1).map(item => item.id), [10, 11]);
});

test('variable product updates price, availability, and purchase state', () => {
    const select = element();
    select.dataset.optionKey = 'attribute_size';
    select.value = '';
    const price = element();
    price.innerHTML = '$99–$119';
    const availability = element();
    const button = element();
    const size40 = element();
    size40.dataset = { optionGroup: 'attribute_size', optionValue: 'EU 40' };
    const size42 = element();
    size42.dataset = { optionGroup: 'attribute_size', optionValue: 'EU 42' };
    const script = { textContent: JSON.stringify({
        type: 'variable', options: [{ key: 'attribute_size', label: 'Size' }],
        variations: [
            { attributes: { attribute_size: 'EU 40' }, price_html: '$99', stock_status: 'instock', purchasable: true },
            { attributes: { attribute_size: 'EU 42' }, price_html: '$119', stock_status: 'outofstock', purchasable: true }
        ]
    }) };
    const detail = {
        querySelector(selector) {
            return ({ '[data-staticbridge-product]': script, '[data-product-price]': price,
                '[data-product-availability]': availability, '[data-add-to-cart]': button })[selector];
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
    assert.equal(size40.disabled, false);
    assert.equal(size42.disabled, true);
    select.value = 'EU 40';
    select.handlers.change();
    assert.equal(price.innerHTML, '$99');
    assert.equal(button.disabled, false);
    assert.equal(availability.textContent, 'Available');
    assert.equal(size40.classList.contains('is-selected'), true);
    select.value = 'EU 42';
    select.handlers.change();
    assert.equal(button.disabled, true);
    assert.equal(availability.textContent, 'Out of stock');
    select.value = '';
    select.handlers.change();
    assert.equal(price.innerHTML, '$99–$119');
});
