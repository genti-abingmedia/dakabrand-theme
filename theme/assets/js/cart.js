(function () {
    'use strict';

    var config = typeof window === 'undefined' ? {} : (window.StaticBridgeConfig || {});
    var storageKey = config.cartStorageKey || 'staticbridge_cart_v1';

    function number(value) {
        var parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function lineKey(item) {
        var attributes = item.attributes || {};
        var selected = Object.keys(attributes).sort().map(function (key) {
            return [key, String(attributes[key])];
        });
        return JSON.stringify([Number(item.productId), Number(item.variationId) || 0, selected]);
    }

    function normalizeCart(value) {
        var source = Array.isArray(value) ? value : (value && Array.isArray(value.items) ? value.items : []);
        return source.filter(function (item) {
            return item && Number.isInteger(Number(item.productId)) && Number(item.productId) > 0 &&
                Number.isInteger(Number(item.quantity)) && Number(item.quantity) > 0;
        }).map(function (item) {
            return {
                productId: Number(item.productId),
                variationId: Number(item.variationId) || null,
                attributes: item.attributes && typeof item.attributes === 'object' && !Array.isArray(item.attributes) ? item.attributes : {},
                labels: item.labels && typeof item.labels === 'object' && !Array.isArray(item.labels) ? item.labels : {},
                name: String(item.name || 'Product'),
                permalink: String(item.permalink || ''),
                image: String(item.image || ''),
                unitPrice: Math.max(0, number(item.unitPrice)),
                currency: /^[A-Z]{3}$/.test(String(item.currency || '')) ? item.currency : 'GBP',
                stockStatus: String(item.stockStatus || ''),
                purchasable: item.purchasable === true,
                quantity: Number(item.quantity)
            };
        });
    }

    function cartCount(items) {
        return items.reduce(function (total, item) { return total + item.quantity; }, 0);
    }

    function cartTotal(items) {
        return items.reduce(function (total, item) { return total + item.unitPrice * item.quantity; }, 0);
    }

    function findVariation(product, selected) {
        var options = Array.isArray(product.options) ? product.options : [];
        if (!options.length || options.some(function (option) {
            return !selected[option.key] || !(option.choices || []).some(function (choice) {
                return String(choice.value) === String(selected[option.key]);
            });
        })) return null;

        return (product.variations || []).find(function (variation) {
            return options.every(function (option) {
                var required = String((variation.attributes || {})[option.key] || '');
                return !required || required === String(selected[option.key]);
            });
        }) || null;
    }

    // Replace this one boundary with a live stock request when the API is available.
    // The caller passes the resulting total quantity, not just the increment.
    async function validateStock(item, requestedQuantity) {
        if (!Number.isInteger(requestedQuantity) || requestedQuantity < 1 || !item.purchasable ||
            (item.stockStatus !== 'instock' && item.stockStatus !== 'onbackorder')) {
            return { ok: false, message: 'This item is currently unavailable.' };
        }
        return { ok: true };
    }

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = { lineKey: lineKey, normalizeCart: normalizeCart, cartCount: cartCount, cartTotal: cartTotal,
            findVariation: findVariation, validateStock: validateStock };
        return;
    }

    var drawer = document.querySelector('[data-cart-drawer]');
    if (!drawer) return;
    var itemsNode = drawer.querySelector('[data-cart-items]');
    var footerNode = drawer.querySelector('[data-cart-footer]');
    var subtotalNode = drawer.querySelector('[data-cart-subtotal]');
    var countNode = drawer.querySelector('[data-cart-drawer-count]');
    var statusNode = drawer.querySelector('[data-cart-status]');
    var busy = false;

    function readCart() {
        try {
            return normalizeCart(JSON.parse(localStorage.getItem(storageKey) || '[]'));
        } catch (error) {
            throw new Error('Your browser cart is unavailable. Please check browser storage settings.');
        }
    }

    function writeCart(items) {
        try {
            localStorage.setItem(storageKey, JSON.stringify(items));
        } catch (error) {
            throw new Error('Could not save your cart in this browser. Please check storage settings.');
        }
        renderCart(items);
        document.dispatchEvent(new CustomEvent('staticbridge:cart-updated'));
    }

    function element(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined) node.textContent = String(text);
        return node;
    }

    function safeUrl(value) {
        try {
            var url = new URL(value, window.location.href);
            return url.protocol === 'https:' || url.protocol === 'http:' ? url.href : '';
        } catch (error) {
            return '';
        }
    }

    function formatPrice(price, currency) {
        try {
            return new Intl.NumberFormat('en-GB', { style: 'currency', currency: currency }).format(price);
        } catch (error) {
            return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'GBP' }).format(price);
        }
    }

    function setStatus(message, isError) {
        statusNode.hidden = !message;
        statusNode.textContent = message || '';
        statusNode.classList.toggle('is-error', Boolean(isError));
    }

    function renderCart(items) {
        var count = cartCount(items);
        var currencies = new Set(items.map(function (item) { return item.currency; }));
        itemsNode.replaceChildren();
        countNode.textContent = '(' + count + ')';
        footerNode.hidden = !items.length;

        if (!items.length) {
            var empty = element('div', 'cart-drawer__empty');
            empty.appendChild(element('p', '', 'Your cart is empty.'));
            var shop = element('a', '', 'Explore the collection');
            shop.href = safeUrl(config.shopUrl || '/shop/');
            empty.appendChild(shop);
            itemsNode.appendChild(empty);
            return;
        }

        items.forEach(function (item) {
            var row = element('article', 'cart-item');
            var href = safeUrl(item.permalink);
            var imageUrl = safeUrl(item.image);
            var details = element('div', 'cart-item__details');
            var title = href ? element('a', 'cart-item__title', item.name) : element('span', 'cart-item__title', item.name);
            if (href) title.href = href;
            if (imageUrl) {
                var image = element('img', 'cart-item__image');
                image.src = imageUrl;
                image.alt = '';
                image.width = 88;
                image.height = 105;
                row.appendChild(image);
            }
            details.appendChild(title);
            Object.keys(item.labels).forEach(function (key) {
                details.appendChild(element('span', 'cart-item__option', key + ': ' + item.labels[key]));
            });
            details.appendChild(element('strong', 'cart-item__price', formatPrice(item.unitPrice * item.quantity, item.currency)));

            var controls = element('div', 'cart-item__controls');
            var minus = element('button', '', '−');
            minus.type = 'button';
            minus.dataset.cartAction = 'decrease';
            minus.dataset.lineKey = lineKey(item);
            minus.setAttribute('aria-label', 'Decrease quantity of ' + item.name);
            minus.disabled = item.quantity <= 1;
            var quantity = element('span', '', item.quantity);
            quantity.setAttribute('aria-label', 'Quantity: ' + item.quantity);
            var plus = element('button', '', '+');
            plus.type = 'button';
            plus.dataset.cartAction = 'increase';
            plus.dataset.lineKey = lineKey(item);
            plus.setAttribute('aria-label', 'Increase quantity of ' + item.name);
            controls.append(minus, quantity, plus);
            var remove = element('button', 'cart-item__remove', 'Remove');
            remove.type = 'button';
            remove.dataset.cartAction = 'remove';
            remove.dataset.lineKey = lineKey(item);
            remove.setAttribute('aria-label', 'Remove ' + item.name + ' from cart');
            details.append(controls, remove);
            row.appendChild(details);
            itemsNode.appendChild(row);
        });

        subtotalNode.textContent = currencies.size === 1
            ? formatPrice(cartTotal(items), items[0].currency)
            : 'Multiple currencies';
    }

    function showDrawer(opener) {
        try { renderCart(readCart()); } catch (error) { setStatus(error.message, true); }
        window.bootstrap.Offcanvas.getOrCreateInstance(drawer).show(opener);
    }

    function productData(container) {
        var script = container && container.querySelector('[data-staticbridge-product]');
        if (!script) return null;
        try { return JSON.parse(script.textContent); } catch (error) { return null; }
    }

    function renderProductOptions() {
        document.querySelectorAll('.product-detail').forEach(function (detail) {
            var product = productData(detail);
            var target = detail.querySelector('[data-product-options]');
            if (!product || product.type !== 'variable' || !target) return;
            (product.options || []).forEach(function (option) {
                var label = element('label', 'product-option');
                var title = element('span', '', option.label);
                var select = element('select');
                select.dataset.optionKey = option.key;
                select.required = true;
                var prompt = element('option', '', 'Choose ' + option.label);
                prompt.value = '';
                select.appendChild(prompt);
                (option.choices || []).forEach(function (choice) {
                    var item = element('option', '', choice.label);
                    item.value = choice.value;
                    select.appendChild(item);
                });
                label.append(title, select);
                target.appendChild(label);
            });
        });
    }

    function productMessage(button, message, isError) {
        var output = button.parentElement.querySelector('[data-cart-add-status]');
        if (!output) {
            output = element('p', 'cart-add-status');
            output.dataset.cartAddStatus = '';
            output.setAttribute('role', 'status');
            output.setAttribute('aria-live', 'polite');
            button.insertAdjacentElement('afterend', output);
        }
        output.textContent = message;
        output.classList.toggle('is-error', Boolean(isError));
    }

    async function addProduct(button) {
        var container = button.closest('.product-detail, .product-card');
        var product = productData(container);
        var selected = {};
        var labels = {};
        var variation = null;
        var stockSource;
        var item;
        var items;
        var previous;
        var check;

        if (!product) {
            productMessage(button, 'Product details are unavailable. Please reload the page.', true);
            return;
        }
        if (product.type !== 'simple' && product.type !== 'variable') {
            productMessage(button, 'This product cannot be added here.', true);
            return;
        }
        if (product.type === 'variable') {
            (product.options || []).forEach(function (option) {
                var select = Array.from(container.querySelectorAll('[data-option-key]')).find(function (field) {
                    return field.dataset.optionKey === option.key;
                });
                var choice = (option.choices || []).find(function (entry) { return select && String(entry.value) === select.value; });
                if (choice) {
                    selected[option.key] = choice.value;
                    labels[option.label] = choice.label;
                }
            });
            if (Object.keys(selected).length !== (product.options || []).length) {
                productMessage(button, 'Choose all product options first.', true);
                return;
            }
            variation = findVariation(product, selected);
            if (!variation) {
                productMessage(button, 'This option combination is unavailable.', true);
                return;
            }
        }

        stockSource = variation || product;
        item = {
            productId: Number(product.product_id),
            variationId: variation ? Number(variation.variation_id) : null,
            attributes: selected,
            labels: labels,
            name: String(product.name || 'Product'),
            permalink: String(product.permalink || ''),
            image: String(product.image || ''),
            unitPrice: number(stockSource.price),
            currency: String(product.currency || 'GBP'),
            stockStatus: String(stockSource.stock_status || ''),
            purchasable: stockSource.purchasable === true,
            quantity: 1
        };

        try {
            items = readCart();
            previous = items.find(function (entry) { return lineKey(entry) === lineKey(item); });
            check = await validateStock(item, previous ? previous.quantity + 1 : 1);
            if (!check.ok) throw new Error(check.message);
            if (previous) {
                previous.quantity += 1;
                previous.unitPrice = item.unitPrice;
                previous.stockStatus = item.stockStatus;
                previous.purchasable = item.purchasable;
            } else items.push(item);
            writeCart(items);
            productMessage(button, 'Added to cart.', false);
            setStatus('Added to cart. Prices and availability are provisional.', false);
            showDrawer(button);
        } catch (error) {
            productMessage(button, error.message || 'Could not add this item.', true);
        }
    }

    async function changeLine(button) {
        var items = readCart();
        var key = button.dataset.lineKey;
        var index = items.findIndex(function (item) { return lineKey(item) === key; });
        var action = button.dataset.cartAction;
        var check;
        if (index < 0) return;
        if (action === 'remove') items.splice(index, 1);
        else if (action === 'decrease') items[index].quantity = Math.max(1, items[index].quantity - 1);
        else if (action === 'increase') {
            check = await validateStock(items[index], items[index].quantity + 1);
            if (!check.ok) throw new Error(check.message);
            items[index].quantity += 1;
        }
        writeCart(items);
        setStatus('', false);
        var next = Array.from(itemsNode.querySelectorAll('[data-cart-action]')).find(function (control) {
            return control.dataset.cartAction === action && control.dataset.lineKey === key;
        });
        if (next && !next.disabled) next.focus();
        else drawer.querySelector('.btn-close').focus();
    }

    document.addEventListener('click', function (event) {
        var add = event.target.closest('[data-add-to-cart]');
        var open = event.target.closest('[data-cart-open], [data-cart-link], a[href]');
        if (add && !add.disabled) {
            if (busy) return;
            busy = true;
            add.disabled = true;
            addProduct(add).finally(function () { add.disabled = false; busy = false; });
            return;
        }
        if (!open || open.closest('[data-cart-drawer]')) return;
        var href = open.tagName === 'A' ? open.href : '';
        var cartUrl = new URL(config.cartUrl || '/cart/', window.location.href);
        if (open.matches('[data-cart-open], [data-cart-link]') || (href && new URL(href).origin === cartUrl.origin &&
            new URL(href).pathname.replace(/\/+$/, '') === cartUrl.pathname.replace(/\/+$/, ''))) {
            event.preventDefault();
            setStatus('', false);
            showDrawer(open);
        }
    });

    itemsNode.addEventListener('click', function (event) {
        var button = event.target.closest('[data-cart-action]');
        if (!button || busy) return;
        busy = true;
        changeLine(button).catch(function (error) {
            setStatus(error.message || 'Could not update your cart.', true);
        }).finally(function () { busy = false; });
    });

    window.addEventListener('storage', function (event) {
        if (event.key !== storageKey) return;
        try {
            renderCart(readCart());
            setStatus('', false);
        } catch (error) {
            setStatus(error.message, true);
        }
    });

    renderProductOptions();
    try { renderCart(readCart()); } catch (error) { renderCart([]); setStatus(error.message, true); }
}());
