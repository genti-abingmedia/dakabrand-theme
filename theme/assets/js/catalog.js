(function () {
    'use strict';

    var root = document.querySelector('[data-catalog]');
    if (!root) return;

    var API = 'https://filter.gliterin.net/public/filter';
    var STOREFRONT = 'https://static-daka.gliterindemo.com';
    var VIEWS = {
        large: { pageSize: 20 },
        small: { pageSize: 25 },
        list: { pageSize: 10 }
    };
    var MAX_PAGE = 500;
    var cache = new Map();
    var controller = null;
    var requestNumber = 0;
    var currentData = null;
    var openFacets = new Set(['size', 'categories', 'brands']);
    var drawerOpen = false;
    var drawerOpener = null;
    var nodes = {
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
    };

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
        return Object.prototype.hasOwnProperty.call(VIEWS, view) ? view : 'large';
    }

    function filtersAreHidden(params) {
        return params.get('catalog_filters') === 'hidden';
    }

    function browserUrl(params) {
        var url = new URL(window.location.href);
        url.search = params.toString();
        return url;
    }

    function sourceUrl() {
        var params = new URLSearchParams(window.location.search);
        var url = new URL(window.location.pathname, STOREFRONT);
        params.delete('limit');
        params.delete('catalog_view');
        params.delete('catalog_filters');
        if (!['price-asc', 'price-desc'].includes(params.get('orderby'))) params.delete('orderby');
        params.set('page', String(pageNumber(params)));
        params.set('limit', String(VIEWS[catalogView(new URLSearchParams(window.location.search))].pageSize));
        url.search = params.toString();
        return url.href;
    }

    function navigate(params, replace) {
        var target = browserUrl(params);
        if (target.href === window.location.href) return;
        window.history[replace ? 'replaceState' : 'pushState']({}, '', target.href);
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
        nodes.toggleFilters.setAttribute('aria-pressed', String(hidden));
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

    function showStatus(message, loading, retry) {
        nodes.status.replaceChildren();
        nodes.status.hidden = false;
        nodes.status.classList.toggle('catalog-status--inline', nodes.grid.childElementCount > 0);
        if (loading) nodes.status.appendChild(element('span', 'catalog-status__loader'));
        nodes.status.appendChild(element('span', '', message));
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
            if (productLink && url.hostname !== 'dakabrand.uk' && url.hostname !== 'www.dakabrand.uk') return '';
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
            var message = 'I AM INTERESTED IN THE PRODUCT: ' + String(product.name || '') +
                ' with SKU: ' + String(product.sku || '') + ' Link: ' + href;
            var whatsapp = element('a', 'catalog-card__whatsapp');
            whatsapp.href = 'https://wa.me/' + phone + '?text=' + encodeURIComponent(message);
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

    function sortedOptions(facet) {
        var options = Array.isArray(facet.options) ? facet.options.slice() : [];
        if (facet.sort === 'alphabetical_asc') options.sort(function (a, b) { return String(a.label).localeCompare(String(b.label)); });
        if (facet.sort === 'alphabetical_desc') options.sort(function (a, b) { return String(b.label).localeCompare(String(a.label)); });
        if (facet.sort === 'count_asc') options.sort(function (a, b) { return number(a.count) - number(b.count); });
        if (facet.sort === 'count_desc') options.sort(function (a, b) { return number(b.count) - number(a.count); });
        return options;
    }

    function makePriceFacet(facet, container) {
        var options = facet.options || {};
        var minimum = number(options.min);
        var maximum = number(options.max);
        var form = element('form', 'catalog-price');
        var fields = element('div', 'catalog-price__inputs');
        var minInput = element('input');
        var maxInput = element('input');
        [minInput, maxInput].forEach(function (input) {
            input.type = 'number';
            input.min = String(minimum);
            input.max = String(maximum);
            input.step = 'any';
            input.required = true;
        });
        minInput.value = String(number(options.selected_min ?? minimum));
        maxInput.value = String(number(options.selected_max ?? maximum));
        minInput.setAttribute('aria-label', 'Minimum price');
        maxInput.setAttribute('aria-label', 'Maximum price');
        fields.append(minInput, element('span', '', '–'), maxInput);
        var apply = element('button', '', 'Apply');
        apply.type = 'submit';
        form.append(fields, apply);
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var low = Number(minInput.value);
            var high = Number(maxInput.value);
            if (!Number.isFinite(low) || !Number.isFinite(high) || low < minimum || high > maximum || low > high) {
                minInput.setCustomValidity('Choose a valid price range.');
                minInput.reportValidity();
                return;
            }
            minInput.setCustomValidity('');
            updateParam(facet.slug, low === minimum && high === maximum ? '' : low + ',' + high, true);
        });
        minInput.addEventListener('input', function () { minInput.setCustomValidity(''); });
        container.appendChild(form);
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
                updateParam(facet.slug, value, true);
            });
            options.appendChild(label);
        });
        container.appendChild(options);
    }

    function renderFacets(data) {
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
            if (facet.display === 'range') makePriceFacet(facet, details);
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
        var defaultHeading = nodes.heading.dataset.defaultHeading || 'Shop';
        nodes.heading.textContent = keyword ? 'Results for “' + keyword + '”' : defaultHeading;
        nodes.toolbar.hidden = false;
        updateDisplayControls();
        updateSortControl(params.get('orderby'));
        var count = Math.max(0, number(data.count));
        nodes.count.textContent = (count >= 10000 ? '10,000+' : count.toLocaleString('en-GB')) + (count === 1 ? ' product' : ' products');
        renderFacets(data);
        renderActive(data);
        var rules = Array.isArray(data.discount_rules) ? data.discount_rules.slice().sort(function (a, b) {
            return number(b.exclusive) - number(a.exclusive) || number(a.priority) - number(b.priority);
        }) : [];
        var cards = (data.products || []).map(function (product, index) {
            return makeCard(product, rules, Boolean(data.include_out_of_stock), index, data.whatsapp_number);
        }).filter(Boolean);
        nodes.grid.replaceChildren.apply(nodes.grid, cards);
        nodes.grid.setAttribute('aria-busy', 'false');
        renderPagination(count);
        if (cards.length) hideStatus();
        else showStatus('No products found. Try removing a filter.', false, false);
    }

    function load(force) {
        var params = new URLSearchParams(window.location.search);
        if (params.has('page') && String(pageNumber(params)) !== params.get('page')) {
            params.set('page', String(pageNumber(params)));
            window.history.replaceState({}, '', browserUrl(params).href);
        }
        var key = sourceUrl();
        requestNumber += 1;
        var thisRequest = requestNumber;
        if (controller) controller.abort();
        controller = null;
        if (!force && cache.has(key)) {
            render(cache.get(key));
            return;
        }
        nodes.grid.setAttribute('aria-busy', 'true');
        showStatus('Loading products…', true, false);
        controller = new AbortController();
        fetch(API + '?url=' + encodeURIComponent(key), { signal: controller.signal })
            .then(function (response) {
                if (!response.ok) throw new Error('Catalog request failed');
                return response.json();
            })
            .then(function (payload) {
                if (thisRequest !== requestNumber) return;
                var data = payload && payload.result;
                if (!data || !Array.isArray(data.products) || !Array.isArray(data.filter) || !Number.isFinite(Number(data.count))) {
                    throw new Error('Invalid catalog response');
                }
                cache.set(key, data);
                if (cache.size > 6) cache.delete(cache.keys().next().value);
                render(data);
            })
            .catch(function (error) {
                if (error.name === 'AbortError' || thisRequest !== requestNumber) return;
                nodes.grid.replaceChildren();
                nodes.grid.setAttribute('aria-busy', 'false');
                nodes.toolbar.hidden = true;
                nodes.active.hidden = true;
                nodes.pagination.hidden = true;
                showStatus('We could not load products right now.', false, true);
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
            updateParam('catalog_view', button.dataset.catalogView === 'large' ? '' : button.dataset.catalogView, true);
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
