# v3 "Deep Coast" rebuild — implementation plan

Status: approved 2026-10-29. Supersedes the Island Coast plan for all remaining
work. Branch `remodel/v2`, phases commit and push individually.

## 1. Why this exists

The v2 rebuild reached 7 of 43 public routes (home + six indexes). The other 36
still render five unrelated legacy systems (`rd-`, `itin-`, `exp-`, `dt-`,
Bootstrap `page-hero-banner`) and load 194 KB of `style.css`, 43 KB of
`mobile-redesign.css`, Bootstrap and the MDI icon font. To a visitor, the site
therefore still looks unchanged.

v3 replaces every public page and the admin with one design language, distinct
layout per page type, Vue/Alpine interactivity and motion — without changing
URLs, SEO output, schema, auth or the data layer.

## 2. Success criteria

| Area | Target |
|---|---|
| Coverage | 43/43 public routes on v3; 40/40 admin nav GETs on the new shell |
| Difference | Zero legacy stylesheets and zero legacy class prefixes in public HTML |
| Interactivity | ≥ 12 Vue islands; Alpine for micro-interactions |
| Performance | LCP ≤ 2.5 s (4G), CLS ≤ 0.05, INP ≤ 200 ms; Lighthouse Perf ≥ 85, A11y 100, BP ≥ 95, SEO 100 |
| Weight | Public CSS ≤ 90 KB uncompressed; home JS ≤ 85 KB gzip (Vue build included); detail JS ≤ 40 KB gzip |
| Accessibility | axe 0 critical/serious at 390/768/1440; WCAG 2.2 AA; full keyboard paths |
| SEO | Title/description/canonical/robots/OG/Twitter/JSON-LD parity; sitemap URL set unchanged; no new redirects |
| Stability | Full route crawl with zero PHP warnings |

## 3. Design language

- **Canvas:** deep ocean `#06171D`, surfaces `#0B2A33`, sand panels `#F2E9DA`.
- **Accents:** coral `#FF6B4A` (primary action), amber `#F2B23E`, seafoam `#7FD8C4`.
- **Type:** Cormorant Garamond (display) + Montserrat (UI) — both already vendored
  in `assets/fonts/brand.css`, so no new font downloads.
- **Tokens:** `--ix-*` namespace in `assets/css/v3/tokens.css`; coexists with v2's
  `--ic-*` until the legacy sheets are deleted.
- **Motion:** three speeds (160/280/560 ms), `cubic-bezier(.16,1,.3,1)`;
  IntersectionObserver reveals, rAF-throttled hero drift, CSS marquee, animated
  counters, View Transitions where supported. All disabled under
  `prefers-reduced-motion`.
- **Texture:** grain overlay on dark sections, sparse glass cards, 1px hairlines.

## 4. Architecture

- Server-rendered PHP remains authoritative; **no SPA, no client router, no API layer**.
- **Vue 3.5.44** global production build (runtime + compiler), pinned at
  `lib/vue-3.5.44/dist/vue.global.prod.js` with sha256 and npm integrity in
  `assets/vendor/manifest.json`. Loaded only on pages that declare islands.
- Islands are declared in PHP (`data-island`, `data-props`) with full server
  markup inside, so crawlers and no-JS visitors get the complete page.
  `assets/js/site/islands.js` mounts them from a small registry.
- **Alpine 3.14.8** (already vendored) handles drawer, accordions, tabs, toasts.
- CSS stays build-free: five sheets in `assets/css/v3/`, cascade layers.

### Directory conventions

```
app/Pages/*PageV3.php        controllers (one per family, or shared family controller)
app/Content/                 repositories (prepared statements, memoised per request)
views/v3/layout.php          shell
views/v3/partials/           head, header, footer, scripts, shared components
views/v3/pages/<family>/     page templates
assets/css/v3/*.css          design system
assets/js/site/islands.js    Vue island registry
assets/js/site/motion.js     reveal, header, counters, hero drift
```

## 5. Page-type layouts

| Family | Layout signature | Signature interaction |
|---|---|---|
| Home | 100svh cinematic hero → marquee → intro split → bento → journey rail → matcher island → experiences → steps → journal → CTA | Hero drift, counters, marquee, Vue matcher |
| Destination detail | Split hero with map pin card, sticky facts rail, story, masonry gallery, "journeys through here" | Sticky rail, lightbox, map focus |
| Itinerary detail | Route timeline: sticky day navigator, day cards, animated SVG route, stays panel | Scroll-spy, route draw, day expand |
| Tour detail | Editorial body + sticky quote card, tabbed programme, coverage map, comparison | Sticky card, tabs, compare |
| Day tour detail | Compact hero, highlight rail, meeting-point map, availability form | Swipe rail, inline form |
| Experience detail | Magazine spread, two-column essay, "day in the life" scroll story, where-to map | Parallax, scroll story |
| Blog index / article | Editorial grid; article = narrow measure, reading progress, TOC rail, share | Reading bar, TOC scroll-spy |
| Country / theme / seasonal | Market landing tiles; seasonal = month strip + climate chart | Chart.js chart, month switch |
| Indexes | Filter bar island, list/map toggle, masonry/grid | Vue filters, instant results |
| Reviews | Rating summary, masonry cards, photo lightbox | Lightbox, chip filters |
| Contact / plan | Four-step wizard with progress rail and trust panel | Vue wizard, validation |
| Account | App dashboard: cards, timeline, drawer detail | Drawer, tabs |
| Legal / 404 | Document layout with TOC; 404 recovery | TOC |

## 6. Admin

- New shell in `admin/_top.php` / `admin/_bottom.php`: collapsible rail, top bar
  with global search + command palette (`Ctrl/⌘+K`), user menu, theme toggle,
  breadcrumbs, mobile drawer; 40 nav items regrouped into five sections.
- Dashboard: KPI cards, Chart.js traffic chart, content-health table, activity.
- One Vue data-table island (sort/filter/bulk/inline status) reused across
  content types; media picker island; editors get sticky save bars and inline
  JSON validation.
- Auth, CSRF, POST gates, permissions and every save handler stay untouched.
- Bootstrap/MDI/Quill remain inside admin until the shell ships, then Bootstrap
  is removed from public output entirely.

## 7. Data and SEO contract

- No URL, route, slug, sitemap or redirect changes.
- No database schema changes in this rebuild.
- `includes/seo.php` and `schema_output()` remain the only emitters.
- New repository methods are read-only additions (counts, day lists, related).

## 8. Phases

| Phase | Work | Gate |
|---|---|---|
| 0 | This document; Vue vendored + manifest; v3 token skeleton; `cli/asset_budget.php`; `design-qa.php` | Budget script runs; Vue pinned and served |
| 1 | v3 shell + home | Home approved at 390/768/1440; no legacy CSS; Lighthouse targets |
| 2 | Detail families (destination, experience, tour, itinerary, day tour, activity, blog post) | All detail routes 200; zero legacy CSS; schema parity |
| 3 | Index upgrades (Vue filters, map toggle, seasonal chart) | Filters work without JS; canonicals unchanged |
| 4 | Editorial/support/account/partners/legal/404 | All public routes on v3; form + auth suites pass |
| 5 | Admin shell + dashboard + command palette | Every nav GET 200; auth/CSRF unchanged |
| 6 | Admin tables + editors + media picker | CRUD round-trip on every editor |
| 7 | Cleanup, media/SEO hardening, QA, launch | Legacy CSS deleted; route crawl clean; axe 0 |

## 9. Risks

| Risk | Mitigation |
|---|---|
| Vue without a bundler means runtime templates | Server markup is complete; islands only enhance |
| Global Vue build is 61 KB gzip | Loaded only on island pages; budget script enforces it |
| Dark-first palette hurts long-form reading | Prose sits on sand panels |
| No screenshots available in this sandbox | `design-qa.php` checklist + review at `http://localhost/ict/` |
| Missing `assets/uploads` media | Hero fallbacks; restore uploads before the Phase 2 gate |

## 10. Rollback

v2 templates and CSS stay in the tree until Phase 7. Reverting a family is
`git revert` of that phase's commit; reverting everything is
`git checkout pre-remodel -- .` on a clean branch.

## 11. v3.1 "Daylight" revision (approved 2026-10-29)

Direction change after review: light by default, more app-like, far less copy.

- **Light default.** Tokens flip to a warm off-white canvas (`#F6F6F3`), white
  surfaces and ink text; dark survives only in the footer and the closing CTA
  band, plus an opt-in toggle stored in `localStorage` (`assets/js/site/theme.js`).
- **App shell.** 36px utility bar, 64px sticky header with a real search field
  (submits to `/destinations?q=`), segmented controls, white cards with 14px
  radii and soft shadows, sticky filter bar on the indexes, dense dark footer.
- **Copy budget.** Home is capped at 450 visible words (was 1,293) and measured
  by `php cli\copy_budget.php`. Removed: marquee ticker, duplicated intro stats,
  all card excerpts, all section intros except one line, the long "how it works"
  paragraphs.
- **Indexes on the v3 shell.** `CollectionIndexPage` now renders
  `views/v3/layout.php` with `views/v3/pages/collections/index.php`, so the six
  discovery routes match the home instead of using the v2 ivory editorial sheet.
- **Budgets.** Public CSS ≤ 90 KB uncompressed; home JS ≤ 85 KB gzip (includes
  the 61 KB Vue build); detail JS ≤ 40 KB gzip. Enforced by
  `php cli\asset_budget.php`.
