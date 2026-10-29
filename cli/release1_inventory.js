#!/usr/bin/env node
'use strict';

/**
 * Release 1 SEO inventory collector.
 *
 * This intentionally uses Node's HTTP clients instead of Playwright so it can
 * produce a machine-readable baseline on a controlled local/staging origin
 * even when a browser audit is unavailable. It never chooses a production
 * origin implicitly: --base-url is required.
 */

const fs = require('node:fs');
const http = require('node:http');
const https = require('node:https');
const path = require('node:path');

function parseArgs(values) {
    return Object.fromEntries(values
        .filter((value) => value.startsWith('--'))
        .map((value) => {
            const [key, ...parts] = value.slice(2).split('=');
            return [key, parts.length ? parts.join('=') : '1'];
        }));
}

function boundedInteger(value, fallback, min, max) {
    const parsed = Number.parseInt(value, 10);
    if (!Number.isFinite(parsed)) return fallback;
    return Math.min(max, Math.max(min, parsed));
}

function normalizeBaseUrl(value) {
    const parsed = new URL(String(value).trim());
    if (!['http:', 'https:'].includes(parsed.protocol)
        || parsed.username
        || parsed.password
        || parsed.search
        || parsed.hash) {
        throw new Error('base-url must be an HTTP(S) URL without credentials, query strings, or fragments');
    }

    return parsed.toString().replace(/\/$/, '');
}

function normalizeUrl(value, baseUrl) {
    try {
        const parsed = new URL(value, `${baseUrl.replace(/\/$/, '')}/`);
        if (!['http:', 'https:'].includes(parsed.protocol) || parsed.username || parsed.password) {
            return null;
        }
        parsed.hash = '';
        if (parsed.search === '?') parsed.search = '';
        return parsed.toString();
    } catch (_) {
        return null;
    }
}

function sameOrigin(first, second) {
    try {
        const a = new URL(first);
        const b = new URL(second);
        return a.protocol === b.protocol
            && a.hostname.toLowerCase() === b.hostname.toLowerCase()
            && effectivePort(a) === effectivePort(b);
    } catch (_) {
        return false;
    }
}

function withinBasePath(value, baseUrl) {
    try {
        const targetPath = new URL(value).pathname.replace(/\/$/, '');
        const configuredPath = new URL(baseUrl).pathname.replace(/\/$/, '');
        return configuredPath === ''
            || targetPath === configuredPath
            || targetPath.startsWith(`${configuredPath}/`);
    } catch (_) {
        return false;
    }
}

function effectivePort(url) {
    if (url.port) return url.port;
    return url.protocol === 'https:' ? '443' : '80';
}

function pageUrl(baseUrl, value) {
    return normalizeUrl(String(value).replace(/^\/+/, ''), `${baseUrl}/`);
}

function decodeXml(value) {
    return String(value)
        .replace(/&amp;/g, '&')
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .replace(/&quot;/g, '"')
        .replace(/&apos;/g, "'")
        .trim();
}

function decodeHtml(value) {
    return String(value)
        .replace(/&nbsp;/gi, ' ')
        .replace(/&amp;/gi, '&')
        .replace(/&lt;/gi, '<')
        .replace(/&gt;/gi, '>')
        .replace(/&quot;/gi, '"')
        .replace(/&#39;/gi, "'")
        .replace(/&#x([0-9a-f]+);/gi, (_, hex) => String.fromCodePoint(Number.parseInt(hex, 16)))
        .replace(/&#(\d+);/g, (_, number) => String.fromCodePoint(Number.parseInt(number, 10)));
}

function extractLocs(body) {
    return [...String(body).matchAll(/<loc\b[^>]*>([\s\S]*?)<\/loc>/gi)]
        .map((match) => decodeXml(match[1]))
        .filter(Boolean);
}

function isRedirectStatus(status) {
    return [301, 302, 303, 307, 308].includes(status);
}

function requestOnce(value, { timeoutMs, maxBodyBytes, ignoreHttpsErrors }) {
    return new Promise((resolve, reject) => {
        let parsed;
        try {
            parsed = new URL(value);
        } catch (error) {
            reject(error);
            return;
        }

        const startedAt = Date.now();
        const transport = parsed.protocol === 'https:' ? https : http;
        const request = transport.request(parsed, {
            method: 'GET',
            timeout: timeoutMs,
            rejectUnauthorized: !ignoreHttpsErrors,
            headers: {
                'User-Agent': 'ICT-Release1Inventory/1.0 (controlled SEO baseline)',
                Accept: 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.1',
                'Accept-Encoding': 'identity',
            },
        }, (response) => {
            const chunks = [];
            let bytes = 0;
            let capturedBytes = 0;
            let truncated = false;
            response.on('data', (chunk) => {
                const buffer = Buffer.isBuffer(chunk) ? chunk : Buffer.from(chunk);
                bytes += buffer.length;
                if (maxBodyBytes <= 0 || capturedBytes >= maxBodyBytes) {
                    truncated = true;
                    return;
                }

                const remaining = maxBodyBytes - capturedBytes;
                const slice = buffer.subarray(0, remaining);
                chunks.push(slice);
                capturedBytes += slice.length;
                if (slice.length < buffer.length) truncated = true;
            });
            response.on('end', () => resolve({
                status: response.statusCode || 0,
                headers: response.headers,
                body: Buffer.concat(chunks).toString('utf8'),
                bytes,
                truncated,
                responseTimeMs: Date.now() - startedAt,
                location: Array.isArray(response.headers.location)
                    ? response.headers.location[0]
                    : response.headers.location || null,
            }));
        });

        request.on('timeout', () => request.destroy(new Error('request timeout')));
        request.on('error', reject);
        request.end();
    });
}

async function fetchWithRedirects(value, {
    timeoutMs = 20000,
    maxRedirects = 5,
    maxBodyBytes = 2 * 1024 * 1024,
    baseUrl,
    ignoreHttpsErrors = false,
} = {}) {
    let current = value;
    const redirects = [];
    let responseTimeMs = 0;

    for (let hop = 0; hop <= maxRedirects; hop += 1) {
        let response;
        try {
            response = await requestOnce(current, { timeoutMs, maxBodyBytes, ignoreHttpsErrors });
        } catch (error) {
            return {
                status: 0,
                headers: {},
                body: '',
                bytes: 0,
                truncated: false,
                finalUrl: current,
                redirects,
                responseTimeMs,
                error: error instanceof Error ? error.message : String(error),
            };
        }

        responseTimeMs += response.responseTimeMs;
        if (!isRedirectStatus(response.status) || !response.location) {
            return {
                ...response,
                finalUrl: current,
                redirects,
                responseTimeMs,
                error: null,
            };
        }

        const next = normalizeUrl(response.location, current);
        if (!next) {
            return {
                ...response,
                finalUrl: current,
                redirects,
                responseTimeMs,
                error: 'invalid redirect location',
            };
        }
        if (baseUrl && (!sameOrigin(next, baseUrl) || !withinBasePath(next, baseUrl))) {
            redirects.push({ from: current, to: next, status: response.status, crossOrigin: true });
            return {
                ...response,
                finalUrl: current,
                redirects,
                responseTimeMs,
                error: 'redirect leaves configured origin',
            };
        }

        redirects.push({ from: current, to: next, status: response.status });
        current = next;
    }

    return {
        status: 0,
        headers: {},
        body: '',
        bytes: 0,
        truncated: false,
        finalUrl: current,
        redirects,
        responseTimeMs,
        error: 'too many redirects',
    };
}

function parseAttributes(tag) {
    const attributes = {};
    for (const match of String(tag).matchAll(/([a-zA-Z_:][\w:.-]*)\s*=\s*(?:(["'])(.*?)\2|([^\s"'=<>`]+))/gs)) {
        attributes[match[1].toLowerCase()] = match[3] ?? match[4] ?? '';
    }
    return attributes;
}

function extractMeta(html, name) {
    for (const match of String(html).matchAll(/<meta\b[^>]*>/gis)) {
        const attributes = parseAttributes(match[0]);
        if (String(attributes.name || '').toLowerCase() === name.toLowerCase()) {
            return decodeHtml(attributes.content || '').trim();
        }
    }
    return '';
}

function extractCanonical(html) {
    for (const match of String(html).matchAll(/<link\b[^>]*>/gis)) {
        const attributes = parseAttributes(match[0]);
        const rel = String(attributes.rel || '').toLowerCase().split(/\s+/).filter(Boolean);
        if (rel.includes('canonical')) return decodeHtml(attributes.href || '').trim();
    }
    return '';
}

function extractTitle(html) {
    const match = String(html).match(/<title\b[^>]*>([\s\S]*?)<\/title>/i);
    return match ? decodeHtml(stripTags(match[1])).replace(/\s+/g, ' ').trim() : '';
}

function stripTags(value) {
    return String(value)
        .replace(/<script\b[^>]*>[\s\S]*?<\/script>/gi, ' ')
        .replace(/<style\b[^>]*>[\s\S]*?<\/style>/gi, ' ')
        .replace(/<[^>]+>/g, ' ');
}

function extractH1Count(html) {
    return [...String(html).matchAll(/<h1\b[^>]*>/gi)].length;
}

function extractContentLength(html) {
    const bodyMatch = String(html).match(/<body\b[^>]*>([\s\S]*?)<\/body>/i);
    const text = decodeHtml(stripTags(bodyMatch ? bodyMatch[1] : html))
        .replace(/\s+/g, ' ')
        .trim();
    return text.length;
}

function extractLinks(html, finalUrl, baseUrl) {
    const internal = new Set();
    const external = new Set();
    const offBase = new Set();

    for (const match of String(html).matchAll(/<a\b[^>]*>/gis)) {
        const href = parseAttributes(match[0]).href;
        if (!href || /^(?:#|mailto:|tel:|javascript:|data:)/i.test(href.trim())) continue;
        const target = normalizeUrl(decodeHtml(href), finalUrl);
        if (!target) continue;
        if (sameOrigin(target, baseUrl) && withinBasePath(target, baseUrl)) internal.add(target);
        else if (sameOrigin(target, baseUrl)) offBase.add(target);
        else external.add(target);
    }

    return {
        internal: [...internal],
        external: [...external],
        offBase: [...offBase],
    };
}

function extractImages(html) {
    let count = 0;
    let missingAlt = 0;
    for (const match of String(html).matchAll(/<img\b[^>]*>/gis)) {
        count += 1;
        if (!Object.prototype.hasOwnProperty.call(parseAttributes(match[0]), 'alt')) missingAlt += 1;
    }
    return { count, missingAlt };
}

function extractSchema(html) {
    const types = new Set();
    let invalid = 0;

    for (const match of String(html).matchAll(/<script\b[^>]*type\s*=\s*(["'])application\/ld\+json\1[^>]*>([\s\S]*?)<\/script>/gi)) {
        let decoded;
        try {
            decoded = JSON.parse(match[2].trim());
        } catch (_) {
            invalid += 1;
            continue;
        }

        const nodes = Array.isArray(decoded)
            ? decoded
            : decoded && Array.isArray(decoded['@graph'])
                ? decoded['@graph']
                : [decoded];
        for (const node of nodes) {
            if (!node || typeof node !== 'object') continue;
            const nodeTypes = Array.isArray(node['@type']) ? node['@type'] : [node['@type']];
            for (const type of nodeTypes) {
                if (typeof type === 'string' && type.trim()) types.add(type.trim());
            }
        }
    }

    return { types: [...types].sort(), invalid };
}

function parseHtmlInventory(html, pageUrlValue, finalUrl, baseUrl) {
    const links = extractLinks(html, finalUrl || pageUrlValue, baseUrl || pageUrlValue);
    const images = extractImages(html);
    const schema = extractSchema(html);

    return {
        url: pageUrlValue,
        finalUrl: finalUrl || pageUrlValue,
        title: extractTitle(html),
        description: extractMeta(html, 'description'),
        robots: extractMeta(html, 'robots'),
        canonical: extractCanonical(html),
        h1Count: extractH1Count(html),
        contentLength: extractContentLength(html),
        outgoingInternalLinks: links.internal,
        outgoingExternalLinks: links.external,
        outgoingOffBaseLinks: links.offBase,
        imageCount: images.count,
        missingAltCount: images.missingAlt,
        schemaTypes: schema.types,
        invalidSchemaBlocks: schema.invalid,
    };
}

function classifyPage(page, baseUrl) {
    const issues = [];
    const requestedUrl = normalizeUrl(page.url, baseUrl);
    const canonicalUrl = page.canonical ? normalizeUrl(page.canonical, page.finalUrl || page.url) : null;
    const indexable = !/\b(?:noindex|none)\b/i.test(page.robots || '');

    if (page.status === 404 || page.status === 410) {
        return {
            classification: 'DELETE',
            issues: [`HTTP ${page.status}`, ...(page.redirects?.length ? ['HTTP redirect'] : [])],
        };
    }
    if (page.status === 0 || page.status >= 500) {
        return { classification: 'IMPROVE', issues: [page.error || `HTTP ${page.status}`] };
    }
    if (page.status >= 400) {
        return { classification: 'IMPROVE', issues: [`HTTP ${page.status}`] };
    }
    if (page.error) {
        return {
            classification: page.redirects?.length ? 'REDIRECT' : 'IMPROVE',
            issues: [page.error],
        };
    }
    if (page.redirects?.length || page.status >= 300) return { classification: 'REDIRECT', issues: ['HTTP redirect'] };
    if (!indexable) return { classification: 'NOINDEX', issues: ['robots noindex'] };
    if (!page.title) issues.push('missing title');
    if (!page.description) issues.push('missing meta description');
    if (page.h1Count !== 1) issues.push(`expected one H1, found ${page.h1Count}`);
    if (!page.canonical) {
        issues.push('missing canonical');
    } else if (!canonicalUrl || !sameOrigin(canonicalUrl, baseUrl) || !withinBasePath(canonicalUrl, baseUrl)) {
        issues.push('canonical is not same-origin');
    } else if (requestedUrl && canonicalUrl !== requestedUrl) {
        return { classification: 'MERGE', issues: ['canonical points to another URL'] };
    }
    if (page.invalidSchemaBlocks > 0) issues.push('invalid JSON-LD');
    if (page.schemaTypes.length === 0) issues.push('missing JSON-LD');
    if (page.contentLength === 0) issues.push('empty visible content');

    return {
        classification: issues.length ? 'IMPROVE' : 'KEEP',
        issues,
    };
}

async function fetchSitemapUrls(baseUrl, sitemapPath, options) {
    const queue = [];
    const requestedCandidate = pageUrl(baseUrl, sitemapPath);
    const requested = requestedCandidate && sameOrigin(requestedCandidate, baseUrl)
        ? requestedCandidate
        : null;
    const fallback = sitemapPath !== '/sitemap_index.php'
        ? pageUrl(baseUrl, '/sitemap_index.php')
        : null;
    if (requested) queue.push(requested);

    const seen = new Set();
    const successfulSitemaps = [];
    const urls = new Set([baseUrl + '/']);
    const issues = [];
    if (!requested) {
        return {
            urls: [...urls],
            issues: [{ url: String(sitemapPath), reason: 'sitemap path must stay on the configured origin' }],
            sitemaps: [],
            successfulSitemaps,
        };
    }
    const addIssue = (url, reason, optionalFallback = false) => {
        issues.push({ url, reason, ...(optionalFallback ? { optionalFallback: true } : {}) });
    };
    while (queue.length && seen.size < 100) {
        const sitemapUrl = queue.shift();
        if (seen.has(sitemapUrl)) continue;
        seen.add(sitemapUrl);

        const result = await fetchWithRedirects(sitemapUrl, {
            ...options,
            baseUrl,
            maxBodyBytes: 4 * 1024 * 1024,
        });
        if (result.error || result.status < 200 || result.status >= 400) {
            addIssue(sitemapUrl, result.error || `HTTP ${result.status}`, sitemapUrl === requested && fallback !== null);
            if (sitemapUrl === requested && fallback && !seen.has(fallback)) queue.push(fallback);
            continue;
        }

        const locs = extractLocs(result.body);
        if (!locs.length) {
            addIssue(sitemapUrl, 'no loc entries', sitemapUrl === requested && fallback !== null);
            if (sitemapUrl === requested && fallback && !seen.has(fallback)) queue.push(fallback);
            continue;
        }
        successfulSitemaps.push(sitemapUrl);

        const isIndex = /<sitemapindex\b/i.test(result.body) || /sitemap_index/i.test(sitemapUrl);
        for (const rawUrl of locs) {
            const normalized = normalizeUrl(rawUrl, sitemapUrl);
            if (!normalized || !sameOrigin(normalized, baseUrl) || !withinBasePath(normalized, baseUrl)) {
                addIssue(sitemapUrl, 'ignored loc outside configured origin or base path');
                continue;
            }
            if (isIndex) queue.push(normalized);
            else urls.add(normalized);
        }
    }

    if (seen.size >= 100) addIssue(baseUrl, 'sitemap recursion limit reached');
    const fallbackSucceeded = fallback !== null && successfulSitemaps.includes(fallback);
    const filteredIssues = issues
        .filter((issue) => !issue.optionalFallback || !fallbackSucceeded)
        .map(({ optionalFallback, ...issue }) => issue);
    return { urls: [...urls], issues: filteredIssues, sitemaps: [...seen], successfulSitemaps };
}

function pageCandidate(value, { includeQuery = false } = {}) {
    try {
        const parsed = new URL(value);
        if (!includeQuery && parsed.search && parsed.search !== '?') return false;
        return !/\.(?:xml|txt|ico|png|jpe?g|webp|gif|svg|avif|css|js|pdf|mp4|webm|mov)$/i.test(parsed.pathname);
    } catch (_) {
        return false;
    }
}

function routeShape(value) {
    try {
        const parsed = new URL(value);
        return {
            protocol: parsed.protocol.replace(':', ''),
            host: parsed.host,
            path: parsed.pathname,
            trailingSlash: parsed.pathname !== '/' && parsed.pathname.endsWith('/'),
        };
    } catch (_) {
        return null;
    }
}

async function collectInventory({
    baseUrl,
    sitemapPath = '/sitemap.xml',
    maxPages = 5000,
    timeoutMs = 20000,
    ignoreHttpsErrors = false,
    includeQueryPages = false,
} = {}) {
    const sitemap = await fetchSitemapUrls(baseUrl, sitemapPath, { timeoutMs, ignoreHttpsErrors });
    const sitemapPageUrls = new Set(sitemap.urls.filter((url) => pageCandidate(url, { includeQuery: true })));
    const pending = [...sitemapPageUrls];
    const queued = new Set(pending);
    const visited = new Set();
    const rows = [];
    const crawlIssues = [];
    const excludedQueryUrls = new Set();

    while (pending.length && visited.size < maxPages) {
        const url = pending.shift();
        if (visited.has(url)) continue;
        visited.add(url);

        const result = await fetchWithRedirects(url, {
            timeoutMs,
            baseUrl,
            ignoreHttpsErrors,
            maxBodyBytes: 4 * 1024 * 1024,
        });
        const page = parseHtmlInventory(result.body, url, result.finalUrl, baseUrl);
        const xRobotsTag = Array.isArray(result.headers['x-robots-tag'])
            ? result.headers['x-robots-tag'].join(', ')
            : String(result.headers['x-robots-tag'] || '');
        page.xRobotsTag = xRobotsTag;
        page.robots = [page.robots, xRobotsTag].filter((value) => value.trim() !== '').join(', ');
        page.canonicalTarget = page.canonical
            ? normalizeUrl(page.canonical, result.finalUrl || url)
            : null;
        const classification = classifyPage({
            ...page,
            status: result.status,
            redirects: result.redirects,
            error: result.error,
        }, baseUrl);
        const issues = [...classification.issues];
        if (result.truncated) issues.push('response body truncated at capture limit');
        const finalClassification = result.truncated && classification.classification === 'KEEP'
            ? 'IMPROVE'
            : classification.classification;
        rows.push({
            ...page,
            inSitemap: sitemapPageUrls.has(url),
            discoveredFrom: sitemapPageUrls.has(url) ? 'sitemap' : 'internal-link',
            status: result.status,
            contentType: result.headers['content-type'] || '',
            responseBytes: result.bytes,
            responseTruncated: result.truncated,
            responseTimeMs: result.responseTimeMs,
            redirects: result.redirects,
            error: result.error,
            classification: finalClassification,
            issues,
            outgoingInternalLinkCount: page.outgoingInternalLinks.length,
            outgoingExternalLinkCount: page.outgoingExternalLinks.length,
            outgoingOffBaseLinkCount: page.outgoingOffBaseLinks.length,
        });

        for (const target of page.outgoingInternalLinks) {
            if (!includeQueryPages && new URL(target).search) excludedQueryUrls.add(target);
            if (pageCandidate(target, { includeQuery: includeQueryPages }) && !queued.has(target)) {
                pending.push(target);
                queued.add(target);
            }
        }
        if (page.canonicalTarget && sameOrigin(page.canonicalTarget, baseUrl)
            && withinBasePath(page.canonicalTarget, baseUrl)
            && pageCandidate(page.canonicalTarget, { includeQuery: includeQueryPages })
            && !queued.has(page.canonicalTarget)) {
            pending.push(page.canonicalTarget);
            queued.add(page.canonicalTarget);
        } else if (page.canonicalTarget && !includeQueryPages && new URL(page.canonicalTarget).search) {
            excludedQueryUrls.add(page.canonicalTarget);
        }
    }

    const limitReached = pending.length > 0;
    if (limitReached) {
        crawlIssues.push({
            type: 'crawl_limit_reached',
            detail: `${maxPages} page limit reached before all discovered URLs were fetched`,
        });
    }

    const rowByUrl = new Map(rows.map((row) => [row.url, row]));
    const incoming = new Map(rows.map((row) => [row.url, new Set()]));
    const brokenInternalLinks = new Map();
    for (const row of rows) {
        for (const target of row.outgoingInternalLinks) {
            if (target === row.url) continue;
            if (incoming.has(target)) incoming.get(target).add(row.url);

            const targetRow = rowByUrl.get(target);
            if (targetRow && (targetRow.error || targetRow.status >= 400 || targetRow.status === 0)) {
                brokenInternalLinks.set(`${row.url}|${target}`, {
                    source: row.url,
                    target,
                    status: targetRow.status,
                    error: targetRow.error || null,
                });
            }
        }
        for (const target of row.outgoingOffBaseLinks) {
            brokenInternalLinks.set(`${row.url}|${target}`, {
                source: row.url,
                target,
                status: null,
                error: 'outside configured base path',
            });
        }
    }

    const pages = rows.map((row) => {
        const canonicalTargetRow = row.canonicalTarget ? rowByUrl.get(row.canonicalTarget) : null;
        const canonicalTargetStatus = canonicalTargetRow?.status ?? null;
        const canonicalTargetInvalid = canonicalTargetRow
            && (canonicalTargetRow.error || canonicalTargetRow.status >= 400 || canonicalTargetRow.status === 0);
        const page = {
            ...row,
            requestedShape: routeShape(row.url),
            finalShape: routeShape(row.finalUrl),
            canonicalShape: routeShape(row.canonicalTarget),
            canonicalTargetStatus,
            canonicalTargetClassification: canonicalTargetRow?.classification ?? null,
            incomingInternalLinkCount: incoming.get(row.url)?.size || 0,
            incomingInternalLinks: [...(incoming.get(row.url) || [])],
        };
        if (canonicalTargetInvalid) {
            page.issues = [...page.issues, 'canonical target is unavailable'];
            if (page.classification === 'MERGE') page.classification = 'IMPROVE';
        }
        return page;
    });
    const orphanCandidates = pages
        .filter((row) => row.url !== baseUrl + '/'
            && row.status >= 200
            && row.status < 300
            && ['KEEP', 'IMPROVE'].includes(row.classification)
            && row.incomingInternalLinkCount === 0)
        .map((row) => row.url);
    const discoveredOutsideSitemap = pages.filter((row) => !row.inSitemap).map((row) => row.url);
    const classifications = {};
    const statuses = {};
    const routeShapes = {};
    for (const row of pages) {
        classifications[row.classification] = (classifications[row.classification] || 0) + 1;
        statuses[String(row.status)] = (statuses[String(row.status)] || 0) + 1;
        const shape = row.requestedShape;
        if (shape) {
            const key = `${shape.protocol}://${shape.host}${shape.path}`;
            routeShapes[key] = (routeShapes[key] || 0) + 1;
        }
    }

    const inventoryStatus = sitemap.successfulSitemaps.length === 0
        ? 'blocked'
        : sitemap.issues.length > 0
            || crawlIssues.length > 0
            || rows.some((row) => row.error || row.responseTruncated)
            ? 'partial'
            : 'complete';

    return {
        generatedAt: new Date().toISOString(),
        baseUrl,
        sitemapPath,
        sitemaps: sitemap.sitemaps,
        successfulSitemaps: sitemap.successfulSitemaps,
        sitemapIssues: sitemap.issues,
        crawlIssues,
        inventoryStatus,
        pageLimit: maxPages,
        pageLimitReached: limitReached,
        pageCount: pages.length,
        pages,
        orphanCandidates,
        brokenInternalLinks: [...brokenInternalLinks.values()],
        sitemapComparison: {
            sitemapPageCount: sitemapPageUrls.size,
            discoveredPageCount: pages.length,
            discoveredOutsideSitemap,
        },
        queryPagesIncluded: includeQueryPages,
        excludedQueryUrls: [...excludedQueryUrls],
        summary: {
            classifications,
            statuses,
            routeShapes,
            orphanCount: orphanCandidates.length,
            brokenInternalLinkCount: brokenInternalLinks.size,
            offBaseLinkCount: pages.reduce((total, row) => total + row.outgoingOffBaseLinkCount, 0),
            discoveredOutsideSitemapCount: discoveredOutsideSitemap.length,
            excludedQueryUrlCount: excludedQueryUrls.size,
        },
    };
}

async function main() {
    const args = parseArgs(process.argv.slice(2));
    if (!args['base-url']) {
        throw new Error('base-url is required; use a controlled local or staging origin');
    }

    const inventory = await collectInventory({
        baseUrl: normalizeBaseUrl(args['base-url']),
        sitemapPath: args['sitemap-path'] || '/sitemap.xml',
        maxPages: boundedInteger(args['max-pages'], 5000, 1, 10000),
        timeoutMs: boundedInteger(args['page-timeout-ms'], 20000, 1000, 120000),
        ignoreHttpsErrors: args['ignore-https-errors'] === '1',
        includeQueryPages: args['include-query-pages'] === '1',
    });
    const output = JSON.stringify(inventory, null, 2) + '\n';
    if (args.output) {
        fs.mkdirSync(path.dirname(path.resolve(args.output)), { recursive: true });
        fs.writeFileSync(args.output, output, 'utf8');
        process.stderr.write(`Release 1 inventory written to ${args.output}\n`);
        return;
    }
    process.stdout.write(output);
}

module.exports = {
    classifyPage,
    collectInventory,
    extractCanonical,
    extractSchema,
    normalizeBaseUrl,
    normalizeUrl,
    parseHtmlInventory,
};

if (require.main === module) {
    main().catch((error) => {
        process.stderr.write(`Release 1 inventory failed: ${error instanceof Error ? error.message : String(error)}\n`);
        process.exitCode = 1;
    });
}
