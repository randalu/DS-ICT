'use strict';

const assert = require('node:assert/strict');
const test = require('node:test');

const {
    canonicalTargetAllowed,
    internalTargetAllowed,
    normalizeBaseUrl,
    normalizeUrl,
} = require('../cli/site_audit.js');

test('normalizes audit URLs without unsafe components', () => {
    assert.equal(normalizeBaseUrl('http://127.0.0.1:8080/island/'), 'http://127.0.0.1:8080/island');
    assert.equal(normalizeUrl('/destinations', 'http://127.0.0.1:8080/island'), 'http://127.0.0.1:8080/destinations');
    assert.throws(() => normalizeBaseUrl('https://user:pass@example.test'));
});

test('allows same-origin local audit targets', async () => {
    assert.equal(
        await internalTargetAllowed(
            'http://127.0.0.1:8080/island/destinations',
            'http://127.0.0.1:8080/island'
        ),
        true
    );
});

test('rejects internal redirects that change host or port', async () => {
    assert.equal(
        await internalTargetAllowed(
            'http://127.0.0.1:8081/private',
            'http://127.0.0.1:8080/island'
        ),
        false
    );
    assert.equal(
        await internalTargetAllowed(
            'http://external.example/private',
            'http://127.0.0.1:8080/island'
        ),
        false
    );
});

test('requires canonical URLs to stay on the configured origin', () => {
    assert.equal(
        canonicalTargetAllowed(
            '/destinations',
            'https://example.test/island/home',
            'https://example.test/island'
        ),
        true
    );
    assert.equal(
        canonicalTargetAllowed(
            'https://other.example/destinations',
            'https://example.test/island/home',
            'https://example.test/island'
        ),
        false
    );
    assert.equal(
        canonicalTargetAllowed(
            'javascript:alert(1)',
            'https://example.test/island/home',
            'https://example.test/island'
        ),
        false
    );
});
