'use strict';

const assert = require('node:assert/strict');
const http = require('node:http');
const test = require('node:test');

const {
    classifyPage,
    collectInventory,
    extractSchema,
    normalizeBaseUrl,
    parseHtmlInventory,
} = require('../cli/release1_inventory.js');

test('normalizes controlled inventory origins', () => {
    assert.equal(normalizeBaseUrl('http://127.0.0.1:8080/island/'), 'http://127.0.0.1:8080/island');
    assert.throws(() => normalizeBaseUrl('https://user:pass@example.test'));
    assert.throws(() => normalizeBaseUrl('https://example.test/?preview=1'));
});

test('extracts Release 1 metadata, links, images, and schema', () => {
    const html = `<!doctype html>
        <html><head>
            <title>Private Sri Lanka Tours</title>
            <meta name="description" content="Plan a private Sri Lanka journey with a flexible route.">
            <meta name="robots" content="index,follow">
            <link rel="canonical" href="https://example.test/private-tours">
            <script type="application/ld+json">{"@graph":[{"@type":"WebPage"},{"@type":"FAQPage"}]}</script>
        </head><body>
            <h1>Private Sri Lanka Tours</h1>
            <p>Build a route around your dates and interests.</p>
            <a href="/contact">Contact</a>
            <a href="https://other.example/guide">Guide</a>
            <img src="/hero.jpg" alt="Sri Lanka coast">
            <img src="/missing-alt.jpg">
        </body></html>`;

    const page = parseHtmlInventory(
        html,
        'https://example.test/private-tours',
        'https://example.test/private-tours',
        'https://example.test'
    );

    assert.equal(page.title, 'Private Sri Lanka Tours');
    assert.equal(page.description, 'Plan a private Sri Lanka journey with a flexible route.');
    assert.equal(page.robots, 'index,follow');
    assert.equal(page.h1Count, 1);
    assert.equal(page.outgoingInternalLinks.length, 1);
    assert.equal(page.outgoingExternalLinks.length, 1);
    assert.equal(page.imageCount, 2);
    assert.equal(page.missingAltCount, 1);
    assert.deepEqual(page.schemaTypes, ['FAQPage', 'WebPage']);
    assert.equal(page.invalidSchemaBlocks, 0);
    assert.ok(page.contentLength > 20);
});

test('classifies canonical merges, noindex pages, and healthy pages', () => {
    const base = {
        url: 'https://example.test/page',
        finalUrl: 'https://example.test/page',
        status: 200,
        redirects: [],
        error: null,
        title: 'Page',
        description: 'A complete page description for the inventory baseline.',
        robots: 'index,follow',
        canonical: 'https://example.test/page',
        h1Count: 1,
        contentLength: 100,
        invalidSchemaBlocks: 0,
        schemaTypes: ['WebPage'],
    };

    assert.equal(classifyPage(base, 'https://example.test').classification, 'KEEP');
    assert.equal(classifyPage({ ...base, canonical: 'https://example.test/other' }, 'https://example.test').classification, 'MERGE');
    assert.equal(classifyPage({ ...base, robots: 'noindex,follow' }, 'https://example.test').classification, 'NOINDEX');
    assert.equal(classifyPage({ ...base, robots: 'none' }, 'https://example.test').classification, 'NOINDEX');
    assert.equal(classifyPage({ ...base, status: 404 }, 'https://example.test').classification, 'DELETE');
    assert.equal(classifyPage({ ...base, status: 301, redirects: [{ status: 301 }] }, 'https://example.test').classification, 'REDIRECT');
    assert.equal(classifyPage({ ...base, status: 500, redirects: [{ status: 301 }] }, 'https://example.test').classification, 'IMPROVE');
});

test('invalid JSON-LD is counted without crashing the parser', () => {
    const schema = extractSchema('<script type="application/ld+json">{broken</script>');
    assert.deepEqual(schema.types, []);
    assert.equal(schema.invalid, 1);

    const topLevelArray = extractSchema('<script type="application/ld+json">[{"@type":"WebPage"}]</script>');
    assert.deepEqual(topLevelArray.types, ['WebPage']);
});

test('parses unquoted attributes and decodes internal link entities', () => {
    const page = parseHtmlInventory(
        '<meta name=description content="Fixture description"><link rel=canonical href="/page"><h1>Page</h1><a href="/page?a=1&amp;b=2">Query</a>',
        'https://example.test/page',
        'https://example.test/page',
        'https://example.test'
    );

    assert.equal(page.description, 'Fixture description');
    assert.deepEqual(page.outgoingInternalLinks, ['https://example.test/page?a=1&b=2']);
});

test('collects a complete inventory from a controlled local origin', async () => {
    const server = http.createServer((request, response) => {
        const host = request.headers.host;
        let body = '';
        let contentType = 'text/html';

        if (request.url === '/sitemap.xml') {
            contentType = 'application/xml';
            body = `<?xml version="1.0"?><urlset>
                <url><loc>http://${host}/</loc></url>
                <url><loc>http://${host}/page</loc></url>
            </urlset>`;
        } else if (request.url === '/' || request.url === '/page' || request.url === '/discovered') {
            const title = request.url === '/' ? 'Home' : 'Page';
            const link = request.url === '/'
                ? '<a href="/page">Page</a>'
                : request.url === '/page'
                    ? '<a href="/discovered">Discovered</a>'
                    : '';
            body = `<html><head>
                <title>${title}</title>
                <meta name="description" content="A complete description for this controlled inventory fixture.">
                <meta name="robots" content="index,follow">
                <link rel="canonical" href="http://${host}${request.url}">
                <script type="application/ld+json">{"@type":"WebPage"}</script>
            </head><body><h1>${title}</h1><p>Visible content.</p>${link}</body></html>`;
        } else {
            response.writeHead(404);
            response.end('Not found');
            return;
        }

        response.writeHead(200, { 'content-type': contentType });
        response.end(body);
    });

    await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
    try {
        const port = server.address().port;
        const inventory = await collectInventory({
            baseUrl: `http://127.0.0.1:${port}`,
            maxPages: 10,
        });

        assert.equal(inventory.inventoryStatus, 'complete');
        assert.equal(inventory.pageCount, 3);
        assert.deepEqual(inventory.summary.classifications, { KEEP: 3 });
        assert.deepEqual(inventory.sitemapComparison.discoveredOutsideSitemap, [`http://127.0.0.1:${port}/discovered`]);
        assert.equal(inventory.orphanCandidates.length, 0);
    } finally {
        await new Promise((resolve) => server.close(resolve));
    }
});

test('rejects an off-origin sitemap path without making a request', async () => {
    const inventory = await collectInventory({
        baseUrl: 'http://127.0.0.1:1',
        sitemapPath: 'https://outside.example/sitemap.xml',
        maxPages: 0,
    });

    assert.equal(inventory.inventoryStatus, 'blocked');
    assert.match(inventory.sitemapIssues[0].reason, /configured origin/);
    assert.equal(inventory.pageCount, 0);
});
