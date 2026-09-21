(function () {
    'use strict';

    var root = document.querySelector('[data-catalog]');
    var rails = document.querySelectorAll('[data-product-grid][data-catalog-source]');
    if (!root && !rails.length) return;

    var API = 'https://filter.gliterin.net/public/filter';
    var STOREFRONT = (window.StaticBridgeConfig || {}).catalogSourceOrigin || 'https://dakabrand.uk/';
    var VIEWS = {
        large: { pageSize: 15 },
        small: { pageSize: 20 },
        dense: { pageSize: 25 },
        list: { pageSize: 10 }
    };
    var MAX_PAGE = 20;
    var CATALOG_BATCH_SIZE = 20;
    var cache = new Map();
    var controller = null;
    var requestNumber = 0;
    var currentData = null;
    var openFacets = new Set(['size', 'categories', 'brands', 'price']);
    var drawerOpen = false;
    var drawerOpener = null;
    var nodes = root ? {
        heading: root.querySelector('[data-catalog-heading]'),
        toolbar: root.querySelector('[data-catalog-toolbar]'),
        count: root.querySelector('[data-catalog-count]'),
        sort: root.querySelector('[data-catalog-sort]'),
        sortTrigger: root.querySelector('[data-catalog-sort-trigger]'),
        sortValue: root.querySelector('[data-catalog-sort-value]'),
        sortMenu: root.querySelector('[data-catalog-sort-menu]'),
        sortOptions: root.querySelectorAll('[data-catalog-sort-option]'),
        active: root.querySelector('[data-catalog-active]'),
        facets: root.querySelector('[data-catalog-facets]'),
        filters: root.querySelector('[data-catalog-filters]'),
        backdrop: root.querySelector('[data-catalog-backdrop]'),
        openFilters: root.querySelector('[data-catalog-open-filters]'),
        toggleFilters: root.querySelector('[data-catalog-toggle-filters]'),
        toggleFiltersLabel: root.querySelector('[data-catalog-toggle-filters-label]'),
        viewButtons: root.querySelectorAll('[data-catalog-view]'),
        status: root.querySelector('[data-catalog-status]'),
        grid: root.querySelector('[data-catalog-grid]'),
        pagination: root.querySelector('[data-catalog-pagination]')
    } : null;

    function element(tag, className, value) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (value !== undefined) node.textContent = String(value);
        return node;
    }

    function pageNumber(params) {
        var parsed = Number(params.get('page') || 1);
        return Number.isInteger(parsed) ? Math.min(MAX_PAGE, Math.max(1, parsed)) : 1;
    }

    function catalogView(params) {
        var view = params.get('catalog_view');
        return Object.prototype.hasOwnProperty.call(VIEWS, view) ? view : 'small';
    }

    function filtersAreHidden(params) {
        return params.get('catalog_filters') === 'hidden';
    }

    function browserUrl(params) {
        var url = new URL(window.location.href);
        url.search = params.toString();
        return url;
    }

    function sourceUrl(page, limit) {
        var params = new URLSearchParams(window.location.search);
        var url = new URL(window.location.pathname, STOREFRONT);
        params.delete('limit');
        params.delete('catalog_view');
        params.delete('catalog_filters');
        params.delete('price'); // The catalog applies price ranges after loading the products.
        if (!['price-asc', 'price-desc'].includes(params.get('orderby'))) params.delete('orderby');
        params.set('page', String(page));
        params.set('limit', String(limit));
        url.search = params.toString();
        return url.href;
    }

    function navigate(params, replace) {
        var target = browserUrl(params);
        if (target.href === window.location.href) return;
        window.history[replace ? 'replaceState' : 'pushState']({}, '', target.href);
        // Stock-mode state is derived from the URL. Refresh it immediately after
        // client-side catalog navigation so it cannot display the prior filter.
        if (window.StaticBridgeStockMode) window.StaticBridgeStockMode.refreshBadge();
        load();
    }

    function updateParam(name, value, resetPage) {
        var params = new URLSearchParams(window.location.search);
        if (value) params.set(name, value);
        else params.delete(name);
        if (resetPage) params.delete('page');
        navigate(params);
    }

    function updateDisplayControls() {
        var params = new URLSearchParams(window.location.search);
        var view = catalogView(params);
        var hidden = filtersAreHidden(params);
        root.dataset.catalogView = view;
        root.classList.toggle('catalog--filters-hidden', hidden);
        nodes.viewButtons.forEach(function (button) {
            button.setAttribute('aria-pressed', String(button.dataset.catalogView === view));
        });
        nodes.toggleFilters.setAttribute('aria-pressed', String(!hidden));
        nodes.toggleFilters.title = hidden ? 'Show filters' : 'Hide filters';
        nodes.toggleFiltersLabel.textContent = nodes.toggleFilters.title;
    }

    function setSortMenu(open) {
        nodes.sortMenu.hidden = !open;
        nodes.sortTrigger.setAttribute('aria-expanded', String(open));
        nodes.sort.classList.toggle('is-open', open);
    }

    function updateSortControl(value) {
        var selected = value === 'price-asc' || value === 'price-desc' ? value : '';
        nodes.sortOptions.forEach(function (option) {
            var isSelected = option.dataset.value === selected;
            option.setAttribute('aria-selected', String(isSelected));
            if (isSelected) nodes.sortValue.textContent = option.textContent;
        });
    }

    function showStatus(statusMessage, loading, retry) {
        nodes.status.replaceChildren();
        nodes.status.hidden = false;
        nodes.status.classList.toggle('catalog-status--inline', nodes.grid.childElementCount > 0);
        if (loading) nodes.status.appendChild(element('span', 'catalog-status__loader'));
        nodes.status.appendChild(element('span', '', statusMessage));
        if (retry) {
            var button = element('button', '', 'Try again');
            button.type = 'button';
            button.addEventListener('click', function () { load(true); });
            nodes.status.appendChild(button);
        }
    }

    function hideStatus() {
        nodes.status.hidden = true;
        nodes.status.replaceChildren();
    }

    function validUrl(value, productLink) {
        try {
            var url = new URL(value);
            if (url.protocol !== 'https:' && url.protocol !== 'http:') return '';
            if (productLink) {
                var source = new URL(STOREFRONT);
                if (![window.location.hostname, source.hostname, 'dakabrand.uk', 'www.dakabrand.uk'].includes(url.hostname)) return '';
                if (url.hostname === source.hostname && !['localhost', '127.0.0.1'].includes(window.location.hostname)) {
                    url = new URL(url.pathname + url.search + url.hash, window.location.origin);
                }
            }
            return url.href;
        } catch (error) {
            return '';
        }
    }

    function number(value) {
        var parsed = Number(value);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function hasNativeSale(item) {
        return number(item.regular_price) > 0 && number(item.sale_price) > 0 && number(item.sale_price) < number(item.regular_price);
    }

    function matchesDiscountFilter(product, filter) {
        if (!filter) return false;
        if (filter.type === 'all_products') return true;
        if (filter.type === 'product_on_sale') {
            var onSale = number(product.sale_price) > 0 && number(product.sale_price) < number(product.price);
            return filter.method === 'not_in_list' ? !onSale : onSale;
        }

        var values = [];
        var taxonomies = product.taxonomies || {};
        var list;
        if (filter.type === 'product_tags') list = taxonomies.tags || [];
        else if (filter.type === 'product_brand') list = taxonomies.brands || [];
        else if (filter.type === 'product_cat' || filter.type === 'category') list = taxonomies.categories || [];
        if (list) values = list.map(function (item) { return String(item.id); });
        else if (filter.type === 'products') values = [String(product.id)];
        else if (filter.type === 'product_sku') values = product.sku ? [String(product.sku)] : [];
        else if (filter.type === 'product_attributes') {
            Object.values(product.attributes || {}).forEach(function (attribute) {
                (attribute.values || []).forEach(function (value) { values.push(String(value)); });
            });
        } else return false;

        var selected = (Array.isArray(filter.value) ? filter.value : []).map(String);
        var intersects = values.some(function (value) { return selected.includes(value); });
        if (filter.method === 'in_list') return intersects;
        if (filter.method === 'not_in_list') return !intersects;
        return false;
    }

    function discountedPrice(price, product, rules, includeOutOfStock) {
        if (!product.is_in_stock && !includeOutOfStock) return price;
        for (var i = 0; i < rules.length; i += 1) {
            var rule = rules[i];
            if (!rule.filters || !Object.values(rule.filters).every(function (filter) { return matchesDiscountFilter(product, filter); })) continue;
            var adjustment = rule.product_adjustments || {};
            var value = number(adjustment.value);
            if (adjustment.type === 'percentage') return price - price * value / 100;
            if (adjustment.type === 'fixed_amount') return Math.max(0, price - value);
            if (adjustment.type === 'fixed_price') return value;
            return price;
        }
        return price;
    }

    function displayPricing(product, rules, includeOutOfStock) {
        var firstVariation = (product.variations || [])[0];
        if (firstVariation && number(firstVariation.price) <= 0) firstVariation = null;
        var item = firstVariation || product;
        var nativeSale = hasNativeSale(item);
        var price = nativeSale ? number(item.regular_price) : number(item.price);
        var sale = nativeSale ? number(item.sale_price) : discountedPrice(number(item.price), product, rules, includeOutOfStock);
        return { price: price, sale: sale > 0 && sale < price ? sale : 0 };
    }

    function formatPrice(value, currency) {
        try {
            return new Intl.NumberFormat('en-GB', {
                style: 'currency', currency: currency || 'EUR', minimumFractionDigits: 2, maximumFractionDigits: 2
            }).format(value);
        } catch (error) {
            return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'EUR' }).format(value);
        }
    }

    function selectedPriceRange(params) {
        var value = params.get('price');
        if (!value) return null;
        var parts = value.split(',');
        if (parts.length !== 2 || parts.some(function (part) { return !part.trim(); })) return null;
        var low = Number(parts[0]);
        var high = Number(parts[1]);
        return Number.isFinite(low) && Number.isFinite(high) && low <= high ? [low, high] : null;
    }

    function productPrice(product, rules, includeOutOfStock) {
        var pricing = displayPricing(product, rules, includeOutOfStock);
        return pricing.sale || pricing.price;
    }

    function formatRangePrice(value, currency) {
        try {
            return new Intl.NumberFormat('en-GB', {
                style: 'currency', currency: currency || 'EUR', minimumFractionDigits: 0, maximumFractionDigits: 2
            }).format(value);
        } catch (error) {
            return new Intl.NumberFormat('en-GB', { style: 'currency', currency: 'EUR', maximumFractionDigits: 2 }).format(value);
        }
    }

    function makeCard(product, rules, includeOutOfStock, index, whatsappNumber) {
        var article = element('article', 'catalog-card');
        var href = validUrl(product.permalink, true);
        if (!href) return null;
        var media = element('div', 'catalog-card__media');
        var imageLink = element('a', 'catalog-card__image-link');
        imageLink.href = href;
        imageLink.setAttribute('aria-label', String(product.name || 'View product'));
        var imageUrl = validUrl(product.product_image, false);
        if (imageUrl) {
            var image = element('img');
            var resizeBase = 'https://img.gliterin.net/resize.php?image=' + encodeURIComponent(imageUrl);
            image.src = resizeBase + '&width=480&height=480';
            image.srcset = resizeBase + '&width=300&height=300 300w, ' + resizeBase + '&width=480&height=480 480w';
            image.sizes = '(max-width: 700px) 48vw, (max-width: 1200px) 30vw, 23vw';
            image.addEventListener('error', function () {
                if (image.src === imageUrl) return;
                image.removeAttribute('srcset');
                image.src = imageUrl;
            });
            image.alt = String(product.name || '');
            image.width = 480;
            image.height = 480;
            image.loading = index < 2 ? 'eager' : 'lazy';
            image.decoding = 'async';
            imageLink.appendChild(image);
        }
        media.appendChild(imageLink);
        var pricing = displayPricing(product, rules, includeOutOfStock);
        if (pricing.sale) {
            var percentage = Math.round((pricing.price - pricing.sale) / pricing.price * 100);
            media.appendChild(element('span', 'catalog-card__badge', percentage + '%'));
        }
        var stock = (product.stock_status && product.stock_status.values) || [];
        if (stock.includes('instock')) media.appendChild(element('span', 'catalog-card__stock', 'In stock'));
        else if (stock.includes('outofstock')) media.appendChild(element('span', 'catalog-card__stock', 'Out of stock'));
        else if (stock.includes('onbackorder')) media.appendChild(element('span', 'catalog-card__stock', '15 days preorder'));

        var phone = String(whatsappNumber || '').replace(/\D/g, '');
        if (phone) {
            var whatsappMessage = 'I AM INTERESTED IN THE PRODUCT: ' + String(product.name || '') +
                ' with SKU: ' + String(product.sku || '') + ' Link: ' + href;
            var whatsapp = element('a', 'catalog-card__whatsapp');
            whatsapp.href = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(whatsappMessage);
            whatsapp.target = '_blank';
            whatsapp.rel = 'noopener noreferrer';
            whatsapp.title = 'Contact on WhatsApp';
            whatsapp.setAttribute('aria-label', 'Contact on WhatsApp about ' + String(product.name || 'this product'));
            var whatsappIcon = element('img');
            whatsappIcon.src = 'https://static.gliterin.net/filter/whatsapp-logo.png';
            whatsappIcon.alt = '';
            whatsappIcon.width = 32;
            whatsappIcon.height = 32;
            whatsappIcon.loading = 'lazy';
            whatsapp.appendChild(whatsappIcon);
            media.appendChild(whatsapp);
        }
        article.appendChild(media);

        var details = element('a', 'catalog-card__details');
        details.href = href;
        details.appendChild(element('h2', 'catalog-card__name', product.name || 'Product'));
        if (pricing.price > 0) {
            var price = element('span', 'catalog-card__price');
            if (pricing.sale) {
                price.appendChild(element('s', '', formatPrice(pricing.price, product.currency)));
                price.appendChild(element('strong', '', formatPrice(pricing.sale, product.currency)));
            } else price.appendChild(element('span', '', formatPrice(pricing.price, product.currency)));
            details.appendChild(price);
        }
        article.appendChild(details);
        return article;
    }

    function makeRailCard(product, rules, includeOutOfStock) {
        var href = validUrl(product.permalink, true);
        if (!href) return null;
        var card = element('article', 'product-card');
        var link = element('a', 'product-card__link');
        var imageUrl = validUrl(product.product_image, false);
        var price = displayPricing(product, rules, includeOutOfStock);
        var stock = (product.stock_status && product.stock_status.values) || [];
        var purchasable = product.type === 'simple' && (product.is_in_stock === true || stock.includes('onbackorder')) &&
            (stock.includes('instock') || stock.includes('onbackorder'));
        var category = ((product.taxonomies || {}).categories || [])[0];
        card.dataset.productCard = '';
        card.dataset.productId = String(product.id);
        card.dataset.productType = String(product.type || '');
        link.href = href;
        if (imageUrl) {
            var image = element('img');
            image.src = imageUrl;
            image.alt = String(product.name || '');
            image.width = 300;
            image.height = 300;
            image.loading = 'lazy';
            link.appendChild(image);
        }
        if (category) link.appendChild(element('p', 'product-card__category', category.name || category.label || ''));
        link.appendChild(element('h2', '', product.name || 'Product'));
        card.appendChild(link);
        if (price.price > 0) {
            var priceNode = element('div', 'product-price');
            if (price.sale) {
                priceNode.appendChild(element('s', '', formatPrice(price.price, product.currency)));
                priceNode.appendChild(element('strong', '', formatPrice(price.sale, product.currency)));
            } else priceNode.textContent = formatPrice(price.price, product.currency);
            card.appendChild(priceNode);
        }
        if (purchasable) {
            var button = element('button', '', 'Add to cart');
            button.type = 'button';
            button.dataset.addToCart = '';
            button.dataset.productId = String(product.id);
            card.appendChild(button);
            var payload = {
                product_id: Number(product.id), name: String(product.name || 'Product'), permalink: href,
                image: imageUrl, currency: String(product.currency || 'GBP'), type: 'simple',
                price: price.sale || price.price, stock_status: stock.includes('onbackorder') ? 'onbackorder' : 'instock',
                purchasable: true
            };
            var script = element('script');
            script.type = 'application/json';
            script.dataset.staticbridgeProduct = '';
            script.textContent = JSON.stringify(payload);
            card.appendChild(script);
        } else {
            var choose = element('a', '', product.type === 'variable' ? 'Choose options' : 'View product');
            choose.href = href;
            card.appendChild(choose);
        }
        return card;
    }

    function makeStorefrontCard(product, rules, includeOutOfStock, index, whatsappNumber) {
        var href = validUrl(product.permalink, true);
        if (!href) return null;

        var article = element('article', 'storefront-product-card');
        var media = element('div', 'storefront-product-card__media');
        var imageLink = element('a', 'storefront-product-card__image-link');
        var imageUrl = validUrl(product.product_image, false);
        var pricing = displayPricing(product, rules, includeOutOfStock);
        var stock = (product.stock_status && product.stock_status.values) || [];

        imageLink.href = href;
        imageLink.setAttribute('aria-label', String(product.name || 'View product'));
        if (imageUrl) {
            var image = element('img');
            image.src = imageUrl;
            image.alt = String(product.name || '');
            image.width = 300;
            image.height = 300;
            image.loading = index < 2 ? 'eager' : 'lazy';
            image.decoding = 'async';
            imageLink.appendChild(image);
        }
        media.appendChild(imageLink);

        if (pricing.sale) {
            media.appendChild(element('span', 'storefront-product-card__badge',
                Math.round((pricing.price - pricing.sale) / pricing.price * 100) + '%'));
        }
        if (stock.includes('onbackorder')) media.appendChild(element('span', 'storefront-product-card__stock', '15 days preorder'));
        else if (stock.includes('outofstock')) media.appendChild(element('span', 'storefront-product-card__stock', 'Out of stock'));

        var phone = String(whatsappNumber || '').replace(/\D/g, '');
        if (phone) {
            var message = 'I AM INTERESTED IN THE PRODUCT: ' + String(product.name || '') +
                ' with SKU: ' + String(product.sku || '') + ' Link: ' + href;
            var whatsapp = element('a', 'storefront-product-card__whatsapp');
            whatsapp.href = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(message);
            whatsapp.target = '_blank';
            whatsapp.rel = 'noopener noreferrer';
            whatsapp.title = 'Contact on WhatsApp';
            whatsapp.setAttribute('aria-label', 'Contact on WhatsApp about ' + String(product.name || 'this product'));
            var icon = element('img');
            icon.src = 'https://static.gliterin.net/filter/whatsapp-logo.png';
            icon.alt = '';
            icon.width = 32;
            icon.height = 32;
            icon.loading = 'lazy';
            whatsapp.appendChild(icon);
            media.appendChild(whatsapp);
        }
        article.appendChild(media);

        var details = element('a', 'storefront-product-card__details');
        details.href = href;
        details.appendChild(element('h3', 'storefront-product-card__name', product.name || 'Product'));
        if (pricing.price > 0) {
            var price = element('span', 'storefront-product-card__price');
            if (pricing.sale) {
                price.appendChild(element('s', '', formatPrice(pricing.price, product.currency)));
                price.appendChild(element('strong', '', formatPrice(pricing.sale, product.currency)));
            } else price.appendChild(element('span', '', formatPrice(pricing.price, product.currency)));
            details.appendChild(price);
        }
        article.appendChild(details);
        return article;
    }

    function loadRail(grid) {
        var status = grid.parentElement.querySelector('[data-catalog-rail-status]');
        var limit = Math.max(1, Math.min(20, Number(grid.dataset.catalogLimit) || 10));
        var excluded = Number(grid.dataset.catalogExclude) || 0;
        var requested = new URL(grid.dataset.catalogSource, window.location.origin);
        var source = new URL(requested.pathname + requested.search, STOREFRONT);
        source.searchParams.set('page', '1');
        source.searchParams.set('limit', String(limit + (excluded ? 1 : 0)));
        grid.setAttribute('aria-busy', 'true');
        fetch(API + '?url=' + encodeURIComponent(source.href))
            .then(function (response) { if (!response.ok) throw new Error('Catalog request failed'); return response.json(); })
            .then(function (payload) {
                var data = payload && payload.result;
                if (!data || !Array.isArray(data.products)) throw new Error('Invalid catalog response');
                var rules = Array.isArray(data.discount_rules) ? data.discount_rules : [];
                var cards = data.products.filter(function (product) { return Number(product.id) !== excluded; })
                    .slice(0, limit).map(function (product, index) {
                        return grid.dataset.storefrontProductCards !== undefined
                            ? makeStorefrontCard(product, rules, Boolean(data.include_out_of_stock), index, data.whatsapp_number)
                            : makeRailCard(product, rules, Boolean(data.include_out_of_stock));
                    }).filter(Boolean);
                grid.replaceChildren.apply(grid, cards);
                status.textContent = cards.length ? '' : 'No products found.';
                status.hidden = Boolean(cards.length);
            })
            .catch(function () {
                status.textContent = 'We could not load products right now.';
                status.hidden = false;
            })
            .finally(function () { grid.setAttribute('aria-busy', 'false'); });
    }

    function sortedOptions(facet) {
        var options = Array.isArray(facet.options) ? facet.options.slice() : [];
        if (facet.sort === 'alphabetical_asc') options.sort(function (a, b) { return String(a.label).localeCompare(String(b.label)); });
        if (facet.sort === 'alphabetical_desc') options.sort(function (a, b) { return String(b.label).localeCompare(String(a.label)); });
        if (facet.sort === 'count_asc') options.sort(function (a, b) { return number(a.count) - number(b.count); });
        if (facet.sort === 'count_desc') options.sort(function (a, b) { return number(b.count) - number(a.count); });
        return options;
    }

    function makePriceFacet(facet, container, data, rules) {
        var prices = (data.products || []).map(function (product) {
            return productPrice(product, rules, Boolean(data.include_out_of_stock));
        }).filter(Number.isFinite);
        if (!prices.length) return;

        var step = number((facet.options || {}).slider_step) || 1;
        var minimum = prices.reduce(function (lowest, price) { return Math.min(lowest, price); }, Infinity);
        var maximum = prices.reduce(function (highest, price) { return Math.max(highest, price); }, -Infinity);
        var start = Math.floor(minimum / step) * step;
        var width = Math.max(step, Math.ceil((maximum - start) / (3 * step)) * step);
        var currency = (data.products || []).find(function (product) { return product.currency; });
        currency = currency ? currency.currency : 'EUR';
        var selected = new URLSearchParams(window.location.search).get(facet.slug);
        var options = element('div', 'catalog-facet__options catalog-price');

        for (var index = 0; index < 3; index += 1) {
            var low = Number((start + index * width).toFixed(2));
            if (index && low >= maximum) break;
            var high = Number(Math.min(maximum, low + width).toFixed(2));
            if (high === low) high = Number((low + step).toFixed(2));
            var count = prices.filter(function (price) { return price >= low && price <= high; }).length;
            if (!count) continue;
            var value = low + ',' + high;
            var label = element('label', 'catalog-facet__option');
            var input = element('input');
            input.type = 'checkbox';
            input.name = facet.slug;
            input.value = value;
            input.checked = selected === value;
            label.append(input,
                element('span', '', formatRangePrice(low, currency) + ' – ' + formatRangePrice(high, currency)),
                element('span', 'catalog-facet__count', '(' + count.toLocaleString('en-GB') + ')'));
            input.addEventListener('change', function () {
                updateParam(facet.slug, this.checked ? this.value : '', true);
            });
            options.appendChild(label);
        }
        container.appendChild(options);
    }

    function makeOptionFacet(facet, container) {
        var options = element('div', 'catalog-facet__options');
        sortedOptions(facet).forEach(function (option, index) {
            var label = element('label', 'catalog-facet__option');
            var input = element('input');
            input.type = facet.select === 'single' ? 'radio' : 'checkbox';
            input.name = facet.slug;
            input.value = String(option.slug);
            input.checked = Boolean(option.is_selected);
            label.append(input, element('span', '', option.label));
            if (facet.show_item_count) label.appendChild(element('span', 'catalog-facet__count', number(option.count).toLocaleString('en-GB')));
            input.addEventListener('change', function () {
                var selected;
                if (facet.select === 'single') selected = [option];
                else {
                    selected = sortedOptions(facet).filter(function (item) {
                        return Array.from(options.querySelectorAll('input:checked')).some(function (checked) { return checked.value === String(item.slug); });
                    });
                }
                var value = selected.map(function (item) { return item.slug + ':' + item.label; }).join(',');
                if (facet.slug === 'stock_status' && window.StaticBridgeStockMode) {
                    window.StaticBridgeStockMode.saveStatus(value);
                }
                updateParam(facet.slug, value, true);
            });
            options.appendChild(label);
        });
        container.appendChild(options);
    }

    function renderFacets(data, rules) {
        nodes.facets.replaceChildren();
        (data.filter || []).filter(function (facet) { return facet.show_in_filter && facet.slug; }).forEach(function (facet) {
            var details = element('details', 'catalog-facet' + (facet.display === 'box' ? ' catalog-facet--box' : ''));
            details.dataset.facetSlug = facet.slug;
            details.open = openFacets.has(facet.slug);
            details.appendChild(element('summary', '', facet.label));
            details.addEventListener('toggle', function () {
                if (details.open) openFacets.add(facet.slug);
                else openFacets.delete(facet.slug);
            });
            if (facet.display === 'range') makePriceFacet(facet, details, data, rules);
            else makeOptionFacet(facet, details);
            nodes.facets.appendChild(details);
        });
    }

    function selectedTokens(value) {
        return value.split(',').reduce(function (parts, piece) {
            var token = piece.trim();
            if (token.includes(':') || parts.length === 0) parts.push(token);
            else parts[parts.length - 1] += ', ' + token;
            return parts;
        }, []).filter(Boolean);
    }

    function renderActive(data) {
        nodes.active.replaceChildren();
        var params = new URLSearchParams(window.location.search);
        var visible = (data.filter || []).filter(function (facet) { return facet.show_in_filter; });
        var activeCount = 0;
        if (params.get('keyword')) {
            var searchChip = element('button', '', 'Search: ' + params.get('keyword') + '  ×');
            searchChip.type = 'button';
            searchChip.addEventListener('click', function () { updateParam('keyword', '', true); });
            nodes.active.appendChild(searchChip);
            activeCount += 1;
        }
        visible.forEach(function (facet) {
            var value = params.get(facet.slug);
            if (!value) return;
            var tokens = facet.display === 'range' ? [value] : selectedTokens(value);
            tokens.forEach(function (token) {
                var text = facet.display === 'range' ? 'Price: ' + token.replace(',', '–') : facet.label + ': ' + (token.split(':').slice(1).join(':') || token);
                var button = element('button', '', text + '  ×');
                button.type = 'button';
                button.addEventListener('click', function () {
                    if (facet.slug === 'stock_status' && window.StaticBridgeStockMode) {
                        window.StaticBridgeStockMode.clear();
                    }
                    if (facet.display === 'range') return updateParam(facet.slug, '', true);
                    var remaining = selectedTokens(value).filter(function (part) { return part !== token; }).join(',');
                    updateParam(facet.slug, remaining, true);
                });
                nodes.active.appendChild(button);
                activeCount += 1;
            });
        });
        if (activeCount > 1) {
            var clear = element('button', 'catalog-active__clear', 'Clear filters');
            clear.type = 'button';
            clear.addEventListener('click', clearFacets);
            nodes.active.appendChild(clear);
        }
        nodes.active.hidden = activeCount === 0;
    }

    function clearFacets() {
        var params = new URLSearchParams(window.location.search);
        (currentData.filter || []).forEach(function (facet) { params.delete(facet.slug); });
        if (window.StaticBridgeStockMode) window.StaticBridgeStockMode.clear();
        params.delete('page');
        navigate(params);
    }

    function paginationLink(label, page, current) {
        if (current) {
            var span = element('span', '', label);
            span.setAttribute('aria-current', 'page');
            return span;
        }
        var params = new URLSearchParams(window.location.search);
        if (page === 1) params.delete('page');
        else params.set('page', String(page));
        var anchor = element('a', '', label);
        anchor.href = browserUrl(params).href;
        anchor.addEventListener('click', function (event) {
            if (event.button || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
            event.preventDefault();
            navigate(params);
            root.scrollIntoView({ block: 'start' });
        });
        return anchor;
    }

    function renderPagination(count) {
        var page = pageNumber(new URLSearchParams(window.location.search));
        var pages = Math.min(MAX_PAGE, Math.ceil(count / VIEWS[catalogView(new URLSearchParams(window.location.search))].pageSize));
        nodes.pagination.replaceChildren();
        nodes.pagination.hidden = pages <= 1;
        if (pages <= 1) return;
        if (page > 1) nodes.pagination.appendChild(paginationLink('Previous', page - 1, false));
        var shown = new Set([1, pages]);
        for (var i = Math.max(1, page - 2); i <= Math.min(pages, page + 2); i += 1) shown.add(i);
        var previous = 0;
        Array.from(shown).sort(function (a, b) { return a - b; }).forEach(function (value) {
            if (value - previous > 1) nodes.pagination.appendChild(element('span', '', '…'));
            nodes.pagination.appendChild(paginationLink(String(value), value, value === page));
            previous = value;
        });
        if (page < pages) nodes.pagination.appendChild(paginationLink('Next', page + 1, false));
    }

    function render(data) {
        currentData = data;

        var params = new URLSearchParams(window.location.search);
        var keyword = params.get('keyword');

        var defaultHeading =
            nodes.heading.dataset.defaultHeading || 'Shop';

        nodes.heading.textContent = keyword
            ? 'Results for “' + keyword + '”'
            : defaultHeading;

        nodes.toolbar.hidden = false;

        updateDisplayControls();
        updateSortControl(params.get('orderby'));

        var rules = Array.isArray(data.discount_rules)
            ? data.discount_rules.slice().sort(function (a, b) {
                return number(b.exclusive) - number(a.exclusive) ||
                    number(a.priority) - number(b.priority);
            })
            : [];

        var range = selectedPriceRange(params);

        var products = (data.products || []).filter(function (product) {
            if (!range) return true;

            var price = productPrice(
                product,
                rules,
                Boolean(data.include_out_of_stock)
            );

            return price >= range[0] && price <= range[1];
        });

        // Total products from API, not current page length.
        var count = Math.max(0, number(data.count));

        nodes.count.textContent =
            (count >= 10000
                ? '10,000+'
                : count.toLocaleString('en-GB')
            ) +
            (count === 1 ? ' product' : ' products');

        renderFacets(data, rules);
        renderActive(data);

        // Products are already paginated by the API.
        // Do not slice them again.
        var cards = products.map(function (product, index) {
            return makeCard(
                product,
                rules,
                Boolean(data.include_out_of_stock),
                index,
                data.whatsapp_number
            );
        }).filter(Boolean);

        nodes.grid.replaceChildren.apply(nodes.grid, cards);

        nodes.grid.setAttribute('aria-busy', 'false');

        // Pagination uses total product count.
        renderPagination(count);

        if (cards.length) {
            hideStatus();
        } else {
            showStatus(
                'No products found. Try removing a filter.',
                false,
                false
            );
        }
    }

    function fetchCatalogPage(page, limit, signal) {
        return fetch(API + '?url=' + encodeURIComponent(sourceUrl(page, limit)), { signal: signal })
            .then(function (response) {
                if (!response.ok) throw new Error('Catalog request failed');
                return response.json();
            })
            .then(function (payload) {
                var data = payload && payload.result;
                if (!data || !Array.isArray(data.products) || !Array.isArray(data.filter) || !Number.isFinite(Number(data.count))) {
                    throw new Error('Invalid catalog response');
                }
                return data;
            });
    }

    function catalogPageSize(params) {
        var requested = Number(params.get('limit'));

        if (
            params.has('limit') &&
            Number.isInteger(requested) &&
            requested > 0
        ) {
            return Math.min(100, requested);
        }

        return VIEWS[catalogView(params)].pageSize;
    }

    function load(force) {
        var params = new URLSearchParams(window.location.search);

        // A saved homepage/catalog choice becomes part of the shareable catalog
        // URL only when this request did not already specify a stock filter.
        if (!params.has('stock_status') && window.StaticBridgeStockMode) {
            var inheritedStockStatus = window.StaticBridgeStockMode.currentStatus();
            if (inheritedStockStatus) {
                params.set('stock_status', inheritedStockStatus);
                window.history.replaceState({}, '', browserUrl(params).href);
                window.StaticBridgeStockMode.refreshBadge();
            }
        }

        var page = pageNumber(params);
        var limit = catalogPageSize(params);

        // Normalize invalid page parameters.
        if (params.has('page') && String(page) !== params.get('page')) {
            params.set('page', String(page));
            window.history.replaceState({}, '', browserUrl(params).href);
        }

        // Cache is unique for every page and page size.
        var key = sourceUrl(page, limit);

        requestNumber += 1;
        var thisRequest = requestNumber;

        // Cancel previous requests.
        if (controller) controller.abort();
        controller = null;

        // Return cached page if available.
        if (!force && cache.has(key)) {
            render(cache.get(key));
            return;
        }

        nodes.grid.setAttribute('aria-busy', 'true');
        showStatus('Loading products…', true, false);

        controller = new AbortController();
        var signal = controller.signal;

        // Fetch ONLY the requested page.
        fetchCatalogPage(page, limit, signal)
            .then(function (data) {
                if (thisRequest !== requestNumber) return;

                cache.set(key, data);

                // Keep only the last 6 cached pages.
                if (cache.size > 6) {
                    cache.delete(cache.keys().next().value);
                }

                render(data);
            })
            .catch(function (error) {
                if (
                    error.name === 'AbortError' ||
                    thisRequest !== requestNumber
                ) {
                    return;
                }

                nodes.grid.replaceChildren();
                nodes.grid.setAttribute('aria-busy', 'false');

                nodes.toolbar.hidden = true;
                nodes.active.hidden = true;
                nodes.pagination.hidden = true;

                showStatus(
                    'We could not load products right now.',
                    false,
                    true
                );
            });
    }

    function setDrawer(open) {
        if (open === drawerOpen) return;
        drawerOpen = open;
        nodes.filters.classList.toggle('is-open', open);
        nodes.backdrop.hidden = !open;
        nodes.openFilters.setAttribute('aria-expanded', String(open));
        document.body.classList.toggle('catalog-drawer-open', open);
        if (open) {
            drawerOpener = document.activeElement;
            nodes.filters.setAttribute('role', 'dialog');
            nodes.filters.setAttribute('aria-modal', 'true');
            nodes.filters.focus();
        } else {
            nodes.filters.removeAttribute('role');
            nodes.filters.removeAttribute('aria-modal');
            if (drawerOpener && drawerOpener.focus) drawerOpener.focus();
        }
    }

    rails.forEach(loadRail);
    if (!root) return;

    nodes.sortTrigger.addEventListener('click', function () { setSortMenu(nodes.sortMenu.hidden); });
    nodes.sortOptions.forEach(function (option) {
        option.addEventListener('click', function () {
            setSortMenu(false);
            updateParam('orderby', option.dataset.value, true);
        });
    });
    document.addEventListener('click', function (event) {
        if (!nodes.sort.contains(event.target)) setSortMenu(false);
    });
    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape' || nodes.sortMenu.hidden) return;
        setSortMenu(false);
        nodes.sortTrigger.focus();
    });
    nodes.toggleFilters.addEventListener('click', function () {
        updateParam('catalog_filters', filtersAreHidden(new URLSearchParams(window.location.search)) ? '' : 'hidden', false);
    });
    nodes.viewButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            updateParam('catalog_view', button.dataset.catalogView === 'small' ? '' : button.dataset.catalogView, true);
        });
    });
    nodes.openFilters.addEventListener('click', function () { setDrawer(true); });
    root.querySelectorAll('[data-catalog-close-filters]').forEach(function (button) {
        button.addEventListener('click', function () { setDrawer(false); });
    });
    root.querySelector('[data-catalog-clear]').addEventListener('click', clearFacets);
    nodes.backdrop.addEventListener('click', function () { setDrawer(false); });
    nodes.filters.addEventListener('keydown', function (event) {
        if (!drawerOpen) return;
        if (event.key === 'Escape') { setDrawer(false); return; }
        if (event.key !== 'Tab') return;
        var focusable = Array.from(nodes.filters.querySelectorAll('button, input, select, summary, a[href]')).filter(function (node) { return node.offsetParent !== null; });
        if (!focusable.length) return;
        var first = focusable[0];
        var last = focusable[focusable.length - 1];
        if (event.shiftKey && (document.activeElement === first || document.activeElement === nodes.filters)) { event.preventDefault(); last.focus(); }
        else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
    });
    window.addEventListener('popstate', function () { load(); });
    window.addEventListener('resize', function () { if (window.innerWidth > 991 && drawerOpen) setDrawer(false); });
    load();
}());
