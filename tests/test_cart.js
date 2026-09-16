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

test('stock check rejects unavailable items and accepts purchasable backorders', async () => {
    assert.equal((await cart.validateStock(simple, 2)).ok, true);
    assert.equal((await cart.validateStock({ ...simple, stockStatus: 'onbackorder' }, 1)).ok, true);
    assert.equal((await cart.validateStock({ ...simple, stockStatus: 'outofstock' }, 1)).ok, false);
    assert.equal((await cart.validateStock({ ...simple, purchasable: false }, 1)).ok, false);
    assert.equal((await cart.validateStock(simple, 0)).ok, false);
});
