'use strict';
const fs = require('node:fs');
const { snapshotDOM, analyzeDOM, placeholderKind, statusCertainty, pageKey, navigationRedirects, resourceFinding } = require('./site_audit_checks');

function robotsRules(body) {
    const groups = []; let agents = [], rules = [];
    function finish() { if (agents.length) groups.push({ agents, rules }); agents = []; rules = []; }
    for (const raw of String(body).split(/\r?\n/)) {
        const [key, ...rest] = raw.replace(/#.*$/, '').split(':'); const value = rest.join(':').trim();
        if (key.trim().toLowerCase() === 'user-agent') { if (rules.length) finish(); agents.push(value.toLowerCase()); }
        else if (agents.length && ['allow','disallow'].includes(key.trim().toLowerCase()) && value) rules.push({ allow: key.trim().toLowerCase() === 'allow', path: value });
    }
    finish(); const specific = groups.filter(g => g.agents.some(a => a !== '*' && 'ict-siteaudit'.includes(a)));
    return (specific.length ? specific : groups.filter(g => g.agents.includes('*'))).flatMap(g => g.rules);
}
function robotsAllowed(url, rules) {
    const path = new URL(url).pathname + new URL(url).search;
    const matched = rules.filter(r => new RegExp('^' + r.path.replace(/[.+?^${}()|[\]\\]/g, '\\$&').replace(/\*/g, '.*').replace(/\\\$$/, '$')).test(path));
    matched.sort((a,b) => b.path.length - a.path.length || Number(b.allow) - Number(a.allow));
    return !matched.length || matched[0].allow;
}

async function crawl(api, options, chromium) {
    const { base, maxPages, maxInternal, maxExternal, timeout, inventoryFile, ignoreHTTPS } = options;
    const emittedIssues = new Set(); let uncertain = false;
    const emit = event => {
        if (event.issue) {
            const row = event.issue, key = JSON.stringify([row.type,row.page_url,row.target_url || '',row.source_location || '']);
            if (emittedIssues.has(key)) return;
            emittedIssues.add(key);
            if (['inconclusive','not_checked'].includes(row.certainty)) uncertain = true;
        }
        api.emit(event);
    };
    const issue = (type, severity, page, detail = '', certainty = 'confirmed', target = '', location = '') => emit({ issue: { type, severity, page_url: page, detail: String(detail).slice(0,2000), certainty, target_url: target, source_location: location } });
    const observe = value => emit({ observation: value });
    const inventory = inventoryFile ? JSON.parse(fs.readFileSync(inventoryFile, 'utf8')) : { items: [], unpublished: [], notices: ['No database inventory supplied'] };
    const records = new Map(inventory.items.map(x => [pageKey(x.url,base), x]));
    const sitemap = await api.fetchSitemapUrls();
    for (const row of sitemap.issues) emit({ issue: { ...row, certainty: 'inconclusive' } });
    const sitemapSet = new Set(sitemap.urls.map(url => pageKey(url,base)));
    const queue = []; const queued = new Set(); const incoming = new Map(); const internal = new Map(); const external = new Map(); const anchors = []; const observations = new Map();
    let limited = false, pagesDone = 0;
    const coverage = { profile: 'public-v3-desktop1440-mobile390', max_pages: maxPages, max_internal: maxInternal, max_external: maxExternal, concurrency: 2, notices: [...inventory.notices], pages_checked: 0, internal_checked: 0, external_checked: 0, resources_checked: 0, resources_not_checked: 0, complete: false };
    emit({coverage});
    const add = value => {
        const normalized = api.normalizeUrl(value, base);
        if (!normalized || !api.publicRouteAllowed(normalized, base) || !api.isPageCandidate(normalized)) return;
        const url = pageKey(normalized,base);
        if (queued.has(url)) return;
        if (queue.length >= maxPages) { limited = true; return; }
        queued.add(url); queue.push(url);
    };
    [base + '/', ...sitemap.urls, ...records.keys()].forEach(add);
    const robotResult = await api.fetchWithRedirects(new URL('robots.txt', base + '/').href, { beforeRequest: u => api.internalTargetAllowed(u) });
    const rules = robotResult.status === 200 ? robotsRules(robotResult.body) : [];
    if (robotResult.error || [401,403,429].includes(robotResult.status) || robotResult.status >= 500) {
        issue('robots_unavailable','medium',base,'robots.txt restrictions could not be established','inconclusive');
        emit({coverage:{...coverage,notices:[...coverage.notices,'robots.txt unavailable; crawl stopped until restrictions can be verified']}}); emit({done:true,pages:0}); return;
    }
    const fetchInternal = target => api.fetchWithRedirects(target, { timeoutMs: 10000, beforeRequest: u => api.internalTargetAllowed(u) });
    const browser = await chromium.launch({ headless: true });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, ignoreHTTPSErrors: ignoreHTTPS, serviceWorkers: 'block', acceptDownloads: false });
    const checkedResources = new Map();
    async function resource(pageUrl, target, location) {
        let normalized = api.normalizeUrl(target,pageUrl); if (!normalized || normalized.startsWith('data:')) return;
        if (!checkedResources.has(normalized)) {
            if (checkedResources.size >= 10000) { limited = true; return; }
            checkedResources.set(normalized, (async () => {
                if (!api.publicRouteAllowed(normalized,base)) return { status: 0, excluded:true, error: 'External or excluded resource not checked' };
                if (!robotsAllowed(normalized,rules)) return { status: 0, excluded:true, error: 'Resource excluded by robots.txt' };
                return api.fetchWithRedirects(normalized,{ maxBodyBytes: 0, beforeRequest: u => api.internalTargetAllowed(u) });
            })());
        }
        const result = await checkedResources.get(normalized);
        if (result.excluded) { coverage.resources_not_checked++; uncertain = true; }
        else coverage.resources_checked++;
        observe({ kind: 'resource', page_url: pageUrl, target_url: normalized, location, status: result.status, checked:!result.excluded, error: result.error || null });
        if (result.error || result.status >= 400) {
            const finding = resourceFinding(result,result.excluded);
            issue(finding.type,finding.severity,pageUrl,result.error || `HTTP ${result.status}`,finding.certainty,normalized,location);
        }
    }
    async function inspect(url) {
        if (!robotsAllowed(url,rules)) { issue('robots_excluded','low',url,'Excluded by robots.txt','not_checked'); observe({ kind: 'page', page_url:url, status:0, complete:false, passed_checks:[] }); return; }
        const page = await context.newPage(); const errors = []; const failures = []; let status = 0;
        await page.route('**/*', async route => {
            const request = route.request();
            if (!['GET','HEAD'].includes(request.method()) || !api.publicRouteAllowed(request.url(),base) || !robotsAllowed(request.url(),rules) || !(await api.internalTargetAllowed(request.url()))) { return route.abort('blockedbyclient').catch(()=>{}); }
            return route.continue().catch(()=>{});
        });
        page.on('pageerror', error => errors.push(error.message.slice(0,300)));
        page.on('console', msg => { if (msg.type() === 'error' && !msg.text().includes('ERR_BLOCKED_BY_CLIENT')) errors.push(msg.text().slice(0,300)); });
        page.on('response', response => {
            const request = response.request(), kind = request.resourceType();
            if (['stylesheet','script','font','image','media'].includes(kind)) {
                observe({kind:'resource',page_url:url,target_url:response.url(),resource_type:kind,status:response.status()});
                const bytes = Number(response.headers()['content-length'] || 0);
                if (kind==='image' && bytes>1024*1024) issue('oversized_image','low',url,`${bytes} declared bytes: review delivery size`,'confirmed',response.url(),'network');
            }
            if (response.status() >= 400 && !request.isNavigationRequest()) failures.push({url:response.url(),status:response.status()});
        });
        page.on('requestfailed', request => { if (!request.failure()?.errorText.includes('ERR_BLOCKED_BY_CLIENT')) failures.push({url:request.url(),status:0}); });
        try {
            const response = await page.goto(url,{ waitUntil:'domcontentloaded',timeout }); status = response?.status() || 0;
            if (status !== 200) {
                issue('http_error','high',url,`HTTP ${status}`,statusCertainty(status));
                observe({kind:'page',page_url:url,status,complete:false,passed_checks:[]}); uncertain = true; return;
            }
            const redirects = navigationRedirects(response.request());
            if (redirects > 1) issue('redirect_chain','medium',url,redirects + ' redirects to ' + page.url(),'confirmed',page.url());
            // Trigger lazy images without clicking, submitting or invoking controls.
            await page.evaluate(async () => { const step = Math.max(400,innerHeight); for (let y=0; y<Math.min(document.documentElement.scrollHeight,30000); y+=step) { scrollTo(0,y); await new Promise(r=>setTimeout(r,20)); } scrollTo(0,0); });
            await page.waitForLoadState('load',{timeout:3000}).catch(()=>{});
            const desktop = await page.evaluate(snapshotDOM);
            await page.setViewportSize({width:390,height:844});
            await page.evaluate(() => new Promise(requestAnimationFrame));
            const mobile = await page.evaluate(snapshotDOM);
            const combined = { ...desktop, robots:desktop.robots + ',' + (response.headers()['x-robots-tag'] || ''), overflow:mobile.overflow, overflowElements:mobile.overflowElements };
            const result = analyzeDOM(combined,url,records.get(url));
            result.issues.forEach(row => {
                if (row.type === 'broken_image' && (!api.publicRouteAllowed(row.target_url,base) || !robotsAllowed(row.target_url,rules))) {
                    issue('resource_not_checked','low',url,'Image decode could not be verified because its resource was excluded','not_checked',row.target_url,row.source_location);
                } else emit({issue:row});
            });
            if (desktop.collectionCapped || mobile.collectionCapped) { limited = true; issue('collection_limit','low',url,'DOM collection reached 1000 elements','not_checked'); }
            if (mobile.overflow) observe({kind:'check',page_url:url,target_url:'viewport:390', viewport:{width:390,height:844}, elements:mobile.overflowElements, desktop_overflow:desktop.overflow});
            const passed = [...result.passed_checks,'http_error','console_errors','resource_failure','invalid_canonical','canonical_target_failure','missing_anchor','broken_internal_link','link_to_unpublished','page_absent_sitemap'];
            for (const err of errors.slice(0,3)) issue('console_errors','medium',url,err,'suspected');
            for (const failure of failures.slice(0,30)) {
                const finding = resourceFinding(failure);
                issue(finding.type,finding.severity,url,`HTTP ${failure.status || 'request failed'}`,finding.certainty,failure.url);
            }
            const record = records.get(url);
            if (record && !sitemapSet.has(url)) issue('page_absent_sitemap','medium',url,'Published database route absent from sitemap','suspected');
            for (const canonical of desktop.canonicals.slice(0,5)) {
                if (!api.canonicalTargetAllowed(canonical,url,base) || !api.publicRouteAllowed(canonical,base)) issue('invalid_canonical','high',url,'Canonical leaves configured public site','confirmed',canonical);
                else { const checked = await fetchInternal(canonical); if (checked.status !== 200 || checked.error) issue('canonical_target_failure','high',url,checked.error || `HTTP ${checked.status}`,statusCertainty(checked.status,checked.error),canonical); }
            }
            const assets = [...desktop.images,...mobile.images,...desktop.backgrounds,...mobile.backgrounds];
            for (const image of assets.slice(0,200)) {
                if (image.src) await resource(url,image.src,image.location);
                if (image.lazy) await resource(url,image.lazy,image.location + ':data-src');
                for (const part of (image.srcset || '').split(',').filter(Boolean).slice(0,8)) await resource(url,part.trim().split(/\s+/)[0],image.location + ':srcset');
                if (image.width > 3000 || image.height > 3000) issue('oversized_image','low',url,`${image.width} × ${image.height} intrinsic pixels: review delivery size`,'suspected',image.src,image.location);
            }
            if (assets.length > 200) { limited = true; coverage.notices.push('Media check capped at 200 elements per page'); }
            if (desktop.ogImage) await resource(url,desktop.ogImage,'og:image');
            for (const [field,target] of Object.entries({...record?.media,...record?.child_media})) if (target) {
                const assignedUrl = new URL(target,base + '/').href;
                await resource(url,assignedUrl,'database:' + field);
                const certainty = placeholderKind(target); if (certainty) issue('placeholder_image','medium',url,'Assigned media uses a placeholder',certainty,assignedUrl,'database:' + field);
            }
            for (const link of desktop.links) {
                let parsed; try { parsed = new URL(link.url); } catch (_) { continue; }
                if (!['http:','https:'].includes(parsed.protocol) || parsed.username || parsed.password) continue;
                const normalized = api.normalizeUrl(link.url,url);
                if (!normalized) continue;
                const target = pageKey(normalized,base);
                if (api.sameOrigin(target,base)) {
                    if (!api.publicRouteAllowed(target,base)) continue;
                    if (inventory.unpublished.includes(target)) issue('link_to_unpublished','high',url,'Link points to an unpublished content route','confirmed',target,link.location);
                    if (parsed.hash && anchors.length < maxInternal) anchors.push({source:url,target,hash:parsed.hash.slice(1),location:link.location});
                    if (!internal.has(target)) internal.set(target,new Set()); internal.get(target).add(url);
                    if (!incoming.has(target)) incoming.set(target,new Set()); incoming.get(target).add(url);
                    add(target);
                } else if (!external.has(target)) external.set(target,url);
            }
            observations.set(url,{kind:'page',page_url:url,status,complete:true,title:desktop.title,description:desktop.description,canonical:desktop.canonicals,robots:desktop.robots,ids:desktop.ids,passed_checks:passed,images:desktop.images.length,desktop:{width:1440,height:1000,overflow:desktop.overflow},mobile:{width:390,height:844,overflow:mobile.overflow},ttfb:desktop.ttfb});
        } catch (error) {
            uncertain = true; issue('page_load_fail','high',url,error.message,'inconclusive'); observe({kind:'page',page_url:url,status,complete:false,passed_checks:[]});
        } finally { await page.close(); }
    }
    try {
        // Dynamic queue grows through public links. Each batch runs at most two pages.
        for (let cursor=0; cursor<queue.length;) {
            emit({total_pages:queue.length});
            const batch = queue.slice(cursor,cursor+2); cursor += batch.length;
            await Promise.all(batch.map(async url => { await inspect(url); pagesDone++; emit({progress:pagesDone,url}); }));
        }
    } finally { await context.close(); await browser.close(); }
    for (const field of ['title','description']) {
        const groups = new Map();
        for (const [url,row] of observations) if (row[field]) { const key=row[field].trim().toLowerCase(); if (!groups.has(key)) groups.set(key,[]); groups.get(key).push(url); }
        for (const row of observations.values()) row.passed_checks.push('duplicate_' + field);
        for (const urls of groups.values()) if (urls.length>1) for (const url of urls) issue('duplicate_' + field,'medium',url,`${urls.length} pages share ${field}; example ${urls.find(x=>x!==url)}`,'confirmed');
    }
    for (const [url,row] of observations) {
        row.passed_checks.push('orphan_page','topic_overlap');
        if (records.has(url) && !incoming.get(url)?.size) issue('orphan_page','medium',url,'No incoming link found within crawled public pages','suspected');
        const words = new Set(row.title.toLowerCase().match(/[a-z]{4,}/g) || []);
        for (const [other,otherRow] of observations) {
            if (other<=url || !words.size) continue;
            const otherWords = new Set(otherRow.title.toLowerCase().match(/[a-z]{4,}/g) || []);
            const overlap = [...words].filter(x=>otherWords.has(x)).length / Math.max(words.size,otherWords.size);
            if (overlap>=0.8 && row.title!==otherRow.title) { issue('topic_overlap','low',url,'Similar page titles; review intent overlap','suspected',other); break; }
        }
        observe(row);
    }
    for (const [target,sources] of [...internal].slice(0,maxInternal)) {
        coverage.internal_checked++;
        if (!robotsAllowed(target,rules)) { for (const source of sources) issue('broken_internal_link','low',source,'Target excluded by robots.txt','not_checked',target); continue; }
        const result = await fetchInternal(target);
        if (result.error || result.status >= 400 || !result.status) for (const source of sources) issue('broken_internal_link','high',source,result.error || `HTTP ${result.status}`,statusCertainty(result.status,result.error),target);
        else if (result.redirects.length>1) for (const source of sources) issue('redirect_chain','medium',source,`${result.redirects.length} redirects`,'confirmed',target);
    }
    for (const anchor of anchors) {
        const row = observations.get(anchor.target);
        let hash; try { hash=decodeURIComponent(anchor.hash); } catch (_) { hash=anchor.hash; }
        if (row?.complete && !row.ids.includes(hash) && !hash.startsWith(':~:text=')) issue('missing_anchor','medium',anchor.source,'Target anchor is absent','confirmed',anchor.target + '#' + anchor.hash,anchor.location);
        else if (!row) issue('missing_anchor','low',anchor.source,'Target page was not successfully inspected','not_checked',anchor.target + '#' + anchor.hash,anchor.location);
    }
    for (const [target,source] of [...external].slice(0,maxExternal)) {
        coverage.external_checked++; const checked=await api.inspectExternalLink(target);
        if (!checked.result) { issue('external_link_failure','low',source,'External target could not be safely verified','inconclusive',target); continue; }
        const result=checked.result;
        if (result.error || result.status>=400 || !result.status) issue('external_link_failure','low',source,result.error || `HTTP ${result.status}`,statusCertainty(result.status,result.error),target);
    }
    if (internal.size>maxInternal || external.size>maxExternal || anchors.length>=maxInternal) limited=true;
    coverage.pages_checked=pagesDone; coverage.pages_discovered=queued.size; coverage.internal_discovered=internal.size; coverage.external_discovered=external.size;
    coverage.complete=!limited && !uncertain && !inventory.notices.length && !sitemap.issues.length;
    if (uncertain) coverage.notices.push('Some pages, links or resources were inconclusive or excluded; review their evidence before treating them as failures.');
    coverage.notices.push('Third-party browser resources are blocked; external links are separately sampled.','Measurements are lab observations, not real-user Core Web Vitals.');
    if (limited) { coverage.notices.push('One or more crawl/resource/link limits reached'); issue('coverage_limit','low',base,'Coverage is partial: configured limits reached','not_checked'); }
    emit({coverage}); emit({total_pages:queue.length,done:true,pages:pagesDone});
}
module.exports={crawl,robotsRules,robotsAllowed};
