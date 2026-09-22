(function () {
    'use strict';

    var config = typeof window === 'undefined' ? {} : (window.StaticBridgeConfig || {});
    var storageKey = config.cartStorageKey || 'staticbridge_cart_v1';
    var locale = String(config.locale || 'en-GB').replace('_', '-');

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
        return new Intl.NumberFormat(locale, {
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
        var address = {
            first_name: String(values.get('billing_first_name') || '').trim(),
            last_name: String(values.get('billing_last_name') || '').trim(),
            address_1: String(values.get('billing_address_1') || '').trim(),
            address_2: '',
            city: String(values.get('billing_city') || '').trim(),
            state: String(values.get('billing_state') || '').trim(),
            postcode: String(values.get('billing_postcode') || '').trim(),
            country: String(values.get('billing_country') || '').trim(),
            // The Store API can require a delivery phone even though the form
            // collects it under billing details. Reuse that single required
            // phone number for the shipping contact.
            phone: String(values.get('billing_phone') || '').trim()
        };
        var shipping = Object.assign({}, address);
        if (values.get('ship_to_different_address')) {
            shipping = {
                first_name: String(values.get('shipping_first_name') || '').trim(),
                last_name: String(values.get('shipping_last_name') || '').trim(),
                address_1: String(values.get('shipping_address_1') || '').trim(),
                address_2: '',
                city: String(values.get('shipping_city') || '').trim(),
                state: String(values.get('shipping_state') || '').trim(),
                postcode: String(values.get('shipping_postcode') || '').trim(),
                country: String(values.get('shipping_country') || '').trim(),
                phone: address.phone
            };
        }
        return {
            shipping_address: shipping,
            billing_address: Object.assign({}, address, {
                email: String(values.get('billing_email') || '').trim(),
                phone: String(values.get('billing_phone') || '').trim()
            })
        };
    }

    function enabledPaymentMethods(cart) {
        var seen = new Set();
        return (Array.isArray(cart && cart.payment_methods) ? cart.payment_methods : []).filter(function (method) {
            method = String(method || '').trim();
            if (!method || seen.has(method)) return false;
            seen.add(method);
            return true;
        });
    }

    function paymentMethodLabel(method) {
        var labels = {
            cod: 'Cash on delivery',
            bacs: 'Direct bank transfer',
            cheque: 'Cheque payment',
            staticbridge_remittance: 'Western Union / MoneyGram / Ria'
        };
        return labels[method] || String(method || '').replace(/[-_]+/g, ' ').replace(/\b\w/g, function (letter) { return letter.toUpperCase(); });
    }

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = { variationPayload: variationPayload, cartLinePayload: cartLinePayload,
            formatMinor: formatMinor, hasSelectedRates: hasSelectedRates, cartMatchesLines: cartMatchesLines,
            customerAddress: customerAddress, enabledPaymentMethods: enabledPaymentMethods,
            paymentMethodLabel: paymentMethodLabel };
        return;
    }

    var root = document.querySelector('[data-checkout-page]');
    if (!root) return;
    var form = root.querySelector('[data-checkout-form]');
    var submit = form.querySelector('[type="submit"]') || root.querySelector('.checkout-form__submit');
    var countryField = form.querySelector('[name="billing_country"]');
    var differentField = form.querySelector('[data-ship-different]');
    var shippingFields = form.querySelector('[data-shipping-fields]');
    var shippingCountry = form.querySelector('[name="shipping_country"]');
    var stateRow = form.querySelector('[data-checkout-state]');
    var stateInput = stateRow.querySelector('input');
    var stateSelect = stateRow.querySelector('select');
    var countryFields = JSON.parse(countryField.getAttribute('data-country-fields') || '{}');
    var status = root.querySelector('[data-checkout-page-status]');
    var itemsNode = root.querySelector('[data-checkout-page-items]');
    var shippingNode = root.querySelector('[data-checkout-shipping-rates]');
    var shippingMethodsSection = root.querySelector('[data-checkout-shipping-methods]');
    var subtotalNode = root.querySelector('[data-checkout-page-subtotal]');
    var shippingTotalNode = root.querySelector('[data-checkout-shipping-total]');
    var adjustmentsNode = root.querySelector('[data-checkout-adjustments]');
    var totalNode = root.querySelector('[data-checkout-order-total]');
    var paymentMethodsNode = root.querySelector('[data-checkout-payment-methods]');
    var paymentNote = root.querySelector('[data-checkout-payment-note]');
    var summary = root.querySelector('[data-checkout-page-summary]');
    var token = '';
    var cart = null;
    var syncedSignature = '';
    var addressSignature = '';
    var syncGeneration = 0;
    var syncing = false;
    var pendingSync = false;
    var addressTimer = null;
    var refreshingAddress = false;
    var refreshingAddressSignature = '';
    var selectingRate = false;
    var submitting = false;
    var uncertainOrder = false;
    var customerNote = '';
    var estimateAddress = false;
    var couponBusy = false;
    var appliedCouponCodes = [];
    var paymentMethods = [];
    var selectedPaymentId = '';
    var snackbarShownAt = 0;
    var snackbarHideTimer = null;
    var snackbarMinimumDuration = 3000;

    function showStatus(message, error) {
        if (snackbarHideTimer) {
            clearTimeout(snackbarHideTimer);
            snackbarHideTimer = null;
        }
        if (!message) {
            if (status.hidden) return;
            var remaining = Math.max(0, snackbarMinimumDuration - (Date.now() - snackbarShownAt));
            snackbarHideTimer = setTimeout(function () {
                status.hidden = true;
                snackbarHideTimer = null;
            }, remaining);
            return;
        }
        snackbarShownAt = Date.now();
        status.textContent = message;
        status.hidden = false;
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

    function updateCountryFields(prefix, preserve) {
        var country = prefix === 'shipping' ? shippingCountry : countryField;
        var rowState = prefix === 'shipping' ? form.querySelector('[data-shipping-state]') : stateRow;
        var inputState = rowState.querySelector('input');
        var selectState = rowState.querySelector('select');
        var oldState = preserve ? (selectState.hidden ? inputState.value : selectState.value) : '';
        var fields = countryFields[country.value] || {};
        var needsState = fields.state !== null && fields.state !== undefined;
        var enabled = prefix !== 'shipping' || (differentField && differentField.checked);
        // Keep the field in its country/state row before a country is chosen;
        // the control itself remains disabled until that choice determines its type.
        rowState.hidden = false;
        var states = fields.states || {};
        var choices = Object.entries(states);
        inputState.hidden = choices.length > 0;
        inputState.disabled = !needsState || choices.length > 0 || !enabled;
        inputState.placeholder = country.value ? '' : 'Select a country first';
        inputState.required = Boolean(needsState && !choices.length && fields.state && enabled);
        selectState.hidden = !choices.length;
        selectState.disabled = !needsState || !choices.length || !enabled;
        selectState.required = Boolean(needsState && choices.length > 0 && fields.state && enabled);
        rowState.querySelector('[data-required-marker]').hidden = !fields.state;
        selectState.replaceChildren();
        if (choices.length) {
            var placeholder = element('option', '', 'Select a state / region…');
            placeholder.value = '';
            selectState.appendChild(placeholder);
            choices.forEach(function (choice) {
                var option = element('option', '', choice[1]);
                option.value = choice[0];
                selectState.appendChild(option);
            });
        }
        inputState.value = oldState;
        selectState.value = oldState;
    }

    function updateDifferentAddress() {
        if (!differentField) return;
        var enabled = differentField.checked;
        shippingFields.hidden = !enabled;
        shippingFields.querySelectorAll('input, select').forEach(function (field) {
            if (field.name === 'shipping_state') return;
            field.disabled = !enabled;
        });
        updateCountryFields('shipping', true);
        scheduleAddress();
    }

    function addressComplete() {
        return Array.from(form.querySelectorAll('[required]')).every(function (field) {
            return field.disabled || (String(field.value || '').trim() && field.checkValidity());
        });
    }

    function readyToOrder() {
        return Boolean(token && cart && !submitting && !uncertainOrder && !syncing && !pendingSync &&
            !refreshingAddress && !selectingRate && !couponBusy &&
            !addressTimer && addressComplete() && addressSignature === JSON.stringify(addressData()) &&
            syncedSignature === lineSignature(readLines()) && hasSelectedRates(cart) &&
            Boolean(selectedPaymentMethod()) && cart.items && cart.items.length);
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

    function safeImageUrl(value) {
        try {
            var url = new URL(String(value || ''), window.location.href);
            return url.protocol === 'https:' || url.protocol === 'http:' ? url.href : '';
        } catch (error) {
            return '';
        }
    }

    function itemImageUrl(item) {
        var image = Array.isArray(item.images) ? item.images[0] : null;
        return image ? safeImageUrl(image.thumbnail || image.src || image.url) : '';
    }

    function renderItems(current) {
        itemsNode.replaceChildren();
        (current.items || []).forEach(function (item) {
            var row = element('div', 'checkout-order__item');
            var imageUrl = itemImageUrl(item);
            var copy = element('div', 'checkout-order__item-copy');
            var title = element('strong', 'checkout-order__item-title', item.name || 'Product');
            title.appendChild(element('span', 'checkout-order__item-quantity', ' ×' + item.quantity));
            copy.appendChild(title);
            (item.item_data || []).forEach(function (detail) {
                var value = String(detail.value || '').trim();
                if (value) copy.appendChild(element('span', 'checkout-order__item-detail', value));
            });
            if (imageUrl) {
                var image = element('img', 'checkout-order__item-image');
                image.src = imageUrl;
                image.alt = '';
                image.width = 56;
                image.height = 67;
                image.loading = 'eager';
                row.appendChild(image);
            } else {
                row.appendChild(element('span', 'checkout-order__item-image checkout-order__item-image--empty'));
            }
            row.append(copy, element('strong', 'checkout-order__item-price', formatMinor(item.totals.line_total, item.totals)));
            itemsNode.appendChild(row);
        });
        if (itemsNode.removeAttribute) itemsNode.removeAttribute('aria-hidden');
        if (summary) {
            summary.classList.remove('checkout-order--loading');
            summary.setAttribute('aria-busy', 'false');
        }
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
        return paymentMethods.includes(selectedPaymentId) ? selectedPaymentId : '';
    }

    function confirmationPaymentLabel() {
        return paymentMethodLabel(selectedPaymentMethod());
    }

    function renderPaymentMethods(current) {
        paymentMethods = enabledPaymentMethods(current);
        if (!paymentMethods.includes(selectedPaymentId)) selectedPaymentId = paymentMethods[0] || '';
        if (!paymentMethodsNode) return;
        paymentMethodsNode.replaceChildren();
        if (!paymentMethods.length) {
            paymentMethodsNode.appendChild(element('p', '', 'No payment methods are currently available.'));
        } else {
            paymentMethods.forEach(function (method) {
                var label = element('label', 'checkout-payment-method');
                var input = element('input');
                input.type = 'radio';
                input.name = 'payment_method';
                input.value = method;
                input.checked = method === selectedPaymentId;
                input.disabled = submitting;
                input.addEventListener('change', function () {
                    selectedPaymentId = method;
                    renderPaymentMethods(current);
                    updateSubmit();
                });
                label.append(input, element('span', '', paymentMethodLabel(method)));
                if (method === 'staticbridge_remittance') label.appendChild(element('span', 'checkout-payment-method__western-union', 'WESTERN UNION'));
                paymentMethodsNode.appendChild(label);
            });
        }
        if (paymentNote) paymentNote.hidden = selectedPaymentId !== 'staticbridge_remittance';
    }

    function confirmationDeliveryLabel() {
        var useShipping = differentField && differentField.checked;
        var city = String(form.elements[useShipping ? 'shipping_city' : 'billing_city'].value || '').trim();
        var selectedCountry = useShipping ? shippingCountry : countryField;
        var country = selectedCountry.options[selectedCountry.selectedIndex];
        var countryName = country ? country.textContent.trim() : '';
        return [city, countryName].filter(Boolean).join(', ') || 'Delivery address confirmed';
    }

    function confirmationRow(label, value, modifier) {
        var row = element('div', 'checkout-confirmation__total' + (modifier ? ' checkout-confirmation__total--' + modifier : ''));
        row.append(element('span', '', label), element('strong', '', value));
        return row;
    }

    function renderConfirmation(current) {
        var confirmation = root.querySelector('[data-checkout-confirmation]');
        var items = confirmation.querySelector('[data-checkout-confirmation-items]');
        var totals = confirmation.querySelector('[data-checkout-confirmation-totals]');
        var payment = confirmation.querySelector('[data-checkout-confirmation-payment]');
        var delivery = confirmation.querySelector('[data-checkout-confirmation-delivery]');

        items.replaceChildren();
        (current.items || []).forEach(function (item) {
            var row = element('div', 'checkout-confirmation__item');
            var description = element('div', 'checkout-confirmation__item-description');
            description.append(element('strong', '', item.name || 'Product'), element('span', '', 'Quantity ' + item.quantity));
            row.append(description, element('strong', '', formatMinor(item.totals.line_total, current.totals)));
            items.appendChild(row);
        });

        totals.replaceChildren();
        totals.appendChild(confirmationRow('Subtotal', formatMinor(current.totals.total_items, current.totals)));
        if (Number(current.totals.total_discount)) {
            totals.appendChild(confirmationRow('Discount', '−' + formatMinor(current.totals.total_discount, current.totals), 'discount'));
        }
        totals.appendChild(confirmationRow(selectedShippingLabel(current) || 'Shipping', formatMinor(current.totals.total_shipping, current.totals)));
        totals.appendChild(confirmationRow('Total', formatMinor(current.totals.total_price, current.totals), 'grand'));
        payment.textContent = confirmationPaymentLabel();
        delivery.textContent = confirmationDeliveryLabel();
    }

    function renderShipping(current) {
        shippingNode.replaceChildren();
        if (shippingMethodsSection) shippingMethodsSection.hidden = true;
        if (!current.needs_shipping) {
            return;
        }
        if (!addressComplete() && !estimateAddress) {
            return;
        }
        var packages = Array.isArray(current.shipping_rates) ? current.shipping_rates : [];
        if (!packages.length || packages.some(function (pack) { return !(pack.shipping_rates || []).length; })) {
            return;
        }
        var rateCount = packages.reduce(function (total, pack) { return total + (pack.shipping_rates || []).length; }, 0);
        if (rateCount < 2) return;
        if (shippingMethodsSection) shippingMethodsSection.hidden = false;
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
        appliedCouponCodes = (current.coupons || []).map(function (coupon) { return coupon.code; });
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
        appliedCouponCodes.forEach(function (code) {
            var row = element('div', 'checkout-order__coupon');
            var button = element('button', '', 'Remove');
            button.type = 'button';
            button.addEventListener('click', function () { removeCoupon(code); });
            row.append(element('span', '', 'Coupon: ' + code), button);
            adjustmentsNode.appendChild(row);
        });
        totalNode.textContent = formatMinor(current.totals.total_price, current.totals);
        renderShipping(current);
        renderPaymentMethods(current);
        updateSubmit();
    }

    async function syncCart() {
        if (syncing || refreshingAddress || selectingRate || couponBusy) {
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
            if (!lines.length) {
                if (summary) summary.hidden = true;
                form.hidden = true;
                showStatus('Your cart is empty.', false);
                return;
            }
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
            var couponMessage = '';
            for (var j = 0; j < appliedCouponCodes.length; j += 1) {
                try {
                    current = await request('cart/apply-coupon', 'POST', { code: appliedCouponCodes[j] });
                } catch (error) {
                    if (!error.status || error.status >= 500) throw error;
                    couponMessage = 'A coupon no longer applies to this cart and was removed.';
                }
            }
            if (!cartMatchesLines(current, lines)) throw new Error('The store cart did not match your saved items. Review your cart and try again.');
            if (generation !== syncGeneration) return;
            if (lineSignature(readLines()) !== lineSignature(lines)) return syncCart();
            syncedSignature = lineSignature(lines);
            renderCart(current);
            showStatus(couponMessage, false);
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
        estimateAddress = false;
        var signature = JSON.stringify(addressData());
        if (!addressComplete() || !token || !cart) {
            addressSignature = '';
            updateSubmit();
            if (cart) renderShipping(cart);
            return;
        }
        // Browsers often emit both input and change for the same edit. Do not
        // recalculate shipping when that exact address is already applied or
        // currently being sent to the Store API.
        if (signature === addressSignature || signature === refreshingAddressSignature) {
            updateSubmit();
            return;
        }
        updateSubmit();
        addressTimer = setTimeout(function () {
            addressTimer = null;
            refreshAddress();
        }, 650);
    }

    async function refreshAddress() {
        if (!token || !cart || !addressComplete()) return;
        var data = addressData();
        var signature = JSON.stringify(data);
        if (signature === addressSignature || signature === refreshingAddressSignature) return;
        if (refreshingAddress || selectingRate || couponBusy) return scheduleAddress();
        refreshingAddress = true;
        refreshingAddressSignature = signature;
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
            refreshingAddressSignature = '';
            if (cart) renderShipping(cart);
            updateSubmit();
            if (pendingSync) {
                pendingSync = false;
                syncCart();
            }
        }
    }

    async function selectRate(packageId, rateId) {
        if (!token || refreshingAddress || selectingRate || couponBusy) return;
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

    async function removeCoupon(code) {
        if (!token || couponBusy || submitting || syncing || refreshingAddress || selectingRate) return;
        couponBusy = true;
        updateSubmit();
        try {
            renderCart(await request('cart/remove-coupon', 'POST', { code: code }));
            showStatus('Coupon removed.', false);
        } catch (error) {
            showStatus(error.message || 'Could not remove the coupon.', true);
        } finally {
            couponBusy = false;
            updateSubmit();
            if (pendingSync) {
                pendingSync = false;
                syncCart();
            }
        }
    }

    function dialogError(dialog, message) {
        var node = dialog.querySelector('[data-dialog-error]');
        if (!node) return;
        node.textContent = message;
        node.hidden = !message;
    }

    function setupDialogs() {
        if (typeof root.querySelectorAll !== 'function') return;
        var dialogs = root.querySelectorAll('[data-checkout-dialog]');
        if (!dialogs || !dialogs.length) return;
        root.querySelectorAll('[data-checkout-open]').forEach(function (button) {
            button.addEventListener('click', function () {
                var dialog = root.querySelector('[data-checkout-dialog="' + button.getAttribute('data-checkout-open') + '"]');
                if (!dialog) return;
                dialogError(dialog, '');
                if (dialog.getAttribute('data-checkout-dialog') === 'shipping') prepareEstimate(dialog);
                dialog.showModal();
            });
        });
        dialogs.forEach(function (dialog) {
            dialog.querySelectorAll('[data-checkout-close]').forEach(function (button) {
                button.addEventListener('click', function () { dialog.close(); });
            });
            dialog.addEventListener('click', function (event) {
                if (event.target === dialog) dialog.close();
            });
        });
        var noteDialog = root.querySelector('[data-checkout-dialog="note"]');
        noteDialog.querySelector('form').addEventListener('submit', function (event) {
            event.preventDefault();
            customerNote = noteDialog.querySelector('textarea').value.trim();
            noteDialog.close();
            showStatus(customerNote ? 'Order note saved.' : 'Order note cleared.', false);
        });
        var couponDialog = root.querySelector('[data-checkout-dialog="coupon"]');
        couponDialog.querySelector('form').addEventListener('submit', async function (event) {
            event.preventDefault();
            if (!token || !cart || syncing || refreshingAddress || selectingRate || couponBusy || submitting) {
                dialogError(couponDialog, 'Wait for your cart to finish updating, then try again.');
                return;
            }
            var code = couponDialog.querySelector('[name="code"]').value.trim();
            if (!code) return;
            couponBusy = true;
            dialogError(couponDialog, '');
            updateSubmit();
            try {
                renderCart(await request('cart/apply-coupon', 'POST', { code: code }));
                couponDialog.close();
                showStatus('Coupon applied.', false);
            } catch (error) {
                dialogError(couponDialog, error.message || 'Could not apply this coupon.');
            } finally {
                couponBusy = false;
                updateSubmit();
                if (pendingSync) {
                    pendingSync = false;
                    syncCart();
                }
            }
        });
        var estimateDialog = root.querySelector('[data-checkout-dialog="shipping"]');
        var estimateCountry = estimateDialog.querySelector('[name="estimate_country"]');
        estimateCountry.addEventListener('change', function () { updateEstimateCountry(estimateDialog); });
        estimateDialog.querySelector('form').addEventListener('submit', async function (event) {
            event.preventDefault();
            if (!token || !cart || syncing || refreshingAddress || selectingRate || couponBusy || submitting) {
                dialogError(estimateDialog, 'Wait for your cart to finish updating, then try again.');
                return;
            }
            var stateSelect = estimateDialog.querySelector('[name="estimate_state"]');
            var stateInput = estimateDialog.querySelector('[name="estimate_state_text"]');
            var estimated = addressData();
            estimated.shipping_address = Object.assign({}, estimated.shipping_address, {
                country: estimateCountry.value,
                state: stateSelect.hidden ? stateInput.value.trim() : stateSelect.value,
                city: estimateDialog.querySelector('[name="estimate_city"]').value.trim(),
                postcode: estimateDialog.querySelector('[name="estimate_postcode"]').value.trim()
            });
            refreshingAddress = true;
            dialogError(estimateDialog, '');
            updateSubmit();
            try {
                var current = await request('cart/update-customer', 'POST', estimated);
                estimateAddress = true;
                addressSignature = '';
                renderCart(current);
                estimateDialog.close();
                showStatus(hasSelectedRates(current) ? 'Shipping rates updated. Complete your address to place the order.' : 'No shipping method is available for this location.', !hasSelectedRates(current));
            } catch (error) {
                dialogError(estimateDialog, error.message || 'Could not calculate shipping rates.');
            } finally {
                refreshingAddress = false;
                if (cart) renderShipping(cart);
                if (addressComplete()) scheduleAddress();
                updateSubmit();
                if (pendingSync) {
                    pendingSync = false;
                    syncCart();
                }
            }
        });
    }

    function updateEstimateCountry(dialog) {
        var country = dialog.querySelector('[name="estimate_country"]').value;
        var fields = countryFields[country] || {};
        var choices = Object.entries(fields.states || {});
        var select = dialog.querySelector('[name="estimate_state"]');
        var input = dialog.querySelector('[name="estimate_state_text"]');
        var postcode = dialog.querySelector('[name="estimate_postcode"]');
        select.replaceChildren();
        select.hidden = !choices.length;
        select.disabled = !choices.length;
        input.hidden = !country || choices.length > 0 || fields.state === null;
        input.disabled = input.hidden;
        select.required = Boolean(choices.length && fields.state);
        input.required = Boolean(!input.hidden && fields.state);
        postcode.hidden = fields.postcode === null;
        postcode.required = Boolean(fields.postcode);
        if (choices.length) {
            var placeholder = element('option', '', 'Select a state / region…');
            placeholder.value = '';
            select.appendChild(placeholder);
            choices.forEach(function (choice) {
                var option = element('option', '', choice[1]);
                option.value = choice[0];
                select.appendChild(option);
            });
        }
    }

    function prepareEstimate(dialog) {
        var address = addressData().shipping_address;
        dialog.querySelector('[name="estimate_country"]').value = address.country;
        updateEstimateCountry(dialog);
        var select = dialog.querySelector('[name="estimate_state"]');
        dialog.querySelector('[name="estimate_state_text"]').value = address.state;
        if (!select.hidden) select.value = address.state;
        dialog.querySelector('[name="estimate_city"]').value = address.city;
        dialog.querySelector('[name="estimate_postcode"]').value = address.postcode;
    }

    function onAddressChange(event) {
        if (event.target.name && event.target.name.indexOf('shipping_package_') === 0) return;
        if (event.target === countryField || event.target === shippingCountry || event.target === differentField) return;
        scheduleAddress();
    }

    function onCountryChange(prefix) {
        updateCountryFields(prefix);
        scheduleAddress();
    }

    form.addEventListener('input', onAddressChange);
    form.addEventListener('change', onAddressChange);
    countryField.addEventListener('change', function () { onCountryChange('billing'); });
    if (shippingCountry) shippingCountry.addEventListener('change', function () { onCountryChange('shipping'); });
    if (differentField) differentField.addEventListener('change', updateDifferentAddress);
    updateCountryFields('billing');
    if (shippingCountry) updateCountryFields('shipping');
    setupDialogs();
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
        data.customer_note = customerNote;
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
            renderConfirmation(cart);
            var confirmation = root.querySelector('[data-checkout-confirmation]');
            confirmation.hidden = false;
            if (typeof confirmation.scrollIntoView === 'function') {
                confirmation.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
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
