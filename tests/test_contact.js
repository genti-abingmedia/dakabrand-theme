const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../theme/assets/js/contact.js'), 'utf8');
const contactTemplate = fs.readFileSync(path.join(__dirname, '../theme/template-parts/views/contact.php'), 'utf8');

function loadForm(endpoint, fetchImpl) {
    const classes = new Set();
    const listeners = {};
    const submit = { disabled: true };
    const status = {
        textContent: '',
        classList: {
            add(name) { classes.add(name); },
            remove(name) { classes.delete(name); }
        }
    };
    const form = {
        elements: {
            name: { value: '  Ada  ' },
            email: { value: '  ada@example.test  ' },
            message: { value: '  Hello  ' }
        },
        valid: true,
        reported: false,
        resetCalled: false,
        querySelector(selector) {
            return selector === 'button[type="submit"]' ? submit : status;
        },
        addEventListener(name, callback) { listeners[name] = callback; },
        checkValidity() { return this.valid; },
        reportValidity() { this.reported = true; },
        setAttribute() {},
        removeAttribute() {},
        reset() { this.resetCalled = true; }
    };

    vm.runInNewContext(source, {
        window: { StaticBridgeContact: {
            endpoint,
            messages: {
                unavailable: 'Messages unavailable',
                success: 'Message sent',
                error: 'Send failed'
            }
        } },
        document: { querySelectorAll() { return [form]; } },
        fetch: fetchImpl
    });

    return {
        form, submit, status, classes,
        submitForm() {
            let prevented = false;
            return Promise.resolve(listeners.submit({ preventDefault() { prevented = true; } }))
                .then(() => prevented);
        }
    };
}

test('contact form never requests or claims success without an endpoint', async () => {
    let calls = 0;
    const state = loadForm('', () => { calls += 1; });

    assert.equal(state.submit.disabled, false);
    assert.equal(await state.submitForm(), true);
    assert.equal(calls, 0);
    assert.equal(state.form.resetCalled, false);
    assert.equal(state.status.textContent, 'Messages unavailable');
    assert.equal(state.classes.has('is-error'), true);
});

test('invalid contact fields block even a configured endpoint', async () => {
    let calls = 0;
    const state = loadForm('/api/contact/', () => { calls += 1; });
    state.form.valid = false;

    await state.submitForm();
    assert.equal(calls, 0);
    assert.equal(state.form.reported, true);
});

test('configured contact endpoint receives JSON and a 2xx response confirms success', async () => {
    const calls = [];
    const state = loadForm('/api/contact/', async (url, options) => {
        calls.push({ url, options });
        return { ok: true, json: async () => ({}) };
    });

    await state.submitForm();
    assert.equal(calls.length, 1);
    assert.equal(calls[0].url, '/api/contact/');
    assert.equal(calls[0].options.method, 'POST');
    assert.deepEqual(JSON.parse(calls[0].options.body), {
        name: 'Ada', email: 'ada@example.test', message: 'Hello'
    });
    assert.equal(state.form.resetCalled, true);
    assert.equal(state.status.textContent, 'Message sent');
    assert.equal(state.submit.disabled, false);
});

test('contact form keeps the message and reports a failed response', async () => {
    const state = loadForm('/api/contact/', async () => ({
        ok: false,
        json: async () => ({ message: 'Service unavailable' })
    }));

    await state.submitForm();
    assert.equal(state.form.resetCalled, false);
    assert.equal(state.status.textContent, 'Service unavailable');
    assert.equal(state.classes.has('is-error'), true);
    assert.equal(state.submit.disabled, false);
});

test('contact details use the named DAKA Brand location and support phone', () => {
    assert.match(contactTemplate, /DAKA Brand - Clothing Shop in Tirana/);
    assert.match(contactTemplate, /Rruga Muhamet Gjollesha, Tiranë 1001, Albania/);
    assert.doesNotMatch(contactTemplate, /41\.3223374,19\.8052229/);
    assert.match(contactTemplate, /tel:\+355683885286/);
    assert.doesNotMatch(contactTemplate, /\+391/);
});
