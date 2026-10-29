const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const source = fs.readFileSync('theme/assets/js/halloween.js', 'utf8');
function setup({ reducedMotion = false, width = 1440, edgeRandom = 0, visitorPresent = true, imageWidth = 280 } = {}) {
    let now = 0, id = 0, randomCalls = 0, overlayObserver;
    const timers = new Map(), documentEvents = {}, windowEvents = {}, visitorEvents = {}, imageEvents = {};
    const classes = new Set(), attributes = {}, style = {}, smokeAttributes = {};
    const media = { matches: reducedMotion, addEventListener: (_, callback) => { media.change = callback; } };
    const image = { complete: true, naturalWidth: imageWidth, addEventListener: (name, callback) => { imageEvents[name] = callback; } };
    const visitor = {
        hidden: true, inert: true, querySelector: () => image,
        classList: { add: name => classes.add(name), remove: name => classes.delete(name) },
        setAttribute: (name, value) => { attributes[name] = value; },
        style: { setProperty: (name, value) => { style[name] = value; } },
        addEventListener: (name, callback) => { visitorEvents[name] = callback; }
    };
    const document = {
        hidden: false, overlay: false, activeElement: { matches: () => false },
        querySelector: selector => selector === '[data-halloween-smoke]'
            ? { toggleAttribute: (name, value) => { smokeAttributes[name] = value; } }
            : selector === '[data-halloween-visitor]' ? (visitorPresent ? visitor : null) : (document.overlay ? {} : null),
        querySelectorAll: () => [{}],
        addEventListener(name, callback) { (documentEvents[name] ||= []).push(callback); }
    };
    const window = { innerWidth: width, matchMedia: () => media,
        addEventListener: (name, callback) => { windowEvents[name] = callback; }, MutationObserver: true };
    vm.runInNewContext(source, {
        document, window,
        Math: { floor: Math.floor, random: () => (++randomCalls === 2 ? edgeRandom : 0) },
        setTimeout(callback, delay) { timers.set(++id, { callback, due: now + delay }); return id; },
        clearTimeout(timer) { timers.delete(timer); },
        MutationObserver: class { constructor(callback) { overlayObserver = callback; } observe() {} }
    });
    function tick(ms) {
        const end = now + ms;
        for (;;) {
            const next = [...timers.entries()].filter(([, t]) => t.due <= end).sort((a, b) => a[1].due - b[1].due)[0];
            if (!next) break;
            timers.delete(next[0]); now = next[1].due; next[1].callback();
        }
        now = end;
    }
    return { document, visitor, classes, attributes, style, media, imageEvents, visitorEvents, timers, smokeAttributes,
        tick, emit(name, event = {}) { (documentEvents[name] || []).forEach(callback => callback(event)); },
        changeOverlay(open) { document.overlay = open; overlayObserver(); }, windowEvents, window,
        emitWindow(name, event = {}) { if (windowEvents[name]) windowEvents[name](event); } };
}

test('visitor waits, peeks for five seconds, and leaves a quiet interval between appearances', () => {
    const h = setup();
    h.tick(1999); assert.equal(h.visitor.hidden, true);
    h.tick(41); assert.equal(h.visitor.hidden, false); assert(h.classes.has('is-peeking'));
    h.tick(4960); assert.equal(h.visitor.inert, true); assert(!h.classes.has('is-peeking'));
    h.tick(350); assert.equal(h.visitor.hidden, true);
    h.tick(29649); assert.equal(h.visitor.hidden, true);
    h.tick(41); assert(h.classes.has('is-peeking'));
});

test('all desktop edges are possible; mobile excludes the toolbar edge', () => {
    for (const [random, edge] of [[0, 'left'], [.26, 'right'], [.51, 'top'], [.99, 'bottom']]) {
        const h = setup({ edgeRandom: random }); h.tick(2040); assert.equal(h.attributes['data-edge'], edge);
    }
    for (const [random, edge] of [[0, 'left'], [.4, 'right'], [.99, 'top']]) {
        const h = setup({ width: 375, edgeRandom: random }); h.tick(2040); assert.equal(h.attributes['data-edge'], edge);
    }
});

test('click and Escape dismiss the visitor and return keyboard focus', () => {
    for (const escape of [false, true]) {
        const h = setup(); let restored = false;
        h.tick(2040);
        const previous = { isConnected: true, focus() { restored = true; } };
        h.visitorEvents.focus({ relatedTarget: previous }); h.document.activeElement = h.visitor;
        if (escape) h.emit('keydown', { key: 'Escape' }); else h.visitorEvents.click();
        assert.equal(restored, true); assert.equal(h.visitor.inert, true);
        h.tick(350); assert.equal(h.visitor.hidden, true);
    }
});

test('mobile scrolling, unrelated taps, and height-only resizes preserve the five-second visit', () => {
    const h = setup({ width: 375 });
    h.tick(2040);
    h.emit('scroll');
    h.emit('pointerdown', { target: h.document.activeElement });
    h.emit('click', { target: h.document.activeElement });
    h.window.innerHeight = 600;
    h.emitWindow('resize');
    h.window.innerHeight = 720;
    h.emitWindow('resize');
    assert.equal(h.visitor.hidden, false);
    assert(h.classes.has('is-peeking'));
    h.tick(4959);
    assert(h.classes.has('is-peeking'));
    h.tick(1);
    assert(!h.classes.has('is-peeking'));
    h.tick(350);
    assert.equal(h.visitor.hidden, true);
});

test('mobile tap dismisses the visitor', () => {
    const tapped = setup({ width: 375 });
    tapped.tick(2040);
    tapped.visitorEvents.click();
    assert(!tapped.classes.has('is-peeking'));
    tapped.tick(350);
    assert.equal(tapped.visitor.hidden, true);

});

test('width changes preserve active visits and do not restart the hide timer', () => {
    for (const width of [375, 1440]) {
        const h = setup({ width });
        h.tick(2040);
        h.window.innerWidth = width === 375 ? 812 : 1024;
        h.emitWindow('resize');
        assert.equal(h.visitor.hidden, false);
        assert(h.classes.has('is-peeking'));
        h.tick(4959);
        assert(h.classes.has('is-peeking'));
        h.tick(1);
        assert(!h.classes.has('is-peeking'));
        h.tick(350);
        assert.equal(h.visitor.hidden, true);
    }
});

test('opening an overlay immediately hides the visitor; typing postpones a visit', () => {
    const h = setup(); h.tick(2040); h.changeOverlay(true);
    assert.equal(h.visitor.hidden, true); assert(!h.classes.has('is-peeking'));
    h.tick(90000); assert.equal(h.visitor.hidden, true);
    const typing = setup(); typing.document.activeElement = { matches: () => true };
    typing.tick(2040); assert.equal(typing.visitor.hidden, true);
});

test('background tabs cancel visits; returning starts a fresh quiet interval', () => {
    const h = setup(); h.tick(2040); h.document.hidden = true; h.emit('visibilitychange');
    assert.equal(h.visitor.hidden, true); assert.equal(h.timers.size, 0); assert.equal(h.smokeAttributes['data-paused'], true);
    h.document.hidden = false; h.emit('visibilitychange');
    h.tick(29999); assert.equal(h.visitor.hidden, true);
    h.tick(41); assert(h.classes.has('is-peeking'));
});

test('reduced motion prevents visits initially and cancels an active visit when enabled', () => {
    const disabled = setup({ reducedMotion: true }); assert.equal(disabled.timers.size, 0);
    const h = setup(); h.tick(2040); h.media.matches = true; h.media.change();
    assert.equal(h.visitor.hidden, true); assert.equal(h.timers.size, 0);
});

test('failed artwork stops visits and checkout pages without a visitor schedule none', () => {
    const h = setup(); h.imageEvents.error(); assert.equal(h.timers.size, 0); assert.equal(h.visitor.hidden, true);
    assert.equal(setup({ imageWidth: 0 }).timers.size, 0);
    const checkout = setup({ visitorPresent: false }); assert.equal(checkout.timers.size, 0);
});

test('page lifecycle stops pending callbacks and resumes safely from back-forward cache', () => {
    const h = setup(); h.tick(2040); h.windowEvents.pagehide();
    assert.equal(h.visitor.hidden, true); assert.equal(h.timers.size, 0);
    h.windowEvents.pageshow({ persisted: true }); h.tick(30040); assert(h.classes.has('is-peeking'));
});
