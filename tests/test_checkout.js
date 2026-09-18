const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const checkout = require('../theme/assets/js/checkout.js');

test('checkout sends variation ID and normalized WooCommerce attributes', () => {
    const payload = checkout.cartLinePayload({
        productId: 20, variationId: 43, quantity: 2,
        attributes: { attribute_pa_color: 'blue', attribute_logo: 'Yes' }
    });
    assert.deepEqual(payload, {
        id: 43, quantity: 2,
        variation: [
            { attribute: 'attribute_logo', value: 'Yes' },
            { attribute: 'pa_color', value: 'blue' }
        ]
    });
    assert.deepEqual(checkout.cartLinePayload({ productId: 40, quantity: 1 }), { id: 40, quantity: 1 });
});

test('checkout totals use WooCommerce minor units and all shipping packages need rates', () => {
    assert.equal(checkout.formatMinor('1250', { currency_code: 'GBP', currency_minor_unit: 2 }), '£12.50');
    assert.equal(checkout.formatMinor('1250', { currency_code: 'ALL', currency_minor_unit: 0 }), 'ALL 1,250');
    assert.equal(checkout.hasSelectedRates({ needs_shipping: false }), true);
    assert.equal(checkout.hasSelectedRates({ needs_shipping: true, shipping_rates: [{ shipping_rates: [{ selected: true }] }, { shipping_rates: [{ selected: false }] }] }), false);
    assert.equal(checkout.hasSelectedRates({ needs_shipping: true, shipping_rates: [{ shipping_rates: [{ selected: true }] }] }), true);
    assert.equal(checkout.cartMatchesLines({ items: [{ id: 43, quantity: 2 }], errors: [] }, [{ productId: 20, variationId: 43, quantity: 2 }]), true);
    assert.equal(checkout.cartMatchesLines({ items: [{ id: 43, quantity: 1 }], errors: [] }, [{ productId: 20, variationId: 43, quantity: 2 }]), false);
});

test('billing address supplies delivery address until another address is selected', () => {
    const fields = {
        billing_name: '  Ada  Lovelace  ', billing_country: 'GB',
        billing_address_1: ' 12 Example Road ', billing_city: ' London ',
        billing_phone: ' 123456 ', billing_email: ' ada@example.com ',
        billing_postcode: ' SW1A 1AA ', billing_state: ''
    };
    const address = checkout.customerAddress({ get: key => fields[key] || '' });
    assert.deepEqual(address.shipping_address, {
        first_name: 'Ada', last_name: 'Lovelace', address_1: '12 Example Road',
        address_2: '', city: 'London', state: '', postcode: 'SW1A 1AA', country: 'GB'
    });
    assert.deepEqual(address.billing_address, {
        ...address.shipping_address, email: 'ada@example.com', phone: '123456'
    });
    assert.notStrictEqual(address.shipping_address, address.billing_address);
    const view = fs.readFileSync('theme/template-parts/views/checkout.php', 'utf8');
    assert.match(view, /data-checkout-form/);
    assert.match(view, /name="ship_to_different_address"/);
    assert.match(view, /data-shipping-fields hidden/);
    assert.match(view, /name="shipping_address_1"/);
    for (const name of Object.keys(fields)) assert.match(view, new RegExp(`name="${name}"`));
});

test('a separate shipping address is sent without changing billing details', () => {
    const fields = {
        billing_name: 'Ada Lovelace', billing_country: 'GB', billing_address_1: '12 Example Road',
        billing_city: 'London', billing_phone: '123456', billing_email: 'ada@example.com',
        ship_to_different_address: '1', shipping_name: 'Grace Hopper', shipping_country: 'US',
        shipping_address_1: '42 Navy Street', shipping_city: 'Arlington', shipping_state: 'VA',
        shipping_postcode: '22201'
    };
    const address = checkout.customerAddress({ get: key => fields[key] || '' });
    assert.equal(address.billing_address.first_name, 'Ada');
    assert.equal(address.billing_address.country, 'GB');
    assert.deepEqual(address.shipping_address, {
        first_name: 'Grace', last_name: 'Hopper', address_1: '42 Navy Street',
        address_2: '', city: 'Arlington', state: 'VA', postcode: '22201', country: 'US'
    });
});

async function checkoutScenario(failureMode, exerciseOptions) {
    function node() {
        return {
            children: [], handlers: {}, hidden: false, disabled: false,
            classList: { toggle() {} },
            addEventListener(name, handler) { this.handlers[name] = handler; },
            append(...children) { this.children.push(...children); },
            appendChild(child) { this.children.push(child); },
            replaceChildren(...children) { this.children = children; }
        };
    }
    const names = [
        'billing_name', 'billing_email', 'billing_phone',
        'billing_address_1', 'billing_city', 'billing_country', 'billing_postcode'
    ];
    const fields = Object.fromEntries(names.map(name => [name, name === 'billing_country' ? 'GB' : name === 'billing_name' ? 'Test Customer' : 'Test']));
    const form = node();
    const submit = node();
    const countryField = { ...node(), value: 'GB', getAttribute: () => JSON.stringify({
        GB: { postcode: true, state: null, states: {} },
        US: { postcode: true, state: true, states: { CA: 'California' } }
    }) };
    const postcodeField = { ...node(), value: 'Test' };
    const stateInput = node();
    const stateSelect = node();
    const postcodeRow = { ...node(), querySelector: selector => selector === 'input' ? postcodeField : node() };
    const stateRow = { ...node(), querySelector: selector => selector === 'input' ? stateInput : selector === 'select' ? stateSelect : node() };
    form.querySelector = selector => ({
        '[type="submit"]': submit, '[name="billing_country"]': countryField,
        '[data-checkout-postcode]': postcodeRow, '[data-checkout-state]': stateRow
    })[selector];
    form.querySelectorAll = () => names.map(name => ({ value: fields[name], checkValidity: () => true }));
    const nodes = Object.fromEntries([
        '[data-checkout-form]', '[data-checkout-page-status]', '[data-checkout-page-items]',
        '[data-checkout-shipping-rates]', '[data-checkout-page-subtotal]',
        '[data-checkout-shipping-total]', '[data-checkout-adjustments]', '[data-checkout-order-total]',
        '.checkout-page__layout', '[data-checkout-confirmation-number]', '[data-checkout-confirmation]'
    ].map(selector => [selector, node()]));
    nodes['[data-checkout-form]'] = form;
    const dialogs = {};
    const openButtons = [];
    if (exerciseOptions) {
        for (const name of ['note', 'shipping', 'coupon']) {
            const dialog = node();
            const dialogForm = node();
            const error = node();
            const controls = { form: dialogForm, '[data-dialog-error]': error };
            if (name === 'note') controls.textarea = { value: '' };
            if (name === 'coupon') controls['[name="code"]'] = { value: '' };
            if (name === 'shipping') controls['[name="estimate_country"]'] = node();
            dialog.querySelector = selector => controls[selector];
            dialog.querySelectorAll = () => [];
            dialog.getAttribute = () => name;
            dialog.showModal = () => { dialog.open = true; };
            dialog.close = () => { dialog.open = false; };
            dialogs[name] = dialog;
            const button = node();
            button.getAttribute = () => name;
            openButtons.push(button);
        }
    }
    const root = {
        querySelector(selector) {
            const match = selector.match(/^\[data-checkout-dialog="(.*)"\]$/);
            return match ? dialogs[match[1]] : nodes[selector];
        },
        querySelectorAll(selector) {
            return selector === '[data-checkout-dialog]' ? Object.values(dialogs) : openButtons;
        }
    };
    const events = {};
    const document = {
        querySelector: () => root, createElement: () => node(),
        addEventListener(name, handler) { events[name] = handler; },
        dispatchEvent(event) { events[event.type]?.(event); }
    };
    const storage = { staticbridge_cart_v1: JSON.stringify([{ productId: 40, quantity: 1, name: 'Bag' }]) };
    const localStorage = {
        getItem(key) { return storage[key]; }, setItem(key, value) { storage[key] = value; }
    };
    const totals = { total_items: '1000', total_shipping: '200', total_price: '1200', currency_code: 'GBP', currency_minor_unit: 2 };
    const item = { name: 'Bag', quantity: 1, totals: { line_total: '1000', currency_code: 'GBP', currency_minor_unit: 2 } };
    const rates = selected => [{ package_id: 0, shipping_rates: [
        { rate_id: 'flat_rate:1', name: 'Standard', price: '200', selected },
        { rate_id: 'flat_rate:2', name: 'Express', price: '400', selected: false }
    ] }];
    const cart = shipping_rates => ({ items: [{ ...item, id: 40 }], totals, needs_shipping: true, shipping_rates });
    const calls = [];
    const fetch = async (url, options) => {
        const path = url.split('/wc/store/v1/')[1];
        calls.push({ path, options });
        if (failureMode === 'network' && path === 'checkout') throw new Error('Connection lost');
        if ((failureMode === 'add' && path === 'cart/add-item') ||
            (failureMode === 'mismatch' && path === 'checkout')) {
            return {
                ok: false, status: failureMode === 'add' ? 400 : 409,
                headers: { get: () => 'guest-token' },
                json: async () => ({ message: failureMode === 'add' ? 'Out of stock' : 'Total changed' })
            };
        }
        const body = path === 'cart' ? { items: [], totals, needs_shipping: false, shipping_rates: [] }
            : path === 'cart/add-item' ? cart([])
            : path === 'cart/update-customer' ? cart(rates(false))
            : path === 'cart/select-shipping-rate' ? cart(rates(true))
            : path === 'cart/apply-coupon' ? { ...cart(rates(true)), coupons: [{ code: 'SAVE20' }], totals: { ...totals, total_discount: '200', total_price: '1000' } }
            : { order_id: 123, order_number: 'DB123', status: 'processing', payment_result: { payment_status: 'success' } };
        return { ok: true, headers: { get: name => name === 'Cart-Token' ? 'guest-token' : null }, json: async () => body };
    };
    const source = fs.readFileSync('theme/assets/js/checkout.js', 'utf8');
    vm.runInNewContext(source, {
        document, localStorage, fetch, FormData: class { constructor() {} get(name) { return fields[name] || ''; } },
        CustomEvent: class { constructor(type) { this.type = type; } },
        window: { StaticBridgeConfig: { apiBase: '/api/' }, addEventListener() {} },
        setTimeout: callback => global.setTimeout(callback, 0), clearTimeout: global.clearTimeout,
        Intl, Array, Object, Number, String, JSON, Math
    });
    assert.equal(postcodeField.required, true);
    assert.equal(stateRow.hidden, true);
    countryField.value = 'US';
    countryField.handlers.change();
    assert.equal(stateSelect.required, true);
    assert.equal(stateSelect.children[1].value, 'CA');
    countryField.value = 'GB';
    countryField.handlers.change();
    await new Promise(resolve => setTimeout(resolve, 30));
    if (failureMode === 'add') {
        assert.match(nodes['[data-checkout-page-status]'].textContent, /Bag: Out of stock/);
        assert.equal(submit.disabled, true);
        assert.notEqual(storage.staticbridge_cart_v1, '[]');
        return;
    }
    assert.deepEqual(calls.map(call => call.path), ['cart', 'cart/add-item', 'cart/update-customer']);
    assert.equal(calls[1].options.headers['Cart-Token'], 'guest-token');
    assert.equal(submit.disabled, true);
    assert.notEqual(storage.staticbridge_cart_v1, '[]');
    nodes['[data-checkout-shipping-rates]'].children[0].children[0].handlers.change();
    await new Promise(resolve => setTimeout(resolve, 20));
    assert.equal(submit.disabled, false);
    if (exerciseOptions) {
        openButtons[0].handlers.click();
        dialogs.note.querySelector('textarea').value = 'Leave at reception';
        dialogs.note.querySelector('form').handlers.submit({ preventDefault() {} });
        openButtons[2].handlers.click();
        dialogs.coupon.querySelector('[name="code"]').value = 'SAVE20';
        await dialogs.coupon.querySelector('form').handlers.submit({ preventDefault() {} });
        assert.equal(dialogs.coupon.open, false);
        assert.equal(nodes['[data-checkout-order-total]'].textContent, '£10.00');
        assert.equal(submit.disabled, false);
    }
    await form.handlers.submit({ preventDefault() {} });
    const order = JSON.parse(calls.find(call => call.path === 'checkout').options.body);
    assert.equal(order.expected_total, exerciseOptions ? '1000' : '1200');
    if (exerciseOptions) {
        assert.equal(order.customer_note, 'Leave at reception');
        assert.deepEqual(JSON.parse(calls.find(call => call.path === 'cart/apply-coupon').options.body), { code: 'SAVE20' });
    }
    assert.equal(order.shipping_address.first_name, 'Test');
    assert.equal(order.shipping_address.last_name, 'Customer');
    assert.equal(order.billing_address.address_1, order.shipping_address.address_1);
    if (failureMode === 'mismatch' || failureMode === 'network') {
        assert.match(nodes['[data-checkout-page-status]'].textContent,
            failureMode === 'mismatch' ? /total changed/i : /could not confirm/i);
        assert.notEqual(storage.staticbridge_cart_v1, '[]');
        if (failureMode === 'network') assert.equal(submit.disabled, true);
        return;
    }
    assert.equal(storage.staticbridge_cart_v1, '[]');
    assert.equal(nodes['[data-checkout-confirmation]'].hidden, false);
}

test('guest checkout syncs cart, selects shipping, and clears storage only after success', () => checkoutScenario());
test('failed cart sync preserves the local cart', () => checkoutScenario('add'));
test('total mismatch preserves the local cart and requests review', () => checkoutScenario('mismatch'));
test('uncertain network result preserves the local cart and blocks duplicate submit', () => checkoutScenario('network'));
test('order note and coupon update the confirmed checkout total', () => checkoutScenario(undefined, true));
