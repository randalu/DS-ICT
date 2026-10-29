#!/usr/bin/env node
'use strict';

const dns = require('node:dns').promises;
const http = require('node:http');
const https = require('node:https');
const net = require('node:net');

let chromium = null;

const args = Object.fromEntries(
    process.argv.slice(2)
        .filter((value) => value.startsWith('--'))
        .map((value) => {
            const [key, ...parts] = value.slice(2).split('=');
            return [key, parts.join('=')];
        })
);

const RUN_ID = args['run-id'] || '0';
let BASE_URL = '';
const MAX_PAGES = boundedInteger(args['max-pages'], 5000, 1, 10000);
const MAX_INTERNAL_LINKS = boundedInteger(args['max-internal-links'], 5000, 1, 20000);
const MAX_EXTERNAL_LINKS = boundedInteger(args['max-external-links'], 25, 0, 200);
const PAGE_TIMEOUT_MS = boundedInteger(args['page-timeout-ms'], 20000, 1000, 120000);
const IGNORE_HTTPS_ERRORS = args['ignore-https-errors'] === '1';

try {
    BASE_URL = normalizeBaseUrl(args['base-url'] || (args.preflight === '1' || require.main !== module ? 'http://127.0.0.1' : ''));
} catch (error) {
    emit({ fatal: error instanceof Error ? error.message : String(error) });
    process.exitCode = 1;
}

if (require.main === module) {
    try {
        chromium = require('playwright').chromium;
    } catch (error) {
        emit({ fatal: error instanceof Error ? error.message : String(error) });
        process.exitCode = 1;
    }
}

function boundedInteger(value, fallback, min, max) {
    const parsed = Number.parseInt(value, 10);
    if (!Number.isFinite(parsed)) return fallback;
    return Math.min(max, Math.max(min, parsed));
}

function normalizeBaseUrl(value) {
    const parsed = new URL(String(value).trim());
    if (!['http:', 'https:'].includes(parsed.protocol) || parsed.username || parsed.password || parsed.search || parsed.hash) {
        throw new Error('base-url must be an HTTP(S) URL without credentials, query strings, or fragments');
    }
    return parsed.toString().replace(/\/$/, '');
}

function emit(value) {
    process.stdout.write(`${JSON.stringify(value)}\n`);
}

function pageUrl(path) {
    return new URL(path.replace(/^\/+/, ''), `${BASE_URL}/`).toString();
}

function normalizeUrl(value, base = BASE_URL) {
    try {
        const parsed = new URL(value, `${base}/`);
        if (!['http:', 'https:'].includes(parsed.protocol) || parsed.username || parsed.password) return null;
        parsed.hash = '';
        return parsed.toString();
    } catch (_) {
        return null;
    }
}

function sameSite(first, second) {
    try {
        const a = new URL(first);
        const b = new URL(second);
        return a.hostname.toLowerCase() === b.hostname.toLowerCase()
            && effectivePort(a) === effectivePort(b);
    } catch (_) {
        return false;
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

function canonicalTargetAllowed(value, resolutionBase = BASE_URL, expectedBase = BASE_URL) {
    const normalized = normalizeUrl(value, resolutionBase);
    return normalized !== null && sameOrigin(normalized, expectedBase);
}

function effectivePort(url) {
    if (url.port) return url.port;
    return url.protocol === 'https:' ? '443' : '80';
}

function isRedirectStatus(status) {
    return [301, 302, 303, 307, 308].includes(status);
}

function assetKind(value) {
    try {
        const pathname = new URL(value).pathname.toLowerCase();
        if (pathname.endsWith('.css')) return 'css';
        if (pathname.endsWith('.js')) return 'js';
        if (/\.(png|jpe?g|webp|gif|svg|ico|avif)$/.test(pathname)) return 'image';
        if (/\.(woff2?|ttf|otf)$/.test(pathname)) return 'font';
    } catch (_) {
    }
    return null;
}

function httpRequest(value, { timeoutMs = 10000, maxBodyBytes = 65536, publicOnly = false } = {}) {
    return new Promise((resolve, reject) => {
        const parsed = new URL(value);
        const transport = parsed.protocol === 'https:' ? https : http;
        const request = transport.request(parsed, {
            method: maxBodyBytes === 0 ? 'HEAD' : 'GET',
            timeout: timeoutMs,
            ...(publicOnly ? { lookup: (host, options, callback) => {
                dns.lookup(host, { all: true, verbatim: true }).then(addresses => {
                    if (!addresses.length || addresses.some(x => isPrivateIp(x.address))) return callback(new Error('unsafe DNS address'));
                    if (options.all) callback(null, addresses); else callback(null, addresses[0].address, addresses[0].family);
                }, callback);
            } } : {}),
            headers: {
                'User-Agent': 'ICT-SiteAudit/1.0 (Island Culture Travels internal audit)',
                Accept: 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.1',
            },
        }, (response) => {
            let body = '';
            response.setEncoding('utf8');
            response.on('data', (chunk) => {
                if (maxBodyBytes <= 0 || body.length >= maxBodyBytes) return;
                body += chunk.slice(0, maxBodyBytes - body.length);
            });
            response.on('end', () => resolve({
                status: response.statusCode || 0,
                body,
                location: Array.isArray(response.headers.location)
                    ? response.headers.location[0]
                    : response.headers.location || null,
            }));
        });

        request.on('timeout', () => request.destroy(new Error('request timeout')));
        const deadline = setTimeout(() => request.destroy(new Error('request timeout')), timeoutMs);
        request.on('close', () => clearTimeout(deadline));
        request.on('error', reject);
        request.end();
    });
}

async function fetchWithRedirects(value, {
    timeoutMs = 10000,
    maxRedirects = 5,
    maxBodyBytes = 65536,
    beforeRequest = null,
    publicOnly = false,
} = {}) {
    let current = value;
    const redirects = [];

    for (let hop = 0; hop <= maxRedirects; hop += 1) {
        if (beforeRequest && !(await beforeRequest(current))) {
            return { status: 0, body: '', finalUrl: current, redirects, error: 'unsafe redirect target' };
        }

        let response;
        try {
            response = await httpRequest(current, { timeoutMs, maxBodyBytes, publicOnly });
        } catch (error) {
            return {
                status: 0,
                body: '',
                finalUrl: current,
                redirects,
                error: error instanceof Error ? error.message : String(error),
            };
        }

        if (!isRedirectStatus(response.status) || !response.location) {
            return { ...response, finalUrl: current, redirects, error: null };
        }

        let next;
        try {
            next = new URL(response.location, current).toString();
        } catch (_) {
            return { ...response, finalUrl: current, redirects, error: 'invalid redirect location' };
        }
        if (!['http:', 'https:'].includes(new URL(next).protocol)) {
            return { ...response, finalUrl: current, redirects, error: 'unsupported redirect scheme' };
        }

        redirects.push({ from: current, to: next, status: response.status });
        current = next;
    }

    return {
        status: 0,
        body: '',
        finalUrl: current,
        redirects,
        error: 'too many redirects',
    };
}

function decodeXml(value) {
    return value
        .replace(/&amp;/g, '&')
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>')
        .replace(/&quot;/g, '"')
        .replace(/&apos;/g, "'")
        .trim();
}

function extractLocs(body) {
    return [...String(body).matchAll(/<loc\b[^>]*>([\s\S]*?)<\/loc>/gi)]
        .map((match) => decodeXml(match[1]))
        .filter(Boolean);
}

function issue(severity, type, pageUrlValue, detail = null) {
    return {
        run_id: RUN_ID,
        severity,
        type,
        page_url: String(pageUrlValue).slice(0, 2048),
        detail: detail === null ? null : String(detail).slice(0, 2000),
    };
}

async function fetchSitemapUrls() {
    const urls = new Set([pageUrl('/')]);
    const sitemapIssues = [];
    const sitemapQueue = [pageUrl('sitemap_index.php')];
    const seenSitemaps = new Set();

    while (sitemapQueue.length && seenSitemaps.size < 100) {
        const sitemapUrl = sitemapQueue.shift();
        if (seenSitemaps.has(sitemapUrl)) continue;
        seenSitemaps.add(sitemapUrl);

        const result = await fetchWithRedirects(sitemapUrl, {
            maxBodyBytes: 2 * 1024 * 1024,
            beforeRequest: async (candidate) => internalTargetAllowed(candidate),
        });
        if (result.error || result.status < 200 || result.status >= 400) {
            sitemapIssues.push(issue(
                'critical',
                'sitemap_fetch_failed',
                sitemapUrl,
                result.error || `HTTP ${result.status}`
            ));
            continue;
        }

        const locs = extractLocs(result.body);
        const isIndex = /<sitemapindex\b/i.test(result.body);
        if (!isIndex && !/<urlset\b/i.test(result.body)) { sitemapIssues.push(issue('high','sitemap_invalid',sitemapUrl,'Response is not a sitemap XML document')); continue; }
        for (const rawUrl of locs) {
            const normalized = normalizeUrl(rawUrl, sitemapUrl);
            if (!normalized || !sameSite(normalized, BASE_URL)) continue;
            if (isIndex) sitemapQueue.push(normalized);
            else urls.add(normalized);
        }
    }

    if (sitemapQueue.length >= 100) {
        sitemapIssues.push(issue('medium', 'sitemap_limit_reached', pageUrl('sitemap_index.php'), 'Sitemap recursion limit reached'));
    }

    return { urls: [...urls], issues: sitemapIssues };
}

function isPageCandidate(value) {
    try {
        const pathname = new URL(value).pathname.toLowerCase();
        return !/\.(xml|txt|ico|png|jpe?g|webp|gif|svg|avif|css|js|pdf|mp4|webm|mov|woff2?|ttf|otf)$/.test(pathname);
    } catch (_) {
        return false;
    }
}

function isPrivateIp(value) {
    const normalized = String(value).toLowerCase();
    if (normalized.startsWith('::ffff:')) return isPrivateIp(normalized.slice(7));

    if (net.isIP(normalized) === 4) {
        const parts = normalized.split('.').map(Number);
        const [first, second] = parts;
        return first === 0
            || first === 10
            || first === 127
            || (first === 100 && second >= 64 && second <= 127)
            || (first === 169 && second === 254)
            || (first === 172 && second >= 16 && second <= 31)
            || (first === 192 && second === 168)
            || (first === 198 && (second === 18 || second === 19))
            || first >= 224;
    }

    if (net.isIP(normalized) === 6) {
        return normalized === '::'
            || normalized === '::1'
            || normalized.startsWith('fc')
            || normalized.startsWith('fd')
            || normalized.startsWith('fe80:');
    }

    return true;
}

async function publicTarget(value) {
    let parsed;
    try {
        parsed = new URL(value);
    } catch (_) {
        return { allowed: false, unresolved: true };
    }
    if (!['http:', 'https:'].includes(parsed.protocol) || parsed.username || parsed.password) {
        return { allowed: false, unresolved: false };
    }

    const host = parsed.hostname.toLowerCase();
    if (host === 'localhost' || isPrivateIp(host) === true && net.isIP(host) !== 0) {
        return { allowed: false, unresolved: false };
    }
    if (net.isIP(host) !== 0) return { allowed: true, unresolved: false };

    try {
        const addresses = await dns.lookup(host, { all: true, verbatim: true });
        if (!addresses.length || addresses.some(({ address }) => isPrivateIp(address))) {
            return { allowed: false, unresolved: false };
        }
        return { allowed: true, unresolved: false };
    } catch (_) {
        return { allowed: false, unresolved: true };
    }
}

function auditTargetUsesLocalHost(value) {
    try {
        const parsed = new URL(value);
        const host = parsed.hostname.toLowerCase();
        return host === 'localhost'
            || (net.isIP(host) !== 0 && isPrivateIp(host));
    } catch (_) {
        return false;
    }
}

async function internalTargetAllowed(value, base = BASE_URL) {
    const normalized = normalizeUrl(value, base);
    if (!normalized || !publicRouteAllowed(normalized, base)) return false;
    if (auditTargetUsesLocalHost(base)) return true;
    return (await publicTarget(normalized)).allowed;
}

async function inspectExternalLink(target) {
    const safety = await publicTarget(target);
    if (!safety.allowed) {
        return {
            result: null,
            unresolved: safety.unresolved,
        };
    }

    const result = await fetchWithRedirects(target, {
        publicOnly: true,
        timeoutMs: 8000,
        maxBodyBytes: 16384,
        beforeRequest: async (candidate) => (await publicTarget(candidate)).allowed,
    });
    return { result, unresolved: false };
}

function publicRouteAllowed(value, base = BASE_URL) {
    try {
        const url = new URL(value), root = new URL(base);
        if (!sameOrigin(url, root) || url.username || url.password) return false;
        const prefix = root.pathname.replace(/\/$/, '');
        const path = decodeURIComponent(url.pathname);
        if (prefix && path !== prefix && !path.startsWith(prefix + '/')) return false;
        const relative = path.slice(prefix.length);
        if (/\/(?:admin|account(?:[_.-][a-z0-9_-]+)?|logout|login|install|cli|config|includes|database|output|vendor|tests)(?:[/.]|$)/i.test(relative)) return false;
        if (/(?:^|[\/_-])(?:save|delete|remove|update|track-event|privacy_choices_save)(?:[/.]|$)/i.test(relative)) return false;
        if (relative.includes('..') || relative.includes('\\') || relative.split('/').some(x => x.startsWith('.'))) return false;
        if (/\.php$/i.test(relative) && !/^\/(?:index|about|contact|destinations|destination|itineraries|itinerary|tours|tour|experiences|experience|day-tours|day-tour|blog|blog-post|country|from|reviews|static_page|sitemap_index|sitemap_partial|robots)\.php$/i.test(relative)) return false;
        const resource = /\.(?:css|js|png|jpe?g|svg|webp|avif|gif|ico|woff2?|ttf|otf|mp4|webm)$/i.test(relative);
        for (const [key,value] of url.searchParams) {
            if (resource && ['v','ver','version'].includes(key) && /^[a-zA-Z0-9_.-]{1,100}$/.test(value)) continue;
            if (key !== 'page' || !/^[1-9][0-9]{0,3}$/.test(value)) return false;
        }
        return true;
    } catch (_) { return false; }
}
async function main() {
    if (!BASE_URL || !chromium) return;
    if (args.preflight === '1') {
        const browser = await chromium.launch({ headless: true });
        await browser.close(); emit({ready:true}); return;
    }
    await require('./site_audit_crawl').crawl(module.exports, {
        base: BASE_URL, maxPages: MAX_PAGES, maxInternal: MAX_INTERNAL_LINKS,
        maxExternal: MAX_EXTERNAL_LINKS, timeout: PAGE_TIMEOUT_MS,
        inventoryFile: args.inventory || '', ignoreHTTPS: IGNORE_HTTPS_ERRORS,
    }, chromium);
}

module.exports = {
    emit, fetchSitemapUrls, fetchWithRedirects, inspectExternalLink, isPageCandidate, sameOrigin, publicRouteAllowed,
    canonicalTargetAllowed,
    internalTargetAllowed,
    normalizeBaseUrl,
    normalizeUrl,
};

if (require.main === module) {
    main().catch((error) => {
        emit({ fatal: error instanceof Error ? error.message : String(error) });
        process.exitCode = 1;
    });
}
