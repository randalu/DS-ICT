'use strict';

// This function runs in the page. Keep it self-contained and bound all collections.
function snapshotDOM() {
    const all = selector => [...document.querySelectorAll(selector)].slice(0, 1000);
    const text = value => (value || '').replace(/\s+/g, ' ').trim();
    const location = el => el.id ? '#' + el.id.slice(0, 100) : el.tagName.toLowerCase() + (el.classList.length ? '.' + [...el.classList].slice(0, 2).join('.') : '');
    const visible = el => {
        if (el.closest('[hidden],[inert],[aria-hidden="true"]')) return false;
        for (let node = el; node; node = node.parentElement) {
            const style = getComputedStyle(node);
            if (style.display === 'none' || ['hidden','collapse'].includes(style.visibility)) return false;
        }
        return el.getClientRects().length > 0;
    };
    const images = [...document.images].slice(0, 1000).map(el => ({ src: el.currentSrc || el.src, declared: el.getAttribute('src'), srcset: el.getAttribute('srcset') || '', lazy: el.dataset.src || '',
        complete: el.complete, width: el.naturalWidth, height: el.naturalHeight, alt: el.getAttribute('alt'), decorative: el.getAttribute('role') === 'presentation' || el.closest('[aria-hidden="true"]') !== null, location: location(el) }));
    const backgrounds = all('[style],.hero,.hero-bg,.hero-media').flatMap(el => [...getComputedStyle(el).backgroundImage.matchAll(/url\(["']?([^"')]+)["']?\)/g)].map(m => ({ src: new URL(m[1], document.baseURI).href, location: location(el) })));
    const main = document.querySelector('main') || document.body;
    return {
        title: text(document.title).slice(0, 500), description: document.querySelector('meta[name="description"]')?.content || '',
        h1s: all('h1').map(el => text(el.textContent)), headings: all('h1,h2,h3,h4,h5,h6').map(el => Number(el.tagName[1])),
        canonicals: all('link[rel="canonical"]').map(el => el.href), robots: document.querySelector('meta[name="robots"]')?.content || '',
        ogTitle: document.querySelector('meta[property="og:title"]')?.content || '', ogDescription: document.querySelector('meta[property="og:description"]')?.content || '',
        ogImage: document.querySelector('meta[property="og:image"]')?.content || '', twitterCard: document.querySelector('meta[name="twitter:card"]')?.content || '',
        schema: all('script[type="application/ld+json"]').map(el => (el.textContent || '').slice(0, 50000)),
        images, backgrounds, videos: all('video').map(el => ({ src: el.currentSrc || el.src, poster: el.poster, location: location(el) })),
        links: all('a[href]').map(el => ({ url: el.href, text: text(el.textContent).slice(0, 200), location: location(el) })),
        ids: all('[id]').map(el => el.id), text: text(main.innerText).slice(0, 10000),
        emptyButtons: all('button').filter(el => !text(el.textContent) && !el.getAttribute('aria-label') && !el.getAttribute('title')).map(location),
        unlabelledInputs: all('input:not([type="hidden"]):not([type="submit"]):not([type="button"]),textarea,select').filter(el => visible(el) && !el.labels?.length && !el.getAttribute('aria-label') && !el.getAttribute('aria-labelledby') && !el.getAttribute('title')).map(location),
        overflow: document.documentElement.scrollWidth > innerWidth + 2,
        overflowElements: all('body *').filter(el => { const r = el.getBoundingClientRect(); return r.width > 0 && r.right > innerWidth + 2 && getComputedStyle(el).position !== 'fixed'; }).slice(0, 8).map(location),
        ttfb: performance.getEntriesByType('navigation')[0]?.responseStart || 0,
        collectionCapped: document.querySelectorAll('a[href]').length > 1000 || document.images.length > 1000,
    };
}

const PLACEHOLDERS = ['destination','experience','itinerary','tour','country','blog'].map(x => '/assets/images/placeholders/placeholder-' + x + '.svg');
function placeholderKind(value) {
    let path; try { path = new URL(value, 'https://audit.invalid/').pathname; } catch (_) { return null; }
    if (PLACEHOLDERS.some(x => path.endsWith(x))) return 'confirmed';
    if (path.endsWith('/assets/images/og-default.jpg')) return 'suspected';
    return /(?:placeholders?[-_/]|dummy[-_/]|no[-_]image|coming[-_]soon)/i.test(path) ? 'suspected' : null;
}
function statusCertainty(status, error = null) {
    if (error || !status || [401,403,405,408,425,429].includes(status) || status >= 500) return 'inconclusive';
    return 'confirmed';
}

// Collapse only known aliases; preserve arbitrary trailing slashes and query values.
function pageKey(value, base) {
    const url = new URL(value, base + '/'), root = new URL(base);
    url.hash = '';
    if (!url.search) url.search = '';
    if (url.origin === root.origin && url.pathname === root.pathname.replace(/\/$/, '')) url.pathname += '/';
    return url.href;
}
function navigationRedirects(request) {
    let count = 0;
    while (request?.redirectedFrom()) { count++; request = request.redirectedFrom(); }
    return count;
}
function resourceFinding(result, excluded = false) {
    if (excluded) return { type:'resource_not_checked', severity:'low', certainty:'not_checked' };
    const certainty = statusCertainty(result.status, result.error);
    return { type:'resource_failure', severity:certainty === 'confirmed' ? 'high' : 'low', certainty };
}

function analyzeDOM(dom, url, record = null) {
    const issues = []; const passed = new Set();
    const add = (type, severity, detail = '', certainty = 'confirmed', target = '', location = '') => issues.push({ type, severity, page_url: url, detail, certainty, target_url: target, source_location: location });
    const check = (type, failed, severity, detail, certainty = 'confirmed', target = '', location = '') => { passed.add(type); if (failed) add(type,severity,detail,certainty,target,location); };
    check('missing_title', !dom.title.trim(), 'high', 'No document title');
    check('missing_meta_description', !dom.description.trim(), 'high', 'No meta description');
    check('short_meta_description', !!dom.description.trim() && dom.description.trim().length < 50, 'low', 'Short description: review usefulness', 'suspected');
    check('missing_h1', !dom.h1s.length, 'high', 'No H1');
    check('empty_h1', dom.h1s.some(x => !x), 'medium', 'Empty H1');
    check('multiple_h1', dom.h1s.length > 1, 'low', `${dom.h1s.length} H1s: review page structure`, 'suspected');
    check('heading_skip', dom.headings.some((n,i,a) => i && n > a[i-1] + 1), 'low', 'Heading levels skip a level', 'suspected');
    check('missing_canonical', !dom.canonicals.length, 'medium', 'No canonical link');
    check('conflicting_canonical', new Set(dom.canonicals).size > 1, 'high', 'Multiple conflicting canonical URLs');
    check('page_noindex', /\bnoindex\b/i.test(dom.robots), 'medium', 'Page declares noindex; review intended visibility', 'suspected');
    check('empty_content', dom.text.length < 80, 'medium', 'Very little visible main content', 'suspected');
    check('soft_404', /\b(page not found|404 not found|content not found)\b/i.test(dom.title + ' ' + dom.h1s.join(' ')), 'high', 'HTTP success with not-found wording', 'suspected');
    check('editorial_placeholder', /\{\{[^{}]{1,120}\}\}|\b(?:TODO|FIXME|TBD)\b/.test(dom.text) || !!record?.placeholder_tokens, 'medium', 'Unresolved editorial marker', 'suspected');
    check('social_metadata_missing', !dom.ogTitle || !dom.ogDescription || !dom.ogImage || !dom.twitterCard, 'low', 'Incomplete Open Graph/Twitter metadata');
    check('mobile_overflow', dom.overflow, 'medium', 'Horizontal overflow at 390px; elements: ' + dom.overflowElements.join(', '), 'confirmed', '', 'viewport:390x844');
    check('unlabelled_input', !!dom.unlabelledInputs.length, 'medium', dom.unlabelledInputs.join(', '));
    check('empty_button', !!dom.emptyButtons.length, 'medium', dom.emptyButtons.join(', '));
    check('slow_ttfb', dom.ttfb > 2000, 'low', `Observed response start ${Math.round(dom.ttfb)}ms; lab observation, not Core Web Vitals`, 'suspected');
    for (const issue of record?.field_issues || []) add(issue.type, 'medium', issue.detail, 'confirmed', '', 'database:' + issue.field);
    for (const type of ['images_missing_alt','broken_image','placeholder_image','missing_media_assignment','missing_video_poster','malformed_jsonld','schema_required_field','schema_url_mismatch']) passed.add(type);
    for (const image of dom.images) {
        if (!image.decorative && image.alt === null) add('images_missing_alt','medium','Image has no alt attribute','confirmed',image.src,image.location);
        if (image.complete && !image.width && image.src && !image.src.startsWith('data:')) add('broken_image','high','Image did not decode; inspect resource status','suspected',image.src,image.location);
    }
    for (const image of [...dom.images,...dom.backgrounds]) {
        const certainty = placeholderKind(image.src); if (certainty) add('placeholder_image','medium','Placeholder/fallback image is rendered',certainty,image.src,image.location);
    }
    for (const video of dom.videos) if (!video.poster) add('missing_video_poster','low','Video has no poster','confirmed',video.src,video.location);
    if (record) {
        const media = record.media;
        if ('hero_image' in media && !media.hero_image && !media.hero_video) add('missing_media_assignment','medium','Hero media assignment is empty; rendered fallback may hide it','confirmed','', 'database:hero_image');
        if (media.hero_video && 'hero_video_poster' in media && !media.hero_video_poster) add('missing_video_poster','low','Assigned video has no poster','confirmed','', 'database:hero_video_poster');
        for (const [location,path] of Object.entries(record.child_media || {})) if (!path) add('missing_media_assignment','medium','Child image assignment is empty; review whether an image is required here','suspected','', 'database:' + location);
    }
    const identities = new Map();
    passed.add('schema_identity_conflict');
    for (const raw of dom.schema) {
        let data; try { data = JSON.parse(raw); } catch (_) { add('malformed_jsonld','high','JSON-LD could not be parsed'); continue; }
        const queue = Array.isArray(data) ? [...data] : [data]; let nodes = 0;
        while (queue.length && nodes++ < 500) {
            const item = queue.shift(); if (!item || typeof item !== 'object') continue;
            if (Array.isArray(item)) { queue.push(...item); continue; }
            for (const value of Object.values(item)) if (typeof value === 'object') queue.push(value);
            const types = [].concat(item['@type'] || []);
            // A typed @id-only node is a graph reference, not a full entity.
            if (typeof item['@id'] === 'string' && Object.keys(item).every(key => ['@id','@type','@context'].includes(key))) continue;
            if (typeof item['@id'] === 'string' && typeof item.name === 'string') {
                const known = identities.get(item['@id']);
                if (known && known !== item.name) add('schema_identity_conflict','medium','A schema identity has conflicting names','suspected',item['@id']);
                identities.set(item['@id'],item.name);
            }
            const required = types.includes('Article') || types.includes('BlogPosting') ? ['headline'] : types.includes('Organization') || types.includes('LocalBusiness') ? ['name','url'] : types.includes('WebPage') ? ['name'] : [];
            const missing = required.filter(key => !item[key]); if (missing.length) add('schema_required_field','medium', `${types.join(',')}: missing ${missing.join(',')}`);
            if (types.includes('WebPage') && typeof item.url === 'string') {
                try { if (new URL(item.url,url).origin !== new URL(url).origin) add('schema_url_mismatch','medium','WebPage URL differs from site origin','suspected',item.url); }
                catch (_) { add('schema_url_mismatch','medium','WebPage has an invalid URL'); }
            }
        }
    }
    return { issues, passed_checks: [...passed] };
}
module.exports = { snapshotDOM, analyzeDOM, placeholderKind, statusCertainty, pageKey, navigationRedirects, resourceFinding, PLACEHOLDERS };
