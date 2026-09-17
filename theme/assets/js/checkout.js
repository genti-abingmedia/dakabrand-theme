(function () {
    'use strict';

    var config = typeof window === 'undefined' ? {} : (window.StaticBridgeConfig || {});
    var storageKey = config.cartStorageKey || 'staticbridge_cart_v1';

    function variationPayload(item) {
        return Object.keys(item.attributes || {}).sort().map(function (key) {
            return {
                attribute: key.indexOf('attribute_pa_') === 0 ? key.slice('attribute_'.length) : key,
                value: String(item.attributes[key])
            };
        });
    }

    function cartLinePayload(item) {
        var payload = { id: Number(item.variationId) || Number(item.productId), quantity: Number(item.quantity) };
        if (item.variationId) payload.variation = variationPayload(item);
        return payload;
    }

    function formatMinor(amount, totals) {
        var minorUnit = Number(totals.currency_minor_unit || 0);
        return new Intl.NumberFormat('en-GB', {
            style: 'currency', currency: totals.currency_code || 'GBP',
            minimumFractionDigits: minorUnit, maximumFractionDigits: minorUnit
        }).format(Number(amount || 0) / Math.pow(10, minorUnit));
    }

    function hasSelectedRates(cart) {
        return !cart.needs_shipping || (Array.isArray(cart.shipping_rates) && cart.shipping_rates.length > 0 &&
            cart.shipping_rates.every(function (pack) {
                return (pack.shipping_rates || []).some(function (rate) { return rate.selected === true; });
            }));
    }

    function cartMatchesLines(cart, lines) {
        if (!cart || !Array.isArray(cart.items) || (Array.isArray(cart.errors) && cart.errors.length)) return false;
        var requested = new Map();
        var received = new Map();
        lines.forEach(function (line) {
            var id = Number(line.variationId) || Number(line.productId);
            requested.set(id, (requested.get(id) || 0) + Number(line.quantity));
        });
        cart.items.forEach(function (line) {
            var id = Number(line.id);
            received.set(id, (received.get(id) || 0) + Number(line.quantity));
        });
        return requested.size === received.size && Array.from(requested).every(function (entry) {
            return received.get(entry[0]) === entry[1];
        });
    }

    function customerAddress(values) {
        var name = String(values.get('billing_name') || '').trim().split(/\s+/);
        var address = {
            first_name: name.shift() || '',
            last_name: name.join(' '),
            address_1: String(values.get('billing_address_1') || '').trim(),
            address_2: '',
            city: String(values.get('billing_city') || '').trim(),
            state: String(values.get('billing_state') || '').trim(),
            postcode: String(values.get('billing_postcode') || '').trim(),
            country: String(values.get('billing_country') || '').trim()
        };
        return {
            shipping_address: Object.assign({}, address),
            billing_address: Object.assign({}, address, {
                email: String(values.get('billing_email') || '').trim(),
                phone: String(values.get('billing_phone') || '').trim()
            })
        };
    }

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = { variationPayload: variationPayload, cartLinePayload: cartLinePayload,
            formatMinor: formatMinor, hasSelectedRates: hasSelectedRates, cartMatchesLines: cartMatchesLines,
            customerAddress: customerAddress };
        return;
    }

    var root = document.querySelector('[data-checkout-page]');
    if (!root) return;
    var form = root.querySelector('[data-checkout-form]');
    var submit = form.querySelector('[type="submit"]') || root.querySelector('.checkout-form__submit');
    var countryField = form.querySelector('[name="billing_country"]');
    var postcodeRow = form.querySelector('[data-checkout-postcode]');
    var postcodeField = postcodeRow.querySelector('input');
    var stateRow = form.querySelector('[data-checkout-state]');
    var stateInput = stateRow.querySelector('input');
    var stateSelect = stateRow.querySelector('select');
    var countryFields = JSON.parse(countryField.getAttribute('data-country-fields') || '{}');
    var status = root.querySelector('[data-checkout-page-status]');
    var itemsNode = root.querySelector('[data-checkout-page-items]');
    var shippingNode = root.querySelector('[data-checkout-shipping-rates]');
    var subtotalNode = root.querySelector('[data-checkout-page-subtotal]');
    var shippingTotalNode = root.querySelector('[data-checkout-shipping-total]');
    var adjustmentsNode = root.querySelector('[data-checkout-adjustments]');
    var totalNode = root.querySelector('[data-checkout-order-total]');
    var token = '';
    var cart = null;
    var syncedSignature = '';
    var addressSignature = '';
    var syncGeneration = 0;
    var syncing = false;
    var pendingSync = false;
    var addressTimer = null;
    var refreshingAddress = false;
    var selectingRate = false;
    var submitting = false;
    var uncertainOrder = false;

    function showStatus(message, error) {
        status.textContent = message;
        status.hidden = !message;
        status.classList.toggle('is-error', Boolean(error));
    }

    function readLines() {
        var value = JSON.parse(localStorage.getItem(storageKey) || '[]');
        if (!Array.isArray(value)) throw new Error('Your saved cart is invalid.');
        return value.filter(function (item) {
            return item && Number.isInteger(Number(item.productId)) && Number(item.productId) > 0 &&
                Number.isInteger(Number(item.quantity)) && Number(item.quantity) > 0;
        });
    }

    function lineSignature(lines) {
        return JSON.stringify(lines.map(function (item) {
            return [item.productId, item.variationId || 0, item.attributes || {}, item.quantity];
        }));
    }

    function addressData() {
        return customerAddress(new FormData(form));
    }

    function updateCountryFields() {
        var fields = countryFields[countryField.value] || {};
        var needsPostcode = fields.postcode !== null && fields.postcode !== undefined;
        var needsState = fields.state !== null && fields.state !== undefined;
        postcodeRow.hidden = !needsPostcode;
        postcodeField.disabled = !needsPostcode;
        postcodeField.required = needsPostcode && fields.postcode;
        postcodeRow.querySelector('[data-required-marker]').hidden = !postcodeField.required;
        stateRow.hidden = !needsState;
        var states = fields.states || {};
        var choices = Object.entries(states);
        stateInput.hidden = choices.length > 0;
        stateInput.disabled = !needsState || choices.length > 0;
        stateInput.required = needsState && !choices.length && fields.state;
        stateSelect.hidden = !choices.length;
        stateSelect.disabled = !needsState || !choices.length;
        stateSelect.required = needsState && choices.length > 0 && fields.state;
        stateRow.querySelector('[data-required-marker]').hidden = !fields.state;
        stateSelect.replaceChildren();
        if (choices.length) {
            var placeholder = element('option', '', 'Select a state / region…');
            placeholder.value = '';
            stateSelect.appendChild(placeholder);
            choices.forEach(function (choice) {
                var option = element('option', '', choice[1]);
                option.value = choice[0];
                stateSelect.appendChild(option);
            });
        }
        stateInput.value = '';
        stateSelect.value = '';
        postcodeField.value = '';
    }

    function addressComplete() {
        return Array.from(form.querySelectorAll('[required]')).every(function (field) {
            return String(field.value || '').trim() && field.checkValidity();
        });
    }

    function readyToOrder() {
        return Boolean(token && cart && !submitting && !uncertainOrder && !syncing && !pendingSync &&
            !refreshingAddress && !selectingRate &&
            !addressTimer && addressComplete() && addressSignature === JSON.stringify(addressData()) &&
            syncedSignature === lineSignature(readLines()) && hasSelectedRates(cart) &&
            cart.items && cart.items.length);
    }

    function updateSubmit() {
        try { submit.disabled = !readyToOrder(); } catch (error) { submit.disabled = true; }
    }

    async function request(path, method, body) {
        var headers = { Accept: 'application/json' };
        if (body !== undefined) headers['Content-Type'] = 'application/json';
        if (token) headers['Cart-Token'] = token;
        var response = await fetch((config.apiBase || '/api/').replace(/\/?$/, '/') + 'wc/store/v1/' + path, {
            method: method || 'GET', headers: headers, body: body === undefined ? undefined : JSON.stringify(body),
            credentials: 'omit', cache: 'no-store'
        });
        var result = await response.json().catch(function () { return {}; });
        var nextToken = response.headers.get('Cart-Token');
        if (nextToken) token = nextToken;
        if (!response.ok) {
            var error = new Error(result.message || 'The store could not complete this request.');
            error.status = response.status;
            error.data = result.data;
            throw error;
        }
        return result;
    }

    function element(tag, className, value) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (value !== undefined) node.textContent = String(value);
        return node;
    }

    function renderItems(current) {
        itemsNode.replaceChildren();
        (current.items || []).forEach(function (item) {
            var row = element('div', 'checkout-order__item');
            row.appendChild(element('span', '', item.name + ' × ' + item.quantity));
            row.appendChild(element('strong', '', formatMinor(item.totals.line_total, item.totals)));
            itemsNode.appendChild(row);
        });
    }

    function selectedShippingLabel(current) {
        var labels = [];
        (current.shipping_rates || []).forEach(function (pack) {
            var selected = (pack.shipping_rates || []).find(function (rate) { return rate.selected === true; });
            if (selected && selected.name) labels.push(selected.name);
        });
        return labels.filter(function (label, index) { return labels.indexOf(label) === index; }).join(', ');
    }

    function selectedPaymentMethod() {
        var option = root.querySelector('[name="checkout_payment_option"]:checked');
        return option && option.value === 'remittance' ? 'staticbridge_remittance' : 'cod';
    }

    function renderShipping(current) {
        shippingNode.replaceChildren();
        if (!current.needs_shipping) {
            shippingNode.appendChild(element('p', '', 'Shipping is not required.'));
            return;
        }
        if (!addressComplete()) {
            shippingNode.appendChild(element('p', '', 'Enter your address to see shipping options.'));
            return;
        }
        var packages = Array.isArray(current.shipping_rates) ? current.shipping_rates : [];
        if (!packages.length || packages.some(function (pack) { return !(pack.shipping_rates || []).length; })) {
            shippingNode.appendChild(element('p', '', 'No shipping method is available for this address.'));
            return;
        }
        packages.forEach(function (pack) {
            (pack.shipping_rates || []).forEach(function (rate) {
                var label = element('label', 'checkout-shipping-rate');
                var input = element('input');
                input.type = 'radio';
                input.name = 'shipping_package_' + pack.package_id;
                input.value = rate.rate_id;
                input.checked = rate.selected === true;
                input.disabled = refreshingAddress || selectingRate || submitting;
                input.addEventListener('change', function () { selectRate(pack.package_id, rate.rate_id); });
                label.append(input, element('span', '', rate.name || 'Shipping'),
                    element('strong', '', formatMinor(rate.price, current.totals)));
                shippingNode.appendChild(label);
            });
        });
    }

    function renderCart(current) {
        cart = current;
        renderItems(current);
        subtotalNode.textContent = formatMinor(current.totals.total_items, current.totals);
        shippingTotalNode.textContent = selectedShippingLabel(current) || '—';
        adjustmentsNode.replaceChildren();
        [
            ['Discount', current.totals.total_discount, true],
            ['Fees', current.totals.total_fees, false],
            ['Tax', current.totals.total_tax, false]
        ].forEach(function (entry) {
            if (!Number(entry[1])) return;
            var row = element('div', 'checkout-order__subtotal');
            row.append(element('span', '', entry[0]),
                element('strong', '', (entry[2] ? '−' : '') + formatMinor(entry[1], current.totals)));
            adjustmentsNode.appendChild(row);
        });
        totalNode.textContent = formatMinor(current.totals.total_price, current.totals);
        renderShipping(current);
        updateSubmit();
    }

    async function syncCart() {
        if (syncing || refreshingAddress || selectingRate) {
            pendingSync = true;
            updateSubmit();
            return;
        }
        syncing = true;
        var generation = ++syncGeneration;
        var lines;
        token = '';
        cart = null;
        syncedSignature = '';
        addressSignature = '';
        updateSubmit();
        try {
            lines = readLines();
            if (!lines.length) return;
            showStatus('Checking your cart with the store…', false);
            var current = await request('cart', 'GET');
            if (!token) throw new Error('The store did not provide a checkout cart token.');
            for (var i = 0; i < lines.length; i += 1) {
                try {
                    current = await request('cart/add-item', 'POST', cartLinePayload(lines[i]));
                } catch (error) {
                    throw new Error(lines[i].name + ': ' + error.message);
                }
            }
            if (!cartMatchesLines(current, lines)) throw new Error('The store cart did not match your saved items. Review your cart and try again.');
            if (generation !== syncGeneration) return;
            if (lineSignature(readLines()) !== lineSignature(lines)) return syncCart();
            syncedSignature = lineSignature(lines);
            renderCart(current);
            showStatus('', false);
            scheduleAddress();
        } catch (error) {
            if (generation !== syncGeneration) return;
            showStatus(error.message || 'Could not check your cart. Reload the page to try again.', true);
            updateSubmit();
        } finally {
            syncing = false;
            if (pendingSync) {
                pendingSync = false;
                syncCart();
            } else updateSubmit();
        }
    }

    function scheduleAddress() {
        if (addressTimer) clearTimeout(addressTimer);
        addressTimer = null;
        addressSignature = '';
        updateSubmit();
        if (!addressComplete() || !token || !cart) {
            if (cart) renderShipping(cart);
            return;
        }
        addressTimer = setTimeout(function () {
            addressTimer = null;
            refreshAddress();
        }, 650);
    }

    async function refreshAddress() {
        if (!token || !cart || !addressComplete()) return;
        if (refreshingAddress || selectingRate) return scheduleAddress();
        var data = addressData();
        var signature = JSON.stringify(data);
        refreshingAddress = true;
        updateSubmit();
        showStatus('Updating shipping and totals…', false);
        try {
            var current = await request('cart/update-customer', 'POST', data);
            if (signature !== JSON.stringify(addressData())) return scheduleAddress();
            addressSignature = signature;
            renderCart(current);
            showStatus(hasSelectedRates(current) ? '' : 'Choose a shipping method to continue.', false);
        } catch (error) {
            showStatus(error.message || 'Could not update shipping. Please try again.', true);
        } finally {
            refreshingAddress = false;
            if (cart) renderShipping(cart);
            updateSubmit();
            if (pendingSync) {
                pendingSync = false;
                syncCart();
            }
        }
    }

    async function selectRate(packageId, rateId) {
        if (!token || refreshingAddress || selectingRate) return;
        selectingRate = true;
        updateSubmit();
        try {
            var current = await request('cart/select-shipping-rate', 'POST', {
                package_id: Number(packageId), rate_id: rateId
            });
            renderCart(current);
            showStatus('', false);
        } catch (error) {
            showStatus(error.message || 'Could not select this shipping method.', true);
        } finally {
            selectingRate = false;
            if (cart) renderShipping(cart);
            updateSubmit();
            if (pendingSync) {
                pendingSync = false;
                syncCart();
            }
        }
    }

    function onAddressChange(event) {
        if (event.target.name && event.target.name.indexOf('shipping_package_') === 0) return;
        if (event.target === countryField) return;
        scheduleAddress();
    }

    function onCountryChange() {
        updateCountryFields();
        scheduleAddress();
    }

    form.addEventListener('input', onAddressChange);
    form.addEventListener('change', onAddressChange);
    countryField.addEventListener('change', onCountryChange);
    updateCountryFields();
    form.addEventListener('submit', async function (event) {
        event.preventDefault();
        if (!readyToOrder()) {
            showStatus('Wait for the latest shipping and total, then try again.', true);
            return;
        }
        submitting = true;
        updateSubmit();
        showStatus('Placing your order…', false);
        var data = addressData();
        data.payment_method = selectedPaymentMethod();
        data.payment_data = [];
        data.expected_total = String(cart.totals.total_price);
        try {
            var result = await request('checkout', 'POST', data);
            if (!result.order_id || result.status === 'checkout-draft' ||
                (result.payment_result && result.payment_result.payment_status === 'failure')) {
                throw new Error('The store did not confirm this order.');
            }
            localStorage.setItem(storageKey, '[]');
            document.dispatchEvent(new CustomEvent('staticbridge:cart-cleared'));
            root.querySelector('.checkout-page__layout').hidden = true;
            root.querySelector('[data-checkout-confirmation-number]').textContent =
                'Order number: ' + String(result.order_number || result.order_id);
            root.querySelector('[data-checkout-confirmation]').hidden = false;
            showStatus('', false);
        } catch (error) {
            if (error.status === 409) {
                showStatus('The order total changed. Review the updated total before placing the order again.', true);
                try { renderCart(await request('cart', 'GET')); } catch (refreshError) { /* Keep the original error. */ }
            } else if (!error.status || error.status >= 500) {
                uncertainOrder = true;
                showStatus('We could not confirm whether the order was placed. Check your email or contact us before trying again.', true);
            } else {
                showStatus(error.message || 'The order could not be placed. Please review your details.', true);
            }
        } finally {
            submitting = false;
            updateSubmit();
        }
    });

    document.addEventListener('staticbridge:cart-updated', function () {
        if (!submitting) syncCart();
    });
    window.addEventListener('storage', function (event) {
        if (event.key === storageKey && !submitting) syncCart();
    });
    syncCart();
}());
