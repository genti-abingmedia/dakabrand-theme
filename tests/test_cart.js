const test = require('node:test');
const assert = require('node:assert/strict');
const cart = require('../theme/assets/js/cart.js');

const simple = {
    productId: 10, variationId: null, attributes: {}, labels: {}, name: 'Bag',
    permalink: '/product/bag/', image: '/bag.jpg', unitPrice: 149, currency: 'USD',
    stockStatus: 'instock', purchasable: true, quantity: 1
};

test('normalizes saved lines and computes count and subtotal after reload', () => {
    const saved = JSON.stringify([simple, { ...simple, productId: 11, quantity: 2, unitPrice: 20 }]);
    const lines = cart.normalizeCart(JSON.parse(saved));
    assert.equal(cart.cartCount(lines), 3);
    assert.equal(cart.cartTotal(lines), 189);
    assert.equal(cart.normalizeCart([{ ...simple, quantity: 0 }, null]).length, 0);
});

test('shared cart summary handles a single currency and mixed currencies', () => {
    const usd = { ...simple, productId: 11, quantity: 2, unitPrice: 20 };
    assert.deepEqual(cart.cartSummary([simple, usd]), { count: 3, total: 189, currency: 'USD' });
    assert.deepEqual(cart.cartSummary([simple, { ...usd, currency: 'GBP' }]), { count: 3, total: 189, currency: null });
});

test('retains and identifies a discounted unit price for drawer rendering', () => {
    const discounted = cart.normalizeCart([{ ...simple, unitPrice: 80, regularPrice: 100 }])[0];
    assert.equal(discounted.regularPrice, 100);
    assert.equal(cart.hasDiscount(discounted), true);
    assert.equal(cart.hasDiscount({ ...discounted, regularPrice: 80 }), false);
});

test('same variation and options merge, distinct options have distinct keys', () => {
    const red = { ...simple, productId: 20, variationId: 101, attributes: { attribute_pa_color: 'red' } };
    const blue = { ...red, attributes: { attribute_pa_color: 'blue' } };
    assert.equal(cart.lineKey(red), cart.lineKey({ ...red, quantity: 3 }));
    assert.notEqual(cart.lineKey(red), cart.lineKey(blue));
    assert.notEqual(cart.lineKey(red), cart.lineKey(simple));
});

test('requested product quantities are positive whole numbers for cart-line merging', () => {
    assert.equal(cart.requestedQuantity(3), 3);
    assert.equal(cart.requestedQuantity('2.8'), 2);
    assert.equal(cart.requestedQuantity(0), 1);
    assert.equal(cart.requestedQuantity('not-a-number'), 1);
    const existing = { ...simple, quantity: 2 };
    const requested = cart.requestedQuantity(3);
    assert.equal(existing.quantity + requested, 5);
});

test('variation matching requires valid selections and permits WooCommerce wildcard values', () => {
    const product = {
        options: [
            { key: 'attribute_pa_color', choices: [{ value: 'red' }, { value: 'blue' }] },
            { key: 'attribute_pa_size', choices: [{ value: 's' }, { value: 'm' }] }
        ],
        variations: [
            { variation_id: 101, attributes: { attribute_pa_color: 'red', attribute_pa_size: 's' } },
            { variation_id: 102, attributes: { attribute_pa_color: 'blue', attribute_pa_size: '' } }
        ]
    };
    assert.equal(cart.findVariation(product, { attribute_pa_color: 'red' }), null);
    assert.equal(cart.findVariation(product, { attribute_pa_color: 'green', attribute_pa_size: 's' }), null);
    assert.equal(cart.findVariation(product, { attribute_pa_color: 'red', attribute_pa_size: 'm' }), null);
    assert.equal(cart.findVariation(product, { attribute_pa_color: 'blue', attribute_pa_size: 'm' }).variation_id, 102);
});

test('live stock lookup checks total quantity and selected variation', async () => {
    const previousFetch = global.fetch;
    const requests = [];
    global.fetch = async (url, options) => {
        requests.push({ url, options });
        return { ok: true, json: async () => ({
            id: 101, is_purchasable: true, is_in_stock: true,
            add_to_cart: { minimum: 1, maximum: 2, multiple_of: 1 }
        }) };
    };
    try {
        const variation = { ...simple, variationId: 101 };
        assert.equal((await cart.validateStock(variation, 2)).ok, true);
        const unavailable = await cart.validateStock(variation, 3);
        assert.equal(unavailable.ok, false);
        assert.equal(unavailable.message, 'Only 2 left in stock.');
        assert.equal(requests[0].url, '/api/wc/store/v1/products/101');
        assert.equal(requests[0].options.cache, 'no-store');
        assert.equal((await cart.validateStock(variation, 0)).ok, false);
    } finally {
        global.fetch = previousFetch;
    }
});

test('stock check fails closed on sold-out and network failure, accepts backorders', async () => {
    assert.equal(cart.stockDecision({ is_purchasable: false, is_in_stock: true, add_to_cart: { maximum: 4 } }, 1).ok, false);
    assert.equal(cart.stockDecision({ is_purchasable: true, is_in_stock: false, is_on_backorder: false, add_to_cart: { maximum: 4 } }, 1).ok, false);
    assert.equal(cart.stockDecision({ is_purchasable: true, is_in_stock: false, is_on_backorder: true, add_to_cart: { maximum: 4 } }, 1).ok, true);
    const previousFetch = global.fetch;
    global.fetch = async () => { throw new Error('offline'); };
    try { assert.equal((await cart.validateStock(simple, 1)).ok, false); }
    finally { global.fetch = previousFetch; }
});
