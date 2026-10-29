'use strict';
const fs = require('node:fs');
const path = require('node:path');
const { spawnSync } = require('node:child_process');
const root = path.resolve(__dirname, '..');
const output = path.join(root, 'output');
const checks = [];
for (const args of [['tests/lint.php'], ['tests/runtime.php', 'http://127.0.0.1:8099'], ['tests/pages.php'], ['tests/repository.php'], ['tests/apache.php'], ['tests/preservation.php']]) {
  const result = spawnSync('php', args, { cwd: root, encoding: 'utf8' });
  const record = { command: `php ${args.join(' ')}`, exitCode: result.status, stdout: result.stdout.trim(), stderr: result.stderr.trim() };
  checks.push(record);
  console.log(record.stdout || record.stderr);
  if (result.status !== 0) process.exit(1);
}
const browser = JSON.parse(fs.readFileSync(path.join(output, 'browser-report.json'), 'utf8'));
const design = JSON.parse(fs.readFileSync(path.join(output, 'design-checks.json'), 'utf8'));
const rollout = JSON.parse(fs.readFileSync(path.join(output, 'rollout-report.json'), 'utf8'));
const partners = JSON.parse(fs.readFileSync(path.join(output, 'partners-report.json'), 'utf8'));
if ([browser, design, rollout, partners].some((result) => result.result !== 'pass')) throw new Error('All recorded browser/design validation must pass before handoff.');
const report = {
  milestone: 'Approved visual direction expanded across core and supporting frontend page families',
  generatedAt: new Date().toISOString(),
  preview: 'http://127.0.0.1:8099',
  scope: 'NEW-SITE only; original application files unchanged',
  contentMode: 'Clearly labelled synthetic design samples with approved existing public photography',
  databaseConnected: false,
  pageFamilyLayoutsBuilt: true,
  productionReady: false,
  publishedInventoryParityVerified: false,
  concretePreviewRoutes: rollout.routes.length,
  conditionalPartnerRoutes: 4,
  nextGate: 'Separately approved SELECT-only source access, actual published-record/media/content parity verification, and isolated backend workflow integration. No deployment.',
  originalSourceChecks: checks,
  browserAssertions: browser.assertions + rollout.assertions + partners.assertions,
  accessibilityScans: [...browser.accessibility, ...rollout.accessibility, ...partners.accessibility.map((scan) => ({ label: scan.route, violations: scan.violations, incomplete: [] }))].map((scan) => ({ label: scan.label, detectedViolations: scan.violations.length, manualReviewItems: scan.incomplete })),
  performanceBudgets: design.assets,
  screenshots: [...browser.screenshots, ...rollout.screenshots],
  contentAdapter: {
    implementation: 'app/repository.php',
    configuration: ['NEW_SITE_DB_DSN', 'NEW_SITE_DB_USER', 'NEW_SITE_DB_PASSWORD'],
    access: 'Loopback MySQL only, verified SELECT/USAGE-only grants, read-only transaction and rollback, no original config imports',
    limits: 'Over 100 records per mapped family, 2000 itinerary days, oversized/malformed source fields or unsupported source shapes fail closed; no silently clipped catalogue is returned',
    mapped: ['destinations', 'itineraries', 'tours', 'day_tours', 'experiences', 'blog_posts', 'itinerary_days'],
    excluded: ['private customer/account/inquiry data', 'settings', 'approved policy content', 'reviews', 'original relationship/gallery tables', 'country/seasonal/editorial CMS records']
  },
  limitations: [
    'No approved database-enforced SELECT-only credentials have been provisioned or used. The optional adapter is implemented and fake-tested, not live-verified. The default preview uses labelled samples.',
    'The 96 default and four optional partner routes are concrete preview layouts, not a full enumeration of current CMS URLs or proof that every original content block and relationship is mapped.',
    'Policies and genuine reviews show honest unavailable/empty states rather than fabricated text or testimonials. Account records are synthetic. Service/guide content is illustrative, not a source-content migration.',
    'Original uploads are not accessed. Real media outside the explicit public-image allowlist stays unavailable; source media inventory and rights/permission verification remain pending.',
    'Demo forms and privacy choices operate only in memory; no real forms, accounts, OAuth, AI, emails, review widgets, analytics or business-data writes run.',
    'Automated accessibility scans supplement manual screenshot and keyboard checks; image-background contrast items still need specialist/manual review. No WCAG conformance certification is claimed.',
    'Chromium only was exercised; no Firefox/WebKit, screen-reader, field Core Web Vitals or production testing.',
    'The NEW-SITE-only Apache deny rule returned 403 on four scoped HEAD requests at the local default Laragon mount. Other virtual hosts/hosting configurations were not tested.',
    'The original test suite was intentionally not run because its bootstrap/schema/session side effects would violate isolation.'
  ]
};
fs.writeFileSync(path.join(output, 'milestone-report.json'), JSON.stringify(report, null, 2) + '\n');
console.log(`Saved rollout report: ${report.browserAssertions} browser assertions; ${report.concretePreviewRoutes} default routes; ${report.accessibilityScans.length} accessibility scans.`);
