'use strict';
const path = require('node:path');
const fs = require('node:fs');
const assert = require('node:assert/strict');
const { execFileSync } = require('node:child_process');
const root = path.resolve(__dirname, '..');
const output = path.join(root, 'output');
process.env.PLAYWRIGHT_BROWSERS_PATH = path.join(output, 'browsers');
const { chromium } = require('playwright');
const AxeBuilder = require('@axe-core/playwright').default;
const base = 'http://127.0.0.1:8099';
const registry = JSON.parse(execFileSync('php', ['tests/routes_export.php'], { cwd: root, encoding: 'utf8' }));
assert.equal(registry.mode, 'fixture');
const report = { result: 'incomplete', checks: [], routes: registry.routes, screenshots: [], accessibility: [], requests: [], errors: [] };
let assertions = 0;
function check(condition, message) { assert.ok(condition, message); assertions++; report.checks.push(message); }
async function loadImages(page) {
  await page.locator('img').evaluateAll(async (nodes) => {
    for (const image of nodes) image.loading = 'eager';
    await Promise.all(nodes.map((image) => image.decode().catch(() => {})));
  });
  const height = await page.evaluate(() => document.documentElement.scrollHeight);
  for (let y = 0; y < height; y += 750) {
    await page.evaluate((top) => window.scrollTo(0, top), y);
    await page.waitForTimeout(35);
  }
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.waitForLoadState('networkidle');
  await page.evaluate(() => document.fonts.ready);
  await page.evaluate(() => new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve))));
}
async function scan(page, label) {
  const results = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa']).analyze();
  report.accessibility.push({ label, violations: results.violations, incomplete: results.incomplete.map((item) => ({ id: item.id, nodes: item.nodes.length })), passedRules: results.passes.length });
  check(results.violations.length === 0, `${label}: no automated WCAG A/AA violations`);
}
async function main() {
  fs.mkdirSync(output, { recursive: true });
  const browser = await chromium.launch({ headless: true, channel: 'chromium' });
  try {
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, reducedMotion: 'reduce' });
    context.on('request', (request) => {
      const info = { method: request.method(), url: request.url() };
      report.requests.push(info);
      assert.equal(new URL(info.url).origin, base, 'No external browser request');
      assert.equal(info.method, 'GET', 'No submission during demos');
    });
    const page = await context.newPage();
    page.on('pageerror', (error) => report.errors.push(error.message));
    page.on('console', (message) => { if (message.type() === 'error' && !message.text().includes('404 (Not Found)')) report.errors.push(message.text()); });

    for (const entry of registry.routes) {
      const response = await context.request.get(base + entry.path);
      check(entry.status === 200 && response.status() === 200, `Registered route renders: ${entry.path} (${entry.kind})`);
      const html = await response.text();
      check((html.match(/<h1(?:\s|>)/g) || []).length === 1 && !html.includes('Preview rendering unavailable.'), `Complete single-H1 document: ${entry.path}`);
      check(!html.includes('A new chapter.<br>Not switched over.'), `No unfinished milestone placeholder: ${entry.path}`);
      check(!response.headers()['set-cookie'], `No account/consent cookie: ${entry.path}`);
    }
    for (const invalid of ['/itineraries/unknown-trip', '/61-day-itinerary', '/2-day-itinerary', '/account/logout', '/account/google/callback', '/account/plans/99', '/account/plans/1/policy-accept', '/business-partners', '/business-partners/apply']) {
      check((await context.request.get(base + invalid)).status() === 404, `Unknown/disabled/stateful route stays unavailable: ${invalid}`);
    }
    check((await context.request.get(base + '/DESTINATIONS/GALLE/')).status() === 200, 'Case-insensitive legacy path shape is preserved');
    const visitedLinks = new Set();
    const frames = [
      ['destinations', '/destinations'], ['destination-detail', '/destinations/galle'],
      ['itinerary-detail', '/itineraries/demo-hills-coast'], ['tour-detail', '/tours/demo-cultural'],
      ['day-tour-detail', '/day-tours/demo-galle'], ['experience-detail', '/experiences/demo-hill-country'],
      ['journal', '/blog'], ['article', '/blog/demo-coastal-chapter'],
      ['month-guide', '/sri-lanka-in-january'], ['contact', '/contact'],
      ['account-plan', '/account/plans/1'], ['account-messages', '/account/inquiries/1'],
      ['privacy', '/privacy_choices.php'], ['comparison', '/compare'], ['policy', '/booking-terms-and-conditions']
    ];
    for (const [label, route] of frames) {
      for (const width of [1440, 390]) {
        await page.setViewportSize({ width, height: width === 390 ? 844 : 1000 });
        const response = await page.goto(base + route, { waitUntil: 'networkidle' });
        check(response.status() === 200, `${label} ${width}px renders`);
        await loadImages(page);
        check(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1), `${label} no overflow at ${width}px`);
        if (['destinations', 'destination-detail', 'itinerary-detail', 'tour-detail', 'day-tour-detail', 'experience-detail', 'journal', 'article'].includes(label)) {
          const hero = await page.evaluate(() => {
            const opening = document.querySelector('.page-opening--bleed, .page-article-hero');
            if (!opening) return null;
            const header = document.querySelector('.site-header');
            const copy = document.querySelector('.page-opening--bleed .page-opening__copy, .page-article-hero .page-article-header');
            const box = opening.getBoundingClientRect();
            return {
              top: box.top,
              left: box.left,
              right: box.right,
              width: innerWidth,
              height: box.height,
              headerBackground: getComputedStyle(header).backgroundColor,
              headerBottom: header.getBoundingClientRect().bottom,
              copyTop: copy.getBoundingClientRect().top,
              breadcrumbs: document.querySelectorAll('nav[aria-label="Breadcrumb"]').length
            };
          });
          check(hero !== null, `${label}: image-led page uses the full-bleed hero`);
          check(Math.abs(hero.top) < 1 && hero.left === 0 && Math.abs(hero.right - hero.width) < 1, `${label} ${width}px: hero spans the full viewport from the top`);
          check(hero.height >= 560, `${label} ${width}px: hero retains a cinematic image area`);
          check(hero.headerBackground === 'rgba(0, 0, 0, 0)', `${label} ${width}px: navigation is transparent over the hero`);
          check(hero.copyTop > hero.headerBottom, `${label} ${width}px: hero copy clears the navigation`);
          check(hero.breadcrumbs === 1, `${label} ${width}px: exactly one breadcrumb trail inside the hero`);
        }
        const broken = await page.locator('img').evaluateAll((nodes) => nodes.filter((img) => img.getClientRects().length && (!img.complete || !img.naturalWidth || img.getBoundingClientRect().width < 1 || img.getBoundingClientRect().height < 1)).map((img) => img.src));
        check(broken.length === 0, `${label} ${width}px visible images decode`);
        const filename = `${label}-${width === 390 ? 'mobile' : 'desktop'}.png`;
        await page.screenshot({ path: path.join(output, filename), fullPage: true, animations: 'disabled' });
        report.screenshots.push(filename);
        if (width === 390 || ['destinations', 'itinerary-detail', 'article', 'contact'].includes(label)) await scan(page, `${label} ${width}px`);
      }
      const links = await page.locator('a[href]').evaluateAll((nodes) => [...new Set(nodes.map((node) => node.getAttribute('href')))].filter((href) => !href.startsWith('#')));
      for (const href of links) {
        check(href.startsWith('/') && !href.startsWith('//'), `${label}: internal link stays local`);
        if (visitedLinks.has(href)) continue;
        visitedLinks.add(href);
        check((await context.request.get(base + href)).status() === 200, `Linked route resolves: ${href}`);
      }
    }

    await page.goto(base + '/destinations?q=zzzz-no-matching-place', { waitUntil: 'networkidle' });
    check(await page.locator('.page-card').count() === 0, 'Destination zero-result state contains no fabricated matches');
    await page.goto(base + '/destinations', { waitUntil: 'networkidle' });
    const region = await page.locator('#filter-region option').evaluateAll((nodes) => nodes.find((node) => node.value !== '').value);
    await page.goto(base + '/destinations?region=' + encodeURIComponent(region), { waitUntil: 'networkidle' });
    check((await page.locator('#filter-region').inputValue()) === region, 'Destination filter state preserved');
    await page.goto(base + '/plan?d=10', { waitUntil: 'networkidle' });
    check((await page.locator('#planner-days').inputValue()) === '10', 'Planner keeps duration query');
    check(await page.locator('.travel-card').count() === 1, 'Planner duration filters sample journeys');
    await page.goto(base + '/compare', { waitUntil: 'networkidle' });
    await page.locator('[data-compare-id]').nth(0).check();
    await page.locator('[data-compare-id]').nth(1).check();
    await Promise.all([page.waitForURL('**/compare?ids=**'), page.locator('[data-compare-link]').click()]);
    check(await page.locator('.comparison-table thead th').count() === 3, 'Comparison shows exactly the two selected journeys');
    await page.goto(base + '/itineraries/demo-hills-coast', { waitUntil: 'networkidle' });
    check(await page.locator('.page-day-list').count() === 1 && (await page.locator('.page-day-list').innerText()).includes('Day 7'), 'Full seven-day illustrative sequence is present');
    await page.goto(base + '/contact', { waitUntil: 'networkidle' });
    const before = report.requests.length;
    await page.locator('#demo-interests').fill('A fictional planning inquiry for interface testing.');
    await page.locator('[data-demo-form] button[type=submit]').click();
    check(await page.locator('[data-demo-status]').isVisible(), 'Contact form provides local-only feedback');
    check((await page.locator('[data-demo-status]').textContent()).includes('Nothing has been saved or sent'), 'Inquiry feedback preserves no-submission boundary');
    check(report.requests.length === before, 'Inquiry demo makes no network request');
    await page.goto(base + '/account/inquiries/1', { waitUntil: 'networkidle' });
    const replyBefore = report.requests.length;
    await page.locator('#demo-interests').fill('A synthetic reply, not a real inquiry message.');
    await page.locator('[data-demo-form] button[type=submit]').click();
    check(await page.locator('[data-demo-status]').isVisible() && report.requests.length === replyBefore, 'Account reply remains local-only');
    await page.goto(base + '/account/plans/1', { waitUntil: 'networkidle' });
    check(await page.locator('.policy-panel fieldset').getAttribute('disabled') !== null && await page.locator('.policy-panel input').first().isDisabled() && await page.locator('.policy-panel button').isDisabled(), 'Unavailable policy snapshot cannot be accepted');
    await page.goto(base + '/privacy_choices.php', { waitUntil: 'networkidle' });
    const privacyBefore = report.requests.length;
    await page.locator('[data-preview-choice]').first().check();
    await page.locator('[data-privacy-apply]').click();
    check(await page.locator('[data-privacy-status]').isVisible() && report.requests.length === privacyBefore, 'Privacy preview changes no cookie/provider state');
    check((await context.cookies()).length === 0, 'All demo journeys leave no cookies');
    await page.goto(base + '/contact', { waitUntil: 'networkidle' });
    const supportHeader = await page.evaluate(() => {
      const header = document.querySelector('.site-header');
      return { position: getComputedStyle(header).position, background: getComputedStyle(header).backgroundColor };
    });
    check(supportHeader.position === 'relative' || supportHeader.position === 'static', 'Heading-only support pages keep the header in normal flow');
    check(supportHeader.background === 'rgb(18, 63, 73)', 'Heading-only support pages keep the solid readable header');
    await context.close();

    const noJs = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 390, height: 844 }, reducedMotion: 'reduce' });
    const plain = await noJs.newPage();
    await plain.goto(base + '/contact', { waitUntil: 'networkidle' });
    check(await plain.locator('[data-demo-fields]').getAttribute('disabled') !== null && await plain.locator('#demo-name').isDisabled(), 'No-JavaScript contact form fails closed');
    await plain.goto(base + '/destinations', { waitUntil: 'networkidle' });
    await plain.locator('#filter-q').fill('Galle');
    await Promise.all([plain.waitForURL('**/destinations?**'), plain.locator('.page-filters button[type=submit]').click()]);
    check(await plain.locator('.page-card').count() === 1, 'Core GET search works without JavaScript');
    await plain.goto(base + '/faq', { waitUntil: 'networkidle' });
    await plain.locator('.support-faqs summary').first().click();
    check(await plain.locator('.support-faqs details').first().getAttribute('open') !== null, 'FAQ disclosure works without JavaScript');
    await noJs.close();
    check(report.errors.length === 0, `No unexpected browser errors: ${report.errors.join('; ')}`);
    report.assertions = assertions;
    report.result = 'pass';
  } finally {
    await browser.close();
    fs.writeFileSync(path.join(output, 'rollout-report.json'), JSON.stringify(report, null, 2) + '\n');
  }
  console.log(`PASS: ${assertions} rollout assertions; ${registry.routes.length} routes; ${report.accessibility.length} accessibility scans; ${report.screenshots.length} screenshots.`);
}
main().catch((error) => { console.error(error); process.exitCode = 1; });
