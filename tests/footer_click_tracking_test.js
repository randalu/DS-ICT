'use strict';

const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const test = require('node:test');

function buildClickHarness(sendBeacon, grants = { first_party: true, gtm: true }) {
    const footerPath = path.join(__dirname, '..', 'includes', 'footer.php');
    const footer = fs.readFileSync(footerPath, 'utf8');
    const marker = /<script>\s*\(function\s*\(\)\s*\{\s*var endpoint\s*=/;
    const match = marker.exec(footer);
    const start = match ? match.index : -1;
    assert.notEqual(start, -1, 'expected click tracking script marker in footer.php');

    const scriptStart = footer.indexOf('>', start) + 1;
    const scriptEnd = footer.indexOf('</script>', scriptStart);
    assert.notEqual(scriptEnd, -1, 'expected click tracking script closing tag');

    const script = footer.slice(scriptStart, scriptEnd)
        .replace("<?= e(ict_privacy_url('track-event.php')) ?>", '/track-event.php')
        .replace('<?= json_encode(csrf_token(), JSON_UNESCAPED_SLASHES) ?>', '"fixture-csrf-token"');
    assert.doesNotMatch(script, /<\?=/, 'PHP template values should be replaced for this browser-free harness');

    let clickListener = null;
    const fetchCalls = [];
    const beaconCalls = [];
    const window = {
        location: {
            href: 'https://example.test/page',
            origin: 'https://example.test',
        },
        dataLayer: [],
        ICTPrivacy: { pageGroup: 'contact', canTrack: transport => grants[transport] === true },
    };
    const navigator = {
        sendBeacon(...args) {
            beaconCalls.push(args);
            return sendBeacon();
        },
    };
    const document = {
        addEventListener(type, listener, useCapture) {
            assert.equal(type, 'click');
            assert.equal(useCapture, true);
            clickListener = listener;
        },
    };
    const context = {
        Blob: class Blob {
            constructor(parts, options) {
                this.parts = parts;
                this.options = options;
            }
        },
        URL,
        document,
        fetch(...args) {
            fetchCalls.push(args);
            return Promise.resolve({ ok: true });
        },
        navigator,
        window,
    };

    vm.runInNewContext(script, context, { filename: 'includes/footer.php#click-tracking' });
    assert.equal(typeof clickListener, 'function');

    const link = {
        classList: { contains: () => false },
        textContent: 'Call us',
        getAttribute(name) {
            if (name === 'href') return 'tel:+94769192427';
            return null;
        },
    };
    clickListener({ target: { closest: () => link } });

    return { beaconCalls, fetchCalls, window };
}

test('falls back to fetch when sendBeacon reports that it did not queue the request', () => {
    const result = buildClickHarness(() => false);

    assert.equal(result.beaconCalls.length, 1);
    assert.equal(result.fetchCalls.length, 1);
    assert.equal(result.fetchCalls[0][0], '/track-event.php');
    assert.equal(result.window.dataLayer.length, 1);
    assert.equal(result.window.dataLayer[0].event, 'phone_click');
});

test('refused choices send neither dataLayer, beacon nor fetch and never block navigation', () => {
    const result = buildClickHarness(() => true, { first_party: false, gtm: false });
    assert.equal(result.beaconCalls.length, 0);
    assert.equal(result.fetchCalls.length, 0);
    assert.equal(result.window.dataLayer.length, 0);
});

test('operator GTM off permits only explicitly allowed first-party transport', () => {
    const result = buildClickHarness(() => false, { first_party: true, gtm: false });
    assert.equal(result.beaconCalls.length, 1);
    assert.equal(result.fetchCalls.length, 1);
    assert.equal(result.window.dataLayer.length, 0);
    const payload = JSON.parse(result.fetchCalls[0][1].body);
    assert.deepEqual(payload, { event_type: 'phone_click', _token: 'fixture-csrf-token' });
    assert.equal(JSON.stringify(payload).includes('9476'), false);
});

test('Google measurement contains only event and coarse public source', () => {
    const result = buildClickHarness(() => true, { first_party: false, gtm: true });
    assert.equal(result.beaconCalls.length, 0);
    assert.equal(result.fetchCalls.length, 0);
    assert.deepEqual(JSON.parse(JSON.stringify(result.window.dataLayer[0])), { event: 'phone_click', source_page: 'contact' });
});

test('does not send a fallback after sendBeacon successfully queues the request', () => {
    const result = buildClickHarness(() => true);

    assert.equal(result.beaconCalls.length, 1);
    assert.equal(result.fetchCalls.length, 0);
    assert.equal(result.window.dataLayer.length, 1);
});

test('falls back to fetch if sendBeacon throws before queueing', () => {
    const result = buildClickHarness(() => {
        throw new Error('beacon unavailable');
    });

    assert.equal(result.beaconCalls.length, 1);
    assert.equal(result.fetchCalls.length, 1);
    assert.equal(result.window.dataLayer.length, 1);
});
