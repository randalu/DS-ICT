'use strict';
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');
const root = path.resolve(__dirname, '..');
process.env.PLAYWRIGHT_BROWSERS_PATH = path.join(root, 'output/browsers');
const { chromium } = require('playwright');
const AxeBuilder = require('@axe-core/playwright').default;
async function main() {
  const browser = await chromium.launch({ headless: true, channel: 'chromium' });
  const checks = [];
  const scans = [];
  const base = 'http://127.0.0.1:8100';
  const check = (value, message) => { assert.ok(value, message); checks.push(message); };
  try {
    const context = await browser.newContext({ reducedMotion: 'reduce' });
    context.on('request', (request) => {
      assert.equal(new URL(request.url()).origin, base);
      assert.equal(request.method(), 'GET');
    });
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    for (const route of ['/business-partners', '/business-partners/stays', '/business-partners/partner/demo-coastal-stay', '/business-partners/apply']) {
      for (const width of [1440, 390]) {
        await page.setViewportSize({ width, height: 900 });
        const response = await page.goto(base + route, { waitUntil: 'networkidle' });
        check(response.status() === 200, `Partner fixture route renders: ${route} ${width}px`);
        check(await page.locator('h1').count() === 1, 'One meaningful H1');
        check(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), 'No horizontal document overflow');
      }
      const result = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa']).analyze();
      scans.push({ route, violations: result.violations });
      check(result.violations.length === 0, `No detected accessibility violations: ${route}`);
    }
    await page.locator('#demo-interests').fill('Fictional partner application, for interface testing only.');
    await page.locator('[data-demo-form] button[type=submit]').click();
    check((await page.locator('[data-demo-status]').textContent()).includes('No business record or upload has been created'), 'Application stays local and creates no business/upload');
    check((await context.cookies()).length === 0 && errors.length === 0, 'No cookie or browser errors');
    fs.writeFileSync(path.join(root, 'output/partners-report.json'), JSON.stringify({ result: 'pass', assertions: checks.length, checks, accessibility: scans, note: 'Separate fixture-only test process; default preview and original partner feature remain unchanged.' }, null, 2) + '\n');
    console.log(`PASS: ${checks.length} conditional-partner assertions; ${scans.length} accessibility scans.`);
  } finally { await browser.close(); }
}
main().catch((error) => { console.error(error); process.exitCode = 1; });
