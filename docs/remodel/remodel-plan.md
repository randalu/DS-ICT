# Island Culture Travels — Full Site Remodelling Plan

**Confirmed direction:** in-place PHP 8.3 revamp, coastal editorial design, public frontend first and admin refresh second. No framework migration, no URL changes, no database replacement.

---

## 1. Objective and success criteria

Replace the current mixed Bootstrap/legacy page families with one original design system and one server-rendered template layer, while keeping the existing database, URLs, SEO signals, auth, admin and third-party integrations intact.

**Measured success criteria**

| Area | Target |
|---|---|
| Route parity | Every canonical route in [.htaccess](../../.htaccess) returns 200 with new templates; unknown routes still return 404 |
| SEO | Title, description, canonical, robots, Open Graph, Twitter and JSON-LD parity on every route; sitemap URLs unchanged; no new redirects |
| Lighthouse (mobile) | Performance ≥90 on home and one detail template; Accessibility 100; Best Practices ≥95; SEO 100 |
| Core Web Vitals | LCP ≤2.5s on 4G, CLS ≤0.05, INP ≤200ms |
| Page weight | Public CSS ≤90KB uncompressed, public JS ≤40KB gzip per page |
| Accessibility | axe-core 0 critical/serious issues on every template at 390px and 1440px; WCAG 2.2 AA |
| Forms | CSRF, honeypot, reCAPTCHA, rate limiting and server validation preserved; inquiry/account tests pass |
| Admin | Every nav GET returns 200; CRUD round-trip passes; auth and CSRF behaviour unchanged |
| Content gates | 0 placeholder heroes on published records; ≥10 verified reviews; ≥15 FAQ entries; legal policies published; partners populated or directory disabled |
| Stability | No PHP warnings/notices in logs during full route smoke |

---

## 2. What exists today (verified)

- PHP 8.3, Apache + `.htaccess`, MySQL database `ict` with **109 tables**; **59 root PHP pages**, **159 admin files**, roughly **60 PHPUnit tests**.
- Public pages mix at least five visual systems: `new-home-*`, `dest-*`/`rd-*`, `exp-*`, `itin-*`/`dt-*`, and generic Bootstrap `page-hero-banner` pages.
- Global CSS is heavy: [style.css](../../assets/css/style.css) ~194KB, [mobile-redesign.css](../../assets/css/mobile-redesign.css) ~43KB, plus page sheets. Bootstrap 5.3.3 and Alpine load on every public page via [includes/header.php](../../includes/header.php).
- Shared chrome lives in [includes/header.php](../../includes/header.php) and [includes/footer.php](../../includes/footer.php); SEO is emitted by [includes/seo.php](../../includes/seo.php); schema is emitted by `schema_output($schemaGraph)`.
- Content inventory: **43 destinations** (31 with real heroes, 12 placeholders; 35 missing `region_slug`, 42 missing best-time data), **13 itineraries** / 127 days, **8 tours** (7 published), **10 day tours**, **26 experiences**, **21 blog posts**, **20 country pages**, **18 static pages** (5 legal/policy pages unpublished), **1 test review**, **0 FAQ rows**, **0 business partners** though `business_partners_public_enabled=1`, **41 media rows** but `assets/uploads/` is empty locally.
- [NEW-SITE/](../../NEW-SITE/templates/layout.php) is a deliberately blocked, read-only design preview. It is useful as a visual reference but is not deployable.
- The D: dump `D:\www\IslandCulture-main\database\sril_site.sql` contains **70 tables**, not the 109 in `ict`. It is a partial snapshot and must not be imported over the live schema.
- This workspace has **no `.git` directory**; the remote is `github.com/randalu/IslandCulture`.
- An API key is currently stored in `site_settings`; it must be removed from the database and rotated.

---

## 3. Decisions

| Decision | Choice | Reason |
|---|---|---|
| Rebuild style | In-place PHP revamp | Keeps data, URLs, SEO, auth and admin; lowest risk |
| Design language | Coastal editorial | Evolves the current Fraunces/Manrope brand; premium and differentiated |
| Delivery order | Public first, admin second | Admin keeps operating while the public site changes |
| Rendering | Server-rendered PHP | SEO-first, no SPA/API layer required |
| Template engine | Plain PHP templates | No new dependency, no build step |
| CSS | Cascade layers, namespaced `--ic-*` tokens | Allows old and new pages to coexist safely |
| JS | ES modules, progressive enhancement | Removes jQuery/Alpine from public pages |
| Bootstrap | Removed from public; retained in admin | Cuts ~230KB and avoids template-look |
| References | Patterns only, original code | Avoids shipping third-party template assets/licensing |
| Database | Additive, guarded migrations | No destructive changes; rollback-safe |

---

## 4. Target architecture

### 4.1 New structure

```text
app/
  bootstrap.php              # manual PSR-4 autoloader + app bootstrapping
  Autoload.php
  Pages/                     # one class per public route
    HomePage.php
    DestinationIndexPage.php
    DestinationPage.php
    ExperienceIndexPage.php
    ExperiencePage.php
    JourneyIndexPage.php
    JourneyPage.php
    DayTourIndexPage.php
    DayTourPage.php
    ActivityIndexPage.php
    ActivityPage.php
    ArticleIndexPage.php
    ArticlePage.php
    GuidePage.php
    ReviewsPage.php
    SupportPage.php
    AccountPage.php
  Content/                   # read repositories, prepared statements only
    DestinationRepository.php
    ExperienceRepository.php
    TourRepository.php
    ItineraryRepository.php
    DayTourRepository.php
    ActivityRepository.php
    ArticleRepository.php
    CountryRepository.php
    SeasonalRepository.php
    ReviewRepository.php
    PartnerRepository.php
    MediaRepository.php
    SettingsRepository.php
  View/
    Page.php                 # page model: meta, schema, breadcrumbs, template, data
    Renderer.php             # render_page(), view(), component()
  Support/
    ContentNormalizer.php    # stable card/detail shapes
    MediaResolver.php        # path, alt, srcset, placeholder handling
    PriceFormatter.php
    SchemaBuilder.php
    ReadingTime.php

views/
  layouts/public.php
  partials/
    head.php seo.php header.php footer.php scripts.php
    breadcrumbs.php hero.php section-heading.php
    card-destination.php card-experience.php card-journey.php
    card-article.php card-review.php card-partner.php
    filter-bar.php pagination.php gallery.php timeline.php
    map.php faq.php toc.php trust-strip.php cta-band.php
    inquiry-form.php sticky-cta.php empty-state.php
  pages/
    home.php
    destinations/index.php destinations/show.php
    experiences/index.php experiences/show.php
    journeys/tours.php journeys/tour.php
    journeys/itineraries.php journeys/itinerary.php
    journeys/day-tours.php journeys/day-tour.php
    activities/index.php activities/show.php
    articles/index.php articles/show.php
    guides/index.php guides/month.php guides/country.php guides/theme.php
    reviews.php support/about.php support/why.php support/faq.php
    support/contact.php support/plan.php support/compare.php support/search.php
    account/*.php partners/index.php partners/show.php partners/apply.php
    legal/page.php errors/404.php

assets/css/site/
  tokens.css base.css layout.css components.css pages.css
assets/js/site/
  nav.js filters.js carousel.js gallery.js wizard.js map.js reveal.js analytics.js
assets/icons/sprite.svg

tests/Remodel/               # PHPUnit tests for app + views
cli/smoke_public_routes.php  # full route smoke + metadata assertions
docs/remodel/                # inventory, content readiness, design notes
```

### 4.2 Request flow

```text
root page (e.g. destination.php)
  → app/bootstrap.php
  → ICT\Pages\DestinationPage::run()
  → repositories (prepared statements, request-local memoisation)
  → ICT\View\Page model
  → render_page()
  → views/layouts/public.php + partials + page view
  → schema_output($schemaGraph)
```

- Root filenames and `.htaccess` stay unchanged.
- `includes/seo.php` is included by the new head partial so canonical, OG and robots behaviour remains identical.
- `schema_output()` remains the single schema emitter.
- Legacy pages keep the old header/footer until their family is migrated. Both systems can coexist because new CSS/JS is scoped under `body.ic-v2` and `--ic-*` tokens.

### 4.3 Data layer

- One repository per content family; no SQL in views.
- Normalizers return stable shapes for cards and detail pages: `title`, `slug`, `url`, `excerpt`, `image`, `alt`, `price`, `duration`, `region`, `badges`, `related`.
- Existing `find_published_*` helpers remain as fallbacks during migration and are retired at the end.
- Repositories accept an injectable PDO connection; `MOCK_DB=1` keeps working for tests.
- Query failures log and return empty; the page layer decides 404 vs 500.
- No persistent object cache initially; add only if measured.

### 4.4 Asset strategy

- Five CSS files loaded in order; no build step.
- No Bootstrap, MDI icon font or Alpine on new public pages.
- ES modules with `<script type="module">`; all content and filters work without JS.
- Inline SVG sprite for icons.
- Extend existing responsive-image pipeline to AVIF/WebP, width/height, `fetchpriority="high"` on hero, lazy loading below the fold.

---

## 5. Design system — “Island Coast”

### 5.1 Art direction

> Your Sri Lanka, unhurried.

Editorial travel journal meets private concierge: full-bleed photography, warm ivory paper, deep lagoon ink, sun-amber accents, generous whitespace, serif headlines, humanist sans body text.

**Borrow deliberately**

- Booklanka: discovery rails, destination grids, clear price/duration facts, sticky filters.
- Tripadvisor: rating summary, review provenance, photo reviews, traveller-type labels.
- Figma “Botique” wireframes: editorial grid, horizontal rails, asymmetric imagery.
- Tourist/htmlcodex: only the utility bar, hero form and service-card structure.

**Reject**

- Template Bootstrap look, loading spinners, icon clutter.
- Marketplace cart/checkout density.
- Ad-heavy Tripadvisor layout.
- Copied template HTML/CSS or jQuery plugins.

### 5.2 Design tokens

| Token | Value | Use |
|---|---|---|
| `--ic-ink` | `#12231D` | Primary text, dark sections |
| `--ic-lagoon` | `#123F49` | Hero overlays, footer, dark bands |
| `--ic-palm` | `#2C6B57` | Primary green, links, active states |
| `--ic-sun` | `#E6B45A` | Primary CTA, highlights |
| `--ic-clay` | `#A9604A` | Secondary accent, editorial marks |
| `--ic-sand` | `#F4ECDD` | Warm section backgrounds |
| `--ic-ivory` | `#FBFAF5` | Page background |
| `--ic-mist` | `#E6EFEA` | Alternate panels |
| `--ic-line` | `rgba(18,35,29,.12)` | Borders |
| `--ic-muted` | `#5C6B64` | Secondary text |
| `--ic-shadow-sm` | `0 2px 8px rgba(18,35,29,.06)` | Cards |
| `--ic-shadow-md` | `0 16px 40px rgba(18,35,29,.10)` | Overlays |
| `--ic-radius-sm` | `2px` | Buttons, inputs |
| `--ic-radius-md` | `6px` | Cards, panels |
| `--ic-radius-pill` | `999px` | Tags only |

**Typography:** keep self-hosted Fraunces (display, 500/600) and Manrope (body, 400–700), already loaded through [typography.css](../../assets/css/typography.css).

- H1: `clamp(2.75rem, 6vw, 5.5rem)` / 1.02 / `-0.045em`
- H2: `clamp(2rem, 4vw, 3.5rem)` / 1.08
- H3: `clamp(1.25rem, 2vw, 1.75rem)` / 1.2
- Body: `1rem` / 1.7; lead `1.25rem` / 1.65
- Eyebrow: `.6875rem`, uppercase, `.18em` tracking
- Measure: 68ch max for prose

**Layout:** max width 1280px, gutter `clamp(20px, 4.5vw, 72px)`, 12-column grid, section spacing 80/112/144px, breakpoints 480/768/1024/1280.

**Motion:** 160ms micro, 240ms standard, 400ms reveal; `cubic-bezier(.2,.6,.2,1)`; all animation disabled under `prefers-reduced-motion`.

### 5.3 Component inventory

1. Utility bar (phone, email, language, WhatsApp)
2. Header with desktop mega menu and mobile drawer
3. Hero variants: home, collection, detail
4. Breadcrumbs
5. Buttons: sun primary, lagoon primary, outline, text-arrow
6. Cards: destination, experience, journey, article, review, partner
7. Filter bar: chips, select, sort, result count, clear
8. Horizontal rails with scroll-snap and arrow controls
9. Gallery with dialog lightbox
10. Day-by-day timeline with stays
11. Route map and destination map
12. “At a glance” facts panel
13. Best-time month strip
14. FAQ accordion
15. Table of contents
16. Trust strip and review summary
17. Multi-step inquiry wizard
18. Sticky CTA / WhatsApp float
19. Pagination and load-more
20. Footer with newsletter and trust widgets
21. Empty, 404 and unavailable states

Every component includes hover, focus-visible, disabled, loading and reduced-motion states.

### 5.4 Navigation model

Keep all existing URLs. New primary navigation:

- **Destinations** — All destinations, regions
- **Experiences** — All experiences, categories, Activities
- **Journeys** — Tours, Itineraries, Day tours
- **Travel Guide** — Journal, When to visit, From abroad, FAQ
- **About** — Our story, Why travel with us, Reviews, Business partners
- **Plan my trip** CTA + phone/WhatsApp actions

Mobile: full-height drawer with grouped links, contact actions and a single primary CTA.

Footer: brand/tagline, four link columns, trust widgets, newsletter, social links, legal links. Preserve all current footer links unless a URL is retired.

---

## 6. Page blueprints

| Route | Template | Above the fold | Core sections | Primary action |
|---|---|---|---|---|
| `/` | `pages/home.php` | Hero image/video, H1, two CTAs, three trust facts | Journey finder; intro; signature journeys; destinations; experiences; day tours; how it works; reviews/trust; journal; final CTA | Plan my trip |
| `/destinations` | `pages/destinations/index.php` | Title, count, region chips | Featured destinations; map; all destinations; seasonal callout | Explore a destination |
| `/destinations/{slug}` | `pages/destinations/show.php` | Hero, region, recommended stay, CTA | At a glance; story; experiences here; journeys including it; gallery; getting there; related | Plan this stop |
| `/experiences` | `pages/experiences/index.php` | Title, mood tiles | Filter bar; featured; grid; CTA | Find journeys |
| `/experiences/{slug}` | `pages/experiences/show.php` | Hero | Story; day-in-the-life; where to experience; journeys; gallery; related | Add to my trip |
| `/tours` | `pages/journeys/tours.php` | Title, style filters | Featured; grid; compare; CTA | View tour |
| `/tours/{slug}` | `pages/journeys/tour.php` | Hero, facts, sticky price card | Overview; coverage map; programme; inclusions; FAQ; related | Request a quote |
| `/itineraries` | `pages/journeys/itineraries.php` | Title, duration/traveller filters | Featured; grid; themes; CTA | View itinerary |
| `/itineraries/{slug}` | `pages/journeys/itinerary.php` | Hero, facts | Route map; day timeline with stays; inclusions; best time; related | Customise this trip |
| `/day-tours` | `pages/journeys/day-tours.php` | Title, duration filters | Grid; map; CTA | Enquire |
| `/day-tours/{slug}` | `pages/journeys/day-tour.php` | Hero, duration/price | Highlights; itinerary; meeting point; inclusions; FAQ | Check availability |
| `/activities`, `/activities/{slug}` | `pages/activities/*` | Title / hero | Filters; grid; detail story; related | Plan around this |
| `/blog`, `/blog/{slug}` | `pages/articles/*` | Title / hero | Featured; categories; grid; article with TOC, share, related | Read next |
| `/seasonal`, `/sri-lanka-in-{month}` | `pages/guides/month.php` | Title, month strip | Weather, crowds, costs, what to do, sample journeys | Plan for this month |
| `/from`, `/from/{slug}` | `pages/guides/country.php` | Title, country hero | Flights, visa, jet lag, suggested routes, FAQ | Get a plan |
| `/{theme}-tours` | `pages/guides/theme.php` | Hero | Theme story; matching journeys; FAQ | See journeys |
| `/reviews` | `pages/reviews.php` | Rating summary and source badges | Filters; review cards; photos; CTA | Read / plan |
| `/about-us`, `/why-choose-us`, `/faq` | `pages/support/*` | Editorial hero | Story, values, team, proof; FAQ with schema | Plan my trip |
| `/contact`, `/plan`, `/compare`, `/ai-search` | `pages/support/*` | Split hero/ form | Wizard; contact details; response promise; trust | Send inquiry |
| `/account*` | `pages/account/*` | New shell, same logic | Account, plans, inquiries, customiser | Manage trip |
| `/business-partners*` | `pages/partners/*` | Directory / application | Filters, partner cards, application form | Apply / connect |
| `/{slug}` policies, 404 | `pages/legal/page.php`, `errors/404.php` | Legal hero / 404 | TOC, policy body, contact | Read / recover |

**Home section detail**

1. Utility bar
2. Header
3. Full-bleed hero with video/image, eyebrow, H1, subcopy, “Plan my trip” + “Explore journeys”, trust facts
4. “Where would you like to go?” finder ribbon (destination, days, interest)
5. Editorial introduction
6. Signature journeys (three cards)
7. Destinations (asymmetric grid + region filters)
8. Experiences rail
9. Day tours
10. How it works (three steps)
11. Reviews/trust (only verifiable claims)
12. Journal cards
13. Final CTA band with WhatsApp
14. Footer

---

## 7. Delivery phases

Indicative effort for one developer; content and legal work runs in parallel.

### Phase 0 — Baseline and safety (2 days)

- Restore version control: clone `randalu/IslandCulture`, create branch `remodel/v2`, tag `pre-remodel`.
- Back up `ict` database and `assets/uploads`; record checksums.
- Write `docs/remodel/route-inventory.md`, `content-readiness.md`, `visual-baseline/`.
- Run baseline: `composer validate`, `composer test`, `php cli\smoke_post_gates.php --only=dynamic`, `php cli\lint_image_alt.php`, Lighthouse on home and a destination page.
- Safety fixes: disable the empty partner directory until populated; remove the API key from `site_settings` and rotate it; confirm test reviews remain excluded.
- **Gate:** reproducible baseline, backups verified, no secret in the database.

### Phase 1 — Foundation and home (6 days)

- Add `app/bootstrap.php`, manual autoloader, `Page`, `Renderer`, base repositories.
- Build `views/layouts/public.php`, head/header/footer/scripts partials.
- Add `assets/css/site/tokens.css`, `base.css`, `layout.css`, `components.css`.
- Add `assets/js/site/nav.js`, `reveal.js`, `analytics.js`.
- Replace [index.php](../../index.php) with a thin `HomePage` controller and the new home template.
- Preserve page-view recording, privacy notice, construction notice, GTM, Trustpilot/GTranslate optional loading, tracking attributes and WhatsApp/phone hooks.
- **Gate:** home approved visually at 390/768/1440; Lighthouse targets met; no Bootstrap/Alpine on home; SEO and schema parity.

### Phase 2 — Discovery indexes (6 days)

- New templates for destinations, experiences, tours, itineraries, day tours and activities indexes.
- Shared filter bar, card grids, pagination, map teaser and rails.
- Server-side filters with canonical/noindex rules preserved.
- Replace `destinations.php`, `experiences.php`, `tours.php`, `itineraries.php`, `day-tours.php`, `activities.php`.
- **Gate:** every published record appears; filters keyboard-accessible; canonical rules tested.

### Phase 3 — Detail templates (9 days)

- New templates for destination, experience, tour, itinerary, day tour and activity detail.
- Components: detail hero, sticky aside, at-a-glance, map/route, day timeline, gallery, inclusions, FAQ, related, CTA.
- Replace `destination.php`, `experience.php`, `tour.php`, `itinerary.php`, `day-tour.php`, `activity.php`.
- Preserve JSON-LD types and FAQPage/Offer/Itinerary schema.
- **Gate:** all detail routes 200; missing-data fallbacks; gallery alt text; no layout shift.

### Phase 4 — Editorial and trust (6 days)

- Templates for journal index/article, seasonal/month, country, theme, reviews, about, why, FAQ.
- Article layout with TOC, share, related; review summary, filters and photos.
- Replace `blog.php`, `blog_post.php`, `seasonal.php`, `sri_lanka_in_month.php`, `from.php`, `country.php`, `theme.php`, `reviews.php`, `about-us.php`, `why-choose-us.php`, `faq.php`.
- **Gate:** article/FAQ/review schema correct; pagination canonicals correct; reviews only rendered when real.

### Phase 5 — Conversion and account (6 days)

- Templates for contact wizard, planner, compare, AI search, account pages, partners, static/legal pages and 404.
- Preserve every POST endpoint, CSRF check, honeypot, reCAPTCHA, rate limit and redirect.
- Account pages use the new shell but keep existing auth logic.
- Replace `contact.php`, `plan.php`, `compare.php`, `ai_search.php`, `account*.php`, `business_partner*.php`, `static_page.php`, `404.php`.
- **Gate:** form and auth test suites pass; no POST route bypassed; all public routes now on the new system.

### Phase 6 — Admin foundation (5 days)

- New admin shell in `admin/_top.php` / `admin/_bottom.php`: sidebar, topbar, search, user menu, responsive layout.
- New dashboard: inquiries, traffic, content health, SEO issues, missing media, drafts.
- Shared field helpers and table patterns.
- Keep Bootstrap, MDI and Quill in admin.
- **Gate:** every nav GET 200; auth/CSRF unchanged; dashboard numbers match queries.

### Phase 7 — Admin content workflows (9 days)

- Refresh editors: destinations, experiences, tours, itineraries + days, day tours, blog, static pages, FAQ, reviews, partners.
- Media library/picker with usage tracking, safe delete and alt enforcement.
- Draft preview, revision restore and activity log.
- Settings cleanup: secrets move to env; AI key revoked.
- **Gate:** CRUD round-trip tests, revision restore, media delete protection.

### Phase 8 — Content, media and SEO hardening (8 days, parallel)

- Restore `assets/uploads` from production; verify all 41 media rows and referenced paths; generate variants; replace 12 destination placeholders.
- Enrich all 43 destinations: `region_slug`, `region_label`, map coordinates, `stay_duration`, `emoji`, `best_time_json`, `route_notes_json`.
- Import verified reviews with permission; add photos; remove test rows.
- Seed ≥15 FAQ entries; publish legal/policy pages after legal sign-off.
- Populate 8–12 partners or disable the directory before launch.
- Reconcile itinerary prices (one missing `price_from`), hero media, homepage content and country/seasonal pages.
- **Gate:** content readiness checklist at 100% or explicitly gated off.

### Phase 9 — QA and launch (4 days)

- Full route smoke, cross-browser/device checks, axe audit, form submissions, account flows.
- Verify GTM events, CSP reports, consent gating, Tawk/Trustpilot/TripAdvisor behaviour.
- Confirm sitemap, robots, canonicals, redirects and 404 behaviour.
- Staging sign-off; production deploy; monitoring for 72 hours.
- **Gate:** launch checklist signed off; rollback tag recorded.

---

## 8. Backend and admin scope

**Kept unchanged**

- Admin auth, sessions, CSRF, POST gates, permission boundaries.
- Customer auth and account logic.
- Privacy/consent, analytics and retention logic.
- Existing data tables and public URLs.

**Rebuilt or added**

- Admin visual shell and dashboard.
- Shared form-field components and media picker.
- Content health scoring and review workflow.
- Revision history, activity log and media usage tracking.
- Review moderation, FAQ management, partner pipeline.
- Settings separation: secrets in `.env`, not `site_settings`.
- Preview links for drafts; publish/unpublish; scheduled publishing if required.

**Not in scope (unless separately approved)**

- Payment gateway or real-time availability.
- Framework migration.
- New languages beyond the existing GTranslate switcher.
- Rewriting admin auth or analytics internals.

---

## 9. Database and migrations

All migrations are additive, guarded and idempotent, under `database/migrations/`, applied with `php cli\migrate.php`, recorded in `schema_migrations`.

| Migration | Contents |
|---|---|
| `2026-11-02-remodel-ops.sql` | `content_revisions`, `admin_activity_log`, `media_usages` |
| `2026-11-02-remodel-content-workflow.sql` | `preview_token`, `reviewed_at`, `review_owner` on content tables (guarded per table) |
| `2026-11-02-remodel-faq-scope.sql` | `scope`, `entity_type`, `entity_id`, `is_featured` on `faq_entries` |
| `2026-11-02-remodel-reviews.sql` | `source_label`, `is_verified`, `consent_confirmed`; review indexes |
| `2026-11-02-remodel-destination-enrichment.sql` | Data backfill for all 43 destinations |
| `2026-11-02-remodel-policies.sql` | Publish legal/policy pages after sign-off |
| `2026-11-02-remodel-home-settings.sql` | New homepage section/finder settings |
| `2026-11-02-remodel-seo-defaults.sql` | Meta defaults for new/legal pages |

**Rules**

- Back up before every migration.
- No `DROP`, no column removal, no data overwrite outside explicit backfills.
- Use `db_table_exists()` / `db_column_exists()` in runtime code.
- Do not import `D:\www\IslandCulture-main\database\sril_site.sql`; it is a 70-table snapshot and would drop features present in `ict`.

---

## 10. Content and media readiness

| Item | Current state | Action |
|---|---|---|
| Destination heroes | 12 placeholders | Restore production media or commission replacements |
| Destination enrichment | 35 missing region, 42 missing best time | Backfill `region_slug`, map coordinates, stay, months, route notes |
| Reviews | 1 test row, no public reviews | Import verified reviews with permission; remove test data |
| FAQ | 0 rows | Seed ≥15 core questions with scoped entries |
| Legal pages | 5 unpublished | Legal review, then publish privacy, terms, cancellation, payment, confidence pages |
| Partners | 0 rows, directory enabled | Populate 8–12 or disable before launch |
| Media files | `assets/uploads/` empty locally | Restore from production backup; verify 41 media rows and all referenced paths |
| Itinerary pricing | 1 missing `price_from` | Fill or hide price on that record |
| Homepage hero media | 3 rows, files missing locally | Restore files or switch to image hero |
| Blog | 21 posts | Verify hero, author, meta, internal links |

---

## 11. SEO, performance and accessibility

**SEO**

- Page model owns title, description, canonical, robots, OG/Twitter image and alt.
- Filtered listings keep `seo_filter_canonical()` behaviour: canonical to base, `noindex,follow`.
- Pagination keeps self-canonical rules.
- JSON-LD: Organization/TravelAgency, WebSite+SearchAction, BreadcrumbList, TouristAttraction, TouristTrip, Product/Offer, FAQPage, Review/AggregateRating only when real.
- No URL changes, no redirect additions, no sitemap changes.
- 404 returns HTTP 404; no soft 404s.

**Performance**

- Remove Bootstrap and Alpine from public pages.
- Self-host fonts, preload the two primary faces, keep `font-display: swap`.
- Hero: responsive AVIF/WebP, `fetchpriority="high"`, explicit dimensions.
- Below-fold images lazy-loaded with `srcset`/`sizes`.
- Defer all JS modules; no third-party script before consent.
- Keep HTML `private, no-store` where privacy state requires it; static assets stay immutable.
- Budget: public CSS ≤90KB uncompressed, JS ≤40KB gzip, initial page ≤1MB.

**Accessibility**

- Landmarks, skip link, visible focus, 44px targets.
- Keyboard-operable menus, filters, accordions, lightboxes and wizards.
- Focus trap and restore for dialogs; `aria-current`, `aria-expanded`, labels and error messages.
- Contrast ≥4.5:1; no autoplaying sound; pause controls for motion.
- axe-core checks in CI for every template.

---

## 12. Testing and validation

**Existing commands**

```powershell
git status --short --branch
composer validate
composer test
git diff --check
php -l path\to\changed-file.php
php cli\smoke_post_gates.php --only=dynamic
php cli\lint_image_alt.php
php cli\generate_image_variants.php --dry-run --widths=400,800
```

**New tests**

- `tests/Remodel/PageTest.php` — page model, meta, canonical, schema.
- `tests/Remodel/*RepositoryTest.php` — fake PDO, normalisation, empty states.
- `tests/Remodel/TemplateRenderTest.php` — each template renders with fixture data.
- `cli/smoke_public_routes.php` — crawls every canonical route, asserts 200/404, title, description, canonical, H1, schema, no PHP warnings.
- Playwright visual + axe suite under `tests/Remodel/browser/` at 390, 768 and 1440.
- CI workflow running lint, Composer tests, route smoke and axe on pull requests.

**Manual gates per phase**

- 360/390/768/1024/1440 layouts.
- Keyboard-only navigation.
- Reduced-motion mode.
- Slow 4G throttling.
- Form submission and error states.
- Admin CRUD round-trip.

---

## 13. Rollout and rollback

- Work on `remodel/v2`; one pull request per phase; staging subdomain for review.
- Deploy per family only after its gate passes; home first, then discovery, then details, then editorial/conversion, then admin.
- Database migrations run before the matching code deploy; they are additive so the previous code release remains compatible.
- Rollback: redeploy the previous release tag; migrations are not reversed automatically.
- Post-deploy checks: route smoke, error logs, CSP reports, analytics events, GSC coverage, Core Web Vitals.
- Keep the old CSS/partials in place until the final family is migrated, then remove them in a dedicated cleanup commit.

---

## 14. Risks and mitigations

| Risk | Impact | Mitigation |
|---|---|---|
| Missing media locally | Cannot validate visual quality | Restore production uploads before Phase 3; placeholders in the meantime |
| Empty reviews/partners/policies | Trust and legal exposure | Content sprint + launch gates; disable empty partner directory now |
| SEO regression | Lost traffic | Metadata/schema parity tests; no URL changes; staged rollout |
| Scope creep | Delay | Fixed phase gates; admin after public; explicit non-goals |
| Admin breakage | Operations stop | Admin untouched until Phase 6; CRUD/auth tests |
| Secrets exposure | Security incident | Remove key from `site_settings`, rotate, move to `.env` |
| Inconsistent legacy pages during transition | Visual mismatch | New styles scoped under `body.ic-v2`; migrate family by family |
| DB snapshot confusion | Data loss | Treat `ict` as source of truth; never import the 70-table dump |

---

## 15. Assumptions and inputs needed

1. Access to production `assets/uploads` or a full backup for media restoration.
2. Permission to publish real customer reviews and their photos.
3. Legal review of privacy, terms, cancellation, payment and confidence pages.
4. Confirmation that bookings remain inquiry/quotation-based (no online payment).
5. Current logo and brand assets; an SVG logo is preferred.
6. Approval of the new navigation labels and home copy.
7. Confirmation that `ict` is the canonical database and the D: dump is reference only.

---

## 16. First slice on approval

I will implement **Phase 0 and Phase 1 only** first:

1. Restore the git baseline, back up DB/uploads, record the route and content inventory.
2. Add the `app/` foundation, page model, renderer and new public layout.
3. Build the design tokens, base CSS, header/footer, buttons, cards and home template.
4. Replace [index.php](../../index.php) with the thin controller and new home page, preserving SEO, schema, privacy, tracking and CTA behaviour.
5. Run route smoke, PHPUnit, image-alt lint and Lighthouse; present home at 390/768/1440 for visual sign-off.

Only after that review will the remaining page families and the admin refresh proceed.
