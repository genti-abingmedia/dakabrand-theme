const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('theme/assets/js/catalog.js', 'utf8');

function fixture(href, config) {
    const requests = [];
    const window = { location: new URL(href), StaticBridgeConfig: config };
    const root = { querySelector() { return null; }, querySelectorAll() { return []; } };
    const document = {
        querySelector() { return root; },
        querySelectorAll() { return []; }
    };
    // Expose the request functions without starting the DOM rendering lifecycle.
    const setup = source.slice(0, source.indexOf('    enhanceExistingCardImages();')) +
        'window.catalog = { sourceUrl, fetchCatalogPage, storefrontCatalog, isStorefrontProduct }; })();';
    vm.runInNewContext(setup, {
        window, document, URL, URLSearchParams,
        fetch(url, options) {
            requests.push({ url, options });
            return Promise.resolve({ ok: true, json: () => Promise.resolve({
                result: { products: [], filter: [], count: 0 }
            }) });
        }
    });
    return { window, requests, ...window.catalog };
}

test('staged products stay hidden even with other categories and on old generated pages', () => {
    const page = fixture('https://dakabrand.uk/shop/');
    const staged = { id: 1, taxonomies: { categories: [{ slug: 'women' }, { slug: 'black-friday' }] } };
    const visible = { id: 2, taxonomies: { categories: [{ slug: 'women' }] } };
    assert.equal(page.isStorefrontProduct(staged), false);
    assert.equal(page.isStorefrontProduct(visible), true);
    assert.equal(page.isStorefrontProduct({ id: 3 }), true);
    const result = page.storefrontCatalog({ products: [staged, visible], count: 2, filter: [] });
    assert.deepEqual(Array.from(result.products, product => product.id), [2]);
    // The external service owns pagination/totals; never replace total with page length.
    assert.equal(result.count, 2);
});

test('configured staged descendants and nested category facets stay hidden without mutating responses', () => {
    const page = fixture('https://dakabrand.uk/shop/', {
        hiddenProductCategorySlugs: ['black-friday', 'bf-shoes']
    });
    const data = {
        products: [{ id: 1, taxonomies: { categories: [{ slug: 'bf-shoes' }] } }],
        filter: [{ slug: 'categories', options: [{ slug: 'women', children: [
            { slug: 'black-friday', children: [{ slug: 'bf-shoes' }] }, { slug: 'shirts' }
        ] }] }]
    };
    const result = page.storefrontCatalog(data);
    assert.equal(result.products.length, 0);
    assert.deepEqual(Array.from(result.filter[0].options[0].children, option => option.slug), ['shirts']);
    assert.equal(data.filter[0].options[0].children.length, 2);
});

test('launch config restores staged products and category choices', () => {
    const page = fixture('https://dakabrand.uk/shop/', { hiddenProductCategorySlugs: [] });
    const data = {
        products: [{ id: 1, taxonomies: { categories: [{ slug: 'black-friday' }] } }],
        filter: [{ slug: 'categories', options: [{ slug: 'black-friday' }] }]
    };
    const result = page.storefrontCatalog(data);
    assert.equal(result.products.length, 1);
    assert.equal(result.filter[0].options.length, 1);
});

test('Latest requests creation date descending while preserving search and filters on later pages', async () => {
    const page = fixture('https://dakabrand.uk/shop/?keyword=shoes&stock_status=instock%3Ainstock&brands=prada&catalog_view=list&catalog_filters=hidden&price=100-300&page=2');
    const signal = new AbortController().signal;
    await page.fetchCatalogPage(2, 10, signal);
    const request = page.requests[0];
    const url = new URL(new URL(request.url).searchParams.get('url'));
    assert.equal(url.searchParams.get('orderby'), 'date_created-desc');
    assert.equal(url.searchParams.get('keyword'), 'shoes');
    assert.equal(url.searchParams.get('stock_status'), 'instock:instock');
    assert.equal(url.searchParams.get('brands'), 'prada');
    assert.equal(url.searchParams.get('page'), '2');
    assert.equal(url.searchParams.get('limit'), '10');
    for (const param of ['catalog_view', 'catalog_filters', 'price']) {
        assert.equal(url.searchParams.has(param), false);
    }
    assert.equal(request.options.signal, signal);
});

test('switching price sorts back to Latest changes the request and cache URL', () => {
    const page = fixture('https://dakabrand.uk/shop/?keyword=shoes&orderby=price-asc');
    for (const sort of ['price-asc', 'price-desc']) {
        page.window.location.search = '?keyword=shoes&orderby=' + sort;
        const priceUrl = page.sourceUrl(1, 20);
        assert.equal(new URL(priceUrl).searchParams.get('orderby'), sort);
        page.window.location.search = '?keyword=shoes';
        const latestUrl = page.sourceUrl(1, 20);
        assert.equal(new URL(latestUrl).searchParams.get('orderby'), 'date_created-desc');
        assert.notEqual(latestUrl, priceUrl);
    }
});

test('Latest requests an explicit date order without a search and on category archives', () => {
    const page = fixture('https://dakabrand.uk/product-category/women/');
    const url = new URL(page.sourceUrl(1, 20));
    assert.equal(url.pathname, '/product-category/women/');
    assert.equal(url.searchParams.get('orderby'), 'date_created-desc');
});

test('localhost requests keep Latest ordering when using the production fallback', async () => {
    const page = fixture('http://localhost:8080/shop/?keyword=shoes');
    await page.fetchCatalogPage(1, 20);
    const url = new URL(new URL(page.requests[0].url).searchParams.get('url'));
    assert.equal(url.origin, 'https://dakabrand.uk');
    assert.equal(url.searchParams.get('orderby'), 'date_created-desc');
    assert.equal(url.searchParams.get('keyword'), 'shoes');
});
