const { chromium } = require('playwright');
const fs = require('node:fs/promises');
const path = require('node:path');

const root = path.resolve(__dirname, '..');
const output = path.join(root, 'output', 'image-alt-local-reconciliation');
const fixturePath = path.join(root, 'output', 'page-gallery-20261028', 'fixture.json');

async function main() {
    const fixture = JSON.parse(await fs.readFile(fixturePath, 'utf8'));
    if (!/^ict_page_media_test_[a-f0-9]{12}$/.test(fixture.database)) throw new Error('Disposable page-media schema required');
    const port = fixture.port || 8768;
    if (!Number.isInteger(port) || port < 1024 || port > 65535) throw new Error('Invalid local fixture port');
    const base = `http://127.0.0.1:${port}`;
    await fs.mkdir(output, { recursive: true });
    console.log('Opening the isolated local AI workflow in Chromium.');
    const browser = await chromium.launch({ channel: process.env.PLAYWRIGHT_CHANNEL || 'chrome', headless: true, timeout: 30000 });
    const context = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
    const page = await context.newPage();
    page.setDefaultTimeout(15000);
    page.setDefaultNavigationTimeout(30000);
    const errors = [];
    const paidRequests = [];
    let generationRequests = 0;
    let generationResolve;
    const generationStarted = new Promise(resolve => { generationResolve = resolve; });
    const responseText = `A rocky peak above green hills in Sri Lanka (${Date.now()})`;
    const report = { providerMocked: true, checks: [] };
    const check = (name, passed) => {
        if (!passed) throw new Error(name);
        report.checks.push(name);
    };
    page.on('pageerror', error => errors.push(error.message));

    // Never send a local test image or fixture credential to an external service.
    await context.route('**/*', async route => {
        const url = new URL(route.request().url());
        if (url.origin !== base) {
            if (url.hostname.endsWith('openai.com')) paidRequests.push(url.href);
            await route.abort();
        } else {
            await route.continue();
        }
    });
    await page.route('**/admin/image_alt_api.php*', async route => {
        if (route.request().method() === 'GET') {
            const response = await route.fetch();
            const data = await response.json();
            if (!response.ok()) throw new Error('Saved AI context did not load');
            check('Sri Lanka context is resolved on the server', data.context.country === 'Sri Lanka');
            check('AI schema is installed in the synthetic database', data.ready === true);
            data.configured = true;
            await route.fulfill({ response, json: data });
        } else {
            generationRequests++;
            generationResolve();
            await route.fulfill({ json: { alt_text: responseText } });
        }
    });

    try {
        await page.goto(`${base}/__fixture-admin`, { waitUntil: 'networkidle' });
        const form = page.locator('[data-pm-form][data-slot="hero_image"]');
        const input = form.locator('[name="description"]');
        const generate = form.getByRole('button', { name: 'Generate alt text', exact: true });
        await generate.waitFor({ state: 'visible' });
        await page.waitForFunction(() => {
            const button = document.querySelector('[data-pm-form][data-slot="hero_image"] [data-alt-generate]');
            return button && !button.disabled;
        });
        const before = await input.inputValue();
        await generate.click();
        await generationStarted;
        await form.getByRole('button', { name: 'Apply suggestion', exact: true }).waitFor();
        check('Generate leaves the current description unchanged', await input.inputValue() === before);
        await form.getByRole('button', { name: 'Apply suggestion', exact: true }).click();
        check('Apply places the suggestion in the editable description', await input.inputValue() === responseText);
        const savedBefore = await page.request.get(`${base}/admin/page_media_api.php?type=blog&id=101`);
        check('Apply does not save the description', (await savedBefore.json()).html.includes(`value="${before}"`));
        await Promise.all([
            page.waitForResponse(response => response.url().includes('page_media_api.php') && response.request().method() === 'GET' && response.ok()),
            form.getByRole('button', { name: 'Save image / description', exact: true }).click(),
        ]);
        await page.waitForFunction(expected => {
            const field = document.querySelector('[data-pm-form][data-slot="hero_image"] [name="description"]');
            return field && field.value === expected;
        }, responseText);
        check('Explicit Save persists the reviewed description', true);
        check('Generation has no automatic retry', generationRequests === 1);
        await page.waitForFunction(() => {
            const button = document.querySelector('[data-pm-form][data-slot="hero_image"] [data-alt-generate]');
            const reference = document.querySelector('[data-pm-form][data-slot="hero_image"] [data-alt-context]');
            return button && !button.disabled && reference && reference.textContent.includes('Sri Lanka');
        });
        await form.screenshot({ path: path.join(output, 'restored-alt-desktop.png') });

        const currentContext = await form.locator('[name="alt_context_revision"]').inputValue();
        await form.getByRole('button', { name: 'Choose from Media Library', exact: true }).click();
        await page.getByRole('dialog').getByRole('button', { name: 'Fixture landscape 2', exact: true }).click();
        await page.waitForFunction(previous => {
            const field = document.querySelector('[data-pm-form][data-slot="hero_image"] [name="alt_context_revision"]');
            return field && field.value && field.value !== previous;
        }, currentContext);
        check('Media Library selection refreshes AI references', true);
        await generate.click();
        await form.getByRole('button', { name: 'Apply suggestion', exact: true }).waitFor();
        check('Generation remains available after a library selection', generationRequests === 2);

        const libraryContext = await form.locator('[name="alt_context_revision"]').inputValue();
        await form.locator('[name="media"]').setInputFiles(path.join(root, 'assets', 'images', 'island_culture_travels_logo.png'));
        await page.waitForFunction(previous => {
            const field = document.querySelector('[data-pm-form][data-slot="hero_image"] [name="alt_context_revision"]');
            return field && field.value && field.value !== previous;
        }, libraryContext);
        check('Upload selection refreshes AI references', true);
        check('Changing the selected image discards the old preview', await form.locator('[data-alt-preview]').isHidden());
        await generate.click();
        await form.getByRole('button', { name: 'Apply suggestion', exact: true }).waitFor();
        check('Uploaded images can request a preview without saving', generationRequests === 3);

        const types = ['destination', 'itinerary', 'tour', 'day_tour', 'experience', 'activity', 'blog', 'page', 'blog_landing'];
        for (const type of types) {
            await page.goto(`${base}/admin/image_manager.php?type=${type}&id=${type === 'blog_landing' ? 1 : 101}`, { waitUntil: 'networkidle' });
            for (const width of [390, 1440]) {
                await page.setViewportSize({ width, height: 900 });
                const state = await page.locator('[data-page-media]').evaluate(panel => ({
                    forms: panel.querySelectorAll('[data-pm-form]').length,
                    generate: panel.querySelectorAll('[data-alt-generate]').length,
                    apply: panel.querySelectorAll('[data-alt-apply]').length,
                    overflow: panel.scrollWidth > panel.clientWidth + 1,
                    checkboxSize: [...panel.querySelectorAll('input[type="checkbox"]')].every(input => input.getBoundingClientRect().width < 30 && input.getBoundingClientRect().height < 30),
                }));
                check(`${type} keeps AI controls at ${width}px`, state.forms >= 9 && state.forms === state.generate && state.forms === state.apply && !state.overflow && state.checkboxSize);
            }
        }
        await page.goto(`${base}/admin/image_manager.php?type=blog&id=101`, { waitUntil: 'networkidle' });
        await page.setViewportSize({ width: 390, height: 844 });
        await form.scrollIntoViewIfNeeded();
        await page.screenshot({ path: path.join(output, 'restored-alt-mobile.png') });

        const routes = {
            destination: '/destinations/page-media-fixture-destination',
            itinerary: '/itineraries/page-media-fixture-itinerary',
            tour: '/tours/page-media-fixture-tour',
            day_tour: '/day-tours/page-media-fixture-day_tour',
            experience: '/experiences/page-media-fixture-experience',
            activity: '/activities/page-media-fixture-activity',
            blog: '/blog/page-media-fixture-blog',
            page: '/page-media-fixture-page',
            blog_landing: '/blog',
        };
        for (const [type, route] of Object.entries(routes)) {
            const response = await page.goto(`${base}${route}`, { waitUntil: 'networkidle' });
            check(`${type} public page still loads`, response.ok());
            for (const width of [320, 390, 768, 1024, 1440]) {
                await page.setViewportSize({ width, height: 900 });
                const state = await page.evaluate(() => {
                    const gallery = document.querySelector('[data-page-gallery]');
                    const hero = document.querySelector('.page-hero-banner,.itin-detail-hero,.journey-hero,.dt-detail-hero,.exp-detail-hero,.rd-hero,.activities-hero-detail,section.py-5.bg-light.border-bottom');
                    const cta = document.querySelector('.cta-section-custom,.dt-final-cta');
                    return {
                        oneHeading: document.querySelectorAll('h1').length === 1,
                        heroBeforeGallery: !!hero && !!gallery && !!(hero.compareDocumentPosition(gallery) & Node.DOCUMENT_POSITION_FOLLOWING),
                        galleryBeforeCta: !cta || !!gallery && !!(gallery.compareDocumentPosition(cta) & Node.DOCUMENT_POSITION_FOLLOWING),
                        overflow: document.documentElement.scrollWidth > innerWidth + 1,
                    };
                });
                check(`${type} keeps its hero, lower gallery and CTA order at ${width}px: ${JSON.stringify(state)}`, state.oneHeading && state.heroBeforeGallery && state.galleryBeforeCta && !state.overflow);
            }
        }
        check('There are no uncaught browser errors', errors.length === 0);
        check('No provider endpoint was contacted', paidRequests.length === 0);
        report.generationRequests = generationRequests;
        report.errors = errors;
        await fs.writeFile(path.join(output, 'browser-results.json'), JSON.stringify(report, null, 2));
        console.log(JSON.stringify({ passed: report.checks.length, providerMocked: true, generationRequests, errors }));
    } finally {
        await browser.close();
    }
}

main().catch(error => { console.error(error); process.exitCode = 1; });
