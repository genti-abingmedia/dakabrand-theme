const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('theme/assets/js/catalog.js', 'utf8');

function fixture(href) {
    const requests = [];
    const window = { location: new URL(href) };
    const root = { querySelector() { return null; }, querySelectorAll() { return []; } };
    const document = {
        querySelector() { return root; },
        querySelectorAll() { return []; }
    };
    // Expose the request functions without starting the DOM rendering lifecycle.
    const setup = source.slice(0, source.indexOf('    enhanceExistingCardImages();')) +
        'window.catalog = { sourceUrl, fetchCatalogPage }; })();';
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
