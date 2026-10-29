'use strict';
const path = require('node:path');
const fs = require('node:fs');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
const output = path.join(root, 'output');
process.env.PLAYWRIGHT_BROWSERS_PATH = path.join(output, 'browsers');
const { chromium } = require('playwright');
const AxeBuilder = require('@axe-core/playwright').default;
const base = process.env.NEW_SITE_TEST_URL || 'http://127.0.0.1:8099';
assert.match(base, /^http:\/\/127\.0\.0\.1:\d+$/);
fs.mkdirSync(output, { recursive: true });
const report = { base, checks: [], screenshots: [], accessibility: [], requests: [], errors: [] };
let checks = 0;
function check(condition, description) {
  assert.ok(condition, description);
  checks++;
  report.checks.push(description);
}
async function loadImages(page) {
  await page.locator('img').evaluateAll(async (images) => {
    for (const image of images) image.loading = 'eager';
    await Promise.all(images.map((image) => image.decode().catch(() => {})));
  });
  const height = await page.evaluate(() => document.documentElement.scrollHeight);
  for (let y = 0; y < height; y += 650) {
    await page.evaluate((top) => window.scrollTo(0, top), y);
    await page.waitForTimeout(65);
  }
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.waitForLoadState('networkidle');
  await page.evaluate(() => document.fonts.ready);
}
async function scan(page, label) {
  const result = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa']).analyze();
  report.accessibility.push({ label, violations: result.violations, incomplete: result.incomplete.map((item) => ({ id: item.id, impact: item.impact, nodes: item.nodes.length })), passedRules: result.passes.length });
  check(result.violations.length === 0, `${label}: zero automated WCAG A/AA violations`);
}
async function screenshot(page, name, fullPage = true) {
  await page.screenshot({ path: path.join(output, name), fullPage, animations: 'disabled' });
  report.screenshots.push(name);
}
async function main() {
  const browser = await chromium.launch({ headless: true, channel: 'chromium' });
  try {
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 }, reducedMotion: 'reduce' });
    context.on('request', (request) => {
      report.requests.push({ method: request.method(), url: request.url() });
      assert.equal(new URL(request.url()).origin, base, 'No external requests');
      assert.equal(request.method(), 'GET', 'Only GET browser requests');
    });
    const page = await context.newPage();
    page.on('pageerror', (error) => report.errors.push(error.message));
    page.on('console', (message) => { if (message.type() === 'error') report.errors.push(message.text()); });
    const response = await page.goto(base, { waitUntil: 'networkidle' });
    check(response.status() === 200, 'Homepage renders HTTP 200');
    check(response.headers()['x-robots-tag'].includes('noindex'), 'Preview has server noindex');
    check(!response.headers()['set-cookie'], 'Homepage emits no session/consent cookie');
    check((await page.locator('h1').allTextContents()).length === 1, 'Single homepage H1');
    check(await page.locator('body').getAttribute('data-content-mode') === 'fixture', 'Fixture content is identified explicitly');
    check(await page.locator('.preview-bar').innerText().then((text) => text.includes('Sample content')), 'Sample status visible at top');
    await loadImages(page);
    const broken = await page.locator('img').evaluateAll((images) => images.filter((img) => img.getClientRects().length && (!img.complete || !img.naturalWidth)).map((img) => img.src));
    check(broken.length === 0, `All visible images decode: ${broken.join(', ')}`);
    check(await page.locator('img').evaluateAll((images) => images.every((img) => img.hasAttribute('alt') && img.hasAttribute('width') && img.hasAttribute('height'))), 'All images declare alt and dimensions');
    const heroGeometry = async () => page.evaluate(() => {
      const hero = document.querySelector('.hero').getBoundingClientRect();
      const header = document.querySelector('.site-header').getBoundingClientRect();
      const copy = document.querySelector('.hero__copy').getBoundingClientRect();
      return { top: hero.top, left: hero.left, right: hero.right, width: innerWidth, headerBottom: header.bottom, copyTop: copy.top, background: getComputedStyle(document.querySelector('.site-header')).backgroundColor };
    });
    const desktopHero = await heroGeometry();
    check(Math.abs(desktopHero.top) < 1 && desktopHero.left === 0 && Math.abs(desktopHero.right - desktopHero.width) < 1, 'Homepage hero bleeds to the top and both viewport edges');
    check(desktopHero.background === 'rgba(0, 0, 0, 0)' && desktopHero.headerBottom > desktopHero.top, 'Desktop navigation is transparent over the hero image');
    check(desktopHero.copyTop > desktopHero.headerBottom, 'Hero content clears the overlaid desktop navigation');
    await screenshot(page, 'homepage-desktop.png');
    await screenshot(page, 'homepage-desktop-first-screen.png', false);
    await scan(page, 'Desktop homepage');
    const links = await page.locator('a[href]').evaluateAll((nodes) => [...new Set(nodes.map((node) => node.getAttribute('href')))].filter((href) => !href.startsWith('#')));
    for (const href of links) {
      check(href.startsWith('/') && !href.startsWith('//'), `Link stays local: ${href}`);
      const result = await context.request.get(base + href);
      check(result.status() === 200, `Linked milestone resolves: ${href}`);
    }
    await page.locator('[data-journey-select]').selectOption('1');
    check(await page.locator('[data-journey-panel="1"]').isVisible(), 'Journey selector updates visible route');
    check(await page.locator('[data-journey-panel]:visible').count() === 1, 'One journey panel visible');
    check(new URL(page.url()).searchParams.get('journey') === '1', 'Selected journey persisted in preview URL');
    await page.reload({ waitUntil: 'networkidle' });
    check(await page.locator('[data-journey-panel="1"]').isVisible(), 'Journey selection survives reload');
    await page.locator('[data-journey-select]').selectOption('0');
    const details = page.locator('.experience-item').nth(1);
    await details.locator('summary').click();
    check(await details.getAttribute('open') !== null, 'Native experience disclosure opens');
    check((await page.locator('[data-experience-caption]').textContent()) === await details.getAttribute('data-experience-title'), 'Experience selection updates image caption');
    await page.locator('[data-preview-open]').first().click();
    check(await page.locator('#preview-dialog').isVisible(), 'Preview information dialog opens');
    await scan(page, 'Preview dialog');
    await page.keyboard.press('Escape');
    check(!(await page.locator('#preview-dialog').isVisible()), 'Escape closes preview dialog');
    for (const width of [360, 390, 768, 1024, 1440, 1920]) {
      await page.setViewportSize({ width, height: 900 });
      const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
      check(!overflow, `No horizontal document overflow at ${width}px`);
    }
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(base, { waitUntil: 'networkidle' });
    await loadImages(page);
    const mobileHero = await heroGeometry();
    check(Math.abs(mobileHero.top) < 1 && mobileHero.background === 'rgba(0, 0, 0, 0)', 'Mobile hero also bleeds behind transparent navigation');
    check(mobileHero.copyTop > mobileHero.headerBottom, 'Mobile hero content clears the navigation');
    await screenshot(page, 'homepage-mobile.png');
    await screenshot(page, 'homepage-mobile-first-screen.png', false);
    const menuButton = page.getByRole('button', { name: 'Open navigation', exact: true });
    check(await menuButton.isVisible(), 'Mobile navigation button labelled');
    await menuButton.click();
    check(await page.locator('#mobile-menu').isVisible(), 'Mobile navigation opens');
    check(await page.locator('#mobile-menu').evaluate((node) => getComputedStyle(node).backgroundColor) === 'rgb(18, 63, 73)', 'Expanded mobile navigation retains its readable solid panel');
    check(await page.locator('#mobile-menu a').first().evaluate((node) => node === document.activeElement), 'Focus enters mobile navigation');
    await scan(page, 'Mobile navigation');
    await screenshot(page, 'mobile-navigation.png', false);
    await page.keyboard.press('Escape');
    check(!(await page.locator('#mobile-menu').isVisible()), 'Escape closes mobile navigation');
    check(await menuButton.evaluate((node) => node === document.activeElement), 'Navigation returns keyboard focus');
    await scan(page, 'Mobile homepage');
    check(await page.locator('#hero-title').evaluate((node) => getComputedStyle(node).animationName) === 'none', 'Reduced motion disables hero animation');
    await page.goto(base + '/contact', { waitUntil: 'networkidle' });
    check((await page.locator('main').innerText()).includes('Nothing is saved or sent'), 'Inquiry layout clearly identifies demo boundary');
    check(await page.locator('[data-demo-form]').count() === 1, 'Explicit local-only inquiry preview is available');
    await screenshot(page, 'contact-preview-mobile.png');
    await scan(page, 'Inquiry preview');
    const missing = await page.goto(base + '/unknown-destination', { waitUntil: 'networkidle' });
    check(missing.status() === 404, 'Unknown route returns HTTP 404');
    // An expected 404 is not a frontend console failure.
    report.errors = report.errors.filter((message) => !message.includes('404 (Not Found)'));
    check(await context.cookies().then((cookies) => cookies.length === 0), 'Preview leaves no browser cookies');
    await context.close();

    const noJs = await browser.newContext({ javaScriptEnabled: false, viewport: { width: 390, height: 844 }, reducedMotion: 'reduce' });
    const plain = await noJs.newPage();
    await plain.goto(base, { waitUntil: 'networkidle' });
    check(await plain.locator('#mobile-menu').isVisible(), 'Navigation remains available without JavaScript');
    await plain.locator('#journey-choice').selectOption('2');
    await Promise.all([plain.waitForURL('**/?journey=2#journey-ribbon'), plain.locator('.journey-picker button').click()]);
    check(await plain.locator('[data-journey-panel="2"]').isVisible(), 'Journey GET fallback works without JavaScript');
    await loadImages(plain);
    await screenshot(plain, 'homepage-no-javascript-mobile.png');
    await noJs.close();

    const motion = await browser.newContext({ reducedMotion: 'no-preference', viewport: { width: 1440, height: 1000 } });
    const animated = await motion.newPage();
    await animated.goto(base, { waitUntil: 'networkidle' });
    check(await animated.locator('#hero-title').evaluate((node) => getComputedStyle(node).animationName) === 'hero-enter', 'Purposeful hero motion enabled when appropriate');
    await motion.close();
    check(report.errors.length === 0, `No unexpected browser errors: ${report.errors.join('; ')}`);
    check(report.requests.every((request) => new URL(request.url).origin === base && request.method === 'GET'), 'All observed browser traffic remains local and read-only');
    report.assertions = checks;
    report.result = 'pass';
  } finally {
    await browser.close();
    fs.writeFileSync(path.join(output, 'browser-report.json'), JSON.stringify(report, null, 2) + '\n');
  }
  console.log(`PASS: ${checks} browser assertions; ${report.accessibility.length} accessibility scans; ${report.screenshots.length} screenshots.`);
}
main().catch((error) => { console.error(error); process.exitCode = 1; });
