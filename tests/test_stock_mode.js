const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('theme/assets/js/main.js', 'utf8');

function fixture({ href = 'https://shop.test/product-category/women/', storage = {}, failStorage = false } = {}) {
    const badge = { hidden: true, textContent: '' };
    const preorder = link('preorder');
    const available = link('available');
    const listeners = {};
    const localStorage = {
        getItem(key) { if (failStorage) throw new Error('blocked'); return storage[key] || null; },
        setItem(key, value) { if (failStorage) throw new Error('blocked'); storage[key] = value; },
        removeItem(key) { if (failStorage) throw new Error('blocked'); delete storage[key]; }
    };
    const location = new URL(href);
    const document = {
        querySelectorAll(selector) {
            if (selector === '[data-stock-mode]') return [preorder, available];
            if (selector === '[data-stock-mode-badge]') return [badge];
            return [];
        },
        querySelector() { return null; },
        addEventListener(name, handler) { listeners[name] = handler; },
        dispatchEvent() {}
    };
    const window = {
        location: { href: location.href, search: location.search, origin: location.origin, pathname: location.pathname },
        StaticBridgeConfig: { stockModeStorageKey: 'stock-mode', cartStorageKey: 'cart', messages: {} },
        addEventListener() {},
        matchMedia() { return { matches: false }; }
    };
    vm.runInNewContext(source, { window, document, localStorage, URL, URLSearchParams, CustomEvent: function (type, options) { return { type, ...options }; } });
    return { badge, preorder, available, storage, window, listeners };
}

function link(mode) {
    return {
        href: 'https://shop.test/product-category/women/',
        target: '',
        dataset: {},
        handlers: {},
        getAttribute(name) { return name === 'data-stock-mode' ? mode : null; },
        hasAttribute() { return false; },
        addEventListener(name, handler) { this.handlers[name] = handler; }
    };
}

test('homepage entry actions save the expected browsing mode and update the badge', () => {
    const page = fixture();
    page.preorder.handlers.click();
    assert.equal(page.storage['stock-mode'], 'onbackorder:onbackorder');
    assert.equal(page.badge.textContent, 'Preorder Only');
    page.available.handlers.click();
    assert.equal(page.storage['stock-mode'], 'instock:instock,outofstock:outofstock');
    assert.equal(page.badge.textContent, 'Available Products');
    assert.equal(new URL(page.available.href).searchParams.get('stock_status'), 'instock:instock,outofstock:outofstock');
});

test('saved status is inherited by eligible internal links but not direct filters or excluded pages', () => {
    const page = fixture();
    page.window.StaticBridgeStockMode.saveMode('preorder');
    assert.equal(page.window.StaticBridgeStockMode.inheritUrl('/product-category/women/clothing/').searchParams.get('stock_status'), 'onbackorder:onbackorder');
    assert.equal(page.window.StaticBridgeStockMode.inheritUrl('/shop/?stock_status=instock%3Ainstock').searchParams.get('stock_status'), 'instock:instock');
    assert.equal(page.window.StaticBridgeStockMode.inheritUrl('/cart/').searchParams.has('stock_status'), false);
    assert.equal(page.window.StaticBridgeStockMode.inheritUrl('/').searchParams.has('stock_status'), false);
});

test('direct stock-status URLs are displayed without replacing the saved browsing mode', () => {
    const page = fixture({ href: 'https://shop.test/shop/?stock_status=onbackorder%3Aonbackorder', storage: { 'stock-mode': 'instock:instock,outofstock:outofstock' } });
    assert.equal(page.window.StaticBridgeStockMode.currentStatus(), 'onbackorder:onbackorder');
    assert.equal(page.storage['stock-mode'], 'instock:instock,outofstock:outofstock');
    assert.equal(page.badge.textContent, 'Preorder Only');
});

test('mixed stock-status selections are not labeled preorder only', () => {
    const page = fixture({ href: 'https://shop.test/shop/?stock_status=instock%3Ainstock%2Conbackorder%3Aonbackorder%2Coutofstock%3Aoutofstock' });
    assert.equal(page.window.StaticBridgeStockMode.modeForStatus(page.window.StaticBridgeStockMode.currentStatus()), 'available');
    assert.equal(page.badge.textContent, 'Available Products');
});

test('storage failures do not prevent stock-mode URLs from being formed', () => {
    const page = fixture({ failStorage: true });
    page.window.StaticBridgeStockMode.saveMode('preorder');
    assert.equal(page.window.StaticBridgeStockMode.inheritUrl('/product-category/man/').searchParams.has('stock_status'), false);
});
