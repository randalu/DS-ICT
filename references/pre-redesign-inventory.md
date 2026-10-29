# Pre-Redesign Inventory — Island Culture Travels

**Date:** 2026-08-28
**Tag:** `pre-redesign` (commit `9004e60`)
**Branch:** `redesign/island-culture-theme` (branched from `main` at `9004e60`)
**Base URL (from config):** `https://island.sey` (local) / `https://srilankaprivatetravels.com` (production APP_URL — check `.env.example`)
**Purpose:** Baseline before site-wide redesign. All URLs, routes, assets, body classes, and integrations below must remain functional after redesign.

> Source of truth: `.htaccess`, `sitemap_index.php`, `sitemap_partial.php`, `includes/header.php`, `includes/footer.php`, `includes/functions.php`, root `*.php` files, `assets/css/*`, `assets/js/*`.

---

## 1. URLs Currently Indexed (Sitemap)

Sitemap entry points (declared in `.htaccess:33-35`):
```
sitemap.xml            → sitemap_index.php
sitemap-{type}.xml     → sitemap_partial.php?type={type}
```

### 1.1 Sitemap Index (`sitemap_index.php:8-24`)
Types emitted (11 core + conditional):
```
core
destinations
itineraries
experiences
day-tours
countries
blog
themes
seasonal
n-day
images
partners  (only if business_partners_public_enabled() === true)
```
Each maps to `<loc>{{base_url}}/sitemap-{type}.xml</loc>`.

### 1.2 Core URLs (`sitemap_partial.php:69-93` — type `core`)
Static set always emitted:
```
/
 /destinations
 /experiences
 /itineraries
 /seasonal
 /from
 /about-us
 /why-choose-us
 /reviews
 /faq
 /contact
 /blog
 /business-partners                       (if partners enabled)
 /business-partners/hotels-accommodations (if partners enabled)
 + every row in static_pages where slug NOT IN (above) AND is_published=1
   → /{slug}  (resolved via RewriteRule ^([a-z0-9-]+)/?$ → static_page.php?slug=$1)
```

### 1.3 Dynamic Types (`sitemap_partial.php:112-431`)

| Type | Source table | URL pattern | Filter |
|------|--------------|-------------|--------|
| `destinations` | `destinations` | `/destinations/{slug}` | `is_published=1` |
| `itineraries` | `itineraries` | `/itineraries/{slug}` | `is_published=1` |
| `experiences` | `experiences` via `fetch_experiences(true)` | `/experiences/{slug}` | published |
| `day-tours` | `day_tours` | `/day-tours/{slug}` | `is_published=1` |
| `tours` | `itinerary_categories` | `/tours/{slug}` | `is_published=1` |
| `themes` | `itinerary_categories` where `theme_slug <> ''` | `/{theme_slug}` (e.g. `/honeymoon-tours`) — see `theme.php` | `is_theme=1` if column exists |
| `partners` | `business_partners` | `/business-partners/partner/{slug}` | `status='published'` |
| `countries` | `country_pages` | `/from/{slug}` | `is_published=1` |
| `blog` | `blog_posts` | `/blog/{slug}` or `canonical_url` if set | `is_published=1` |
| `seasonal` | hard-coded 12 months | `/sri-lanka-in-{january…december}` | — |
| `n-day` | `itineraries` distinct `days` | `/{N}-day-itinerary` (1–30) | `days BETWEEN 1 AND 30 AND is_published=1` |
| `images` | same rows as above + image columns (`og_image`, `cover_image`, `hero_image`) | same `<loc>` as parent + `<image:loc>` | — |

### 1.4 Canonical Route Table (`.htaccess:37-88`)

Static:
```
/admin                → admin/index.php
/account              → account.php
/account/login         → account_login.php
/account/logout        → account_logout.php
/account/google/callback
/account/customize-itinerary
/account/plans/{id}
/account/plans/{id}/days/{id}/request
/account/inquiries/{id}
/account/inquiries/{id}/message
/about-us             → about-us.php
/why-choose-us        → why-choose-us.php
/reviews              → reviews.php
/faq                  → faq.php
/contact              → contact.php
/ai-search            → ai_search.php
/destinations         → destinations.php
/tours                → tours.php
/experiences          → experiences.php
/day-tours            → day-tours.php
/business-partners    → business_partners.php
/business-partners/apply → business_partner_apply.php
/business-partners/partner/{slug} → business_partner.php
/business-partners/{category}     → business_partners.php?category={slug}
/itineraries          → itineraries.php
/from                 → from.php
/seasonal             → seasonal.php
/blog                 → blog.php
/blog/{slug}          → blog_post.php
/compare              → compare.php
/plan                 → plan.php
/{N}-day-itinerary    → n_day_itinerary.php?days={N}
/{honeymoon|family|adventure|wellness|luxury}-tours → theme.php
/sri-lanka-in-{month} → sri_lanka_in_month.php
/destinations/{slug}  → destination.php
/tours/{slug}         → tour.php
/day-tours/{slug}     → day-tour.php
/experiences/{slug}   → experience.php
/itineraries/{slug}   → itinerary.php
/from/{slug}          → country.php
/{slug}               → static_page.php (fallback before 404)
404                   → 404.php (final RewriteRule + ErrorDocument)
```

All rewrites are `NC` + `QSA`; canonical host/scheme 301s at top (`www→non-www`, `http→https`).

Production indexed URL count depends on DB rows; run locally: `php -r "require 'includes/functions.php';"` then hit `/sitemap.xml` or `sitemap_partial.php?type=core`.

---

## 2. Navigation Items and Their Routes

### 2.1 Header Nav (`includes/header.php:138-164`)

Rendered as Bootstrap navbar with Alpine `mobileMenu()` — `aria-expanded`, `aria-controls="siteNav"`.

| Label | Route (`base_url(...)`) | Active check | Notes |
|-------|-------------------------|--------------|-------|
| Destinations | `/destinations` | `route_is('/destinations') \|\| path starts /destinations/` | `page-destinations`, `page-destination` |
| Experiences | `/experiences` | `route_is('/experiences','/tours') \|\| /experiences/* or /tours/*` | Covers tours too |
| Tour Itineraries | `/itineraries` | `route_is('/itineraries') \|\| /itineraries/*` | |
| About Us | `/about-us` | `route_is('/about-us')` | |
| Travel Guide | `/seasonal` | `route_is('/seasonal') \|\| /sri-lanka-in-* \|\| /plan \|\| /compare \|\| /blog*` | umbrella — groups seasonal + planner + blog |
| Reviews | `/reviews` | `route_is('/reviews')` | |
| Contact | `/contact` | `route_is('/contact')` | |
| Plan My Trip (CTA) | `/contact` | `site-shell-plan-btn` | In `.site-shell-actions` next to `.gtranslate_wrapper` |

Logo links to `/` (`assets/images/island_culture_travels_logo.png`).

Mobile: hamburger toggles `#siteNav` via `@click="toggleMenu()"`, backdrop `#siteNavBackdrop`, `x-show`, `x-transition`, overlay overflow-hidden on `body.menu-open`.

### 2.2 Footer Nav (`includes/footer.php:224-284`)

4-column grid `.footer-links-grid` (accordion on mobile via `x-data="{open:false}"`):

- **Destinations** — loop `destinations where is_published=1 order is_featured DESC, sort_order, name` (+ merge popular from `page_views` if table exists) → `/destinations/{slug}`; final item `All Destinations` → `/destinations`
- **Experiences** — `experiences where is_published=1 …` → `/experiences/{slug}`; header `Experiences & Hidden Gems` → `/experiences`
- **Day Tours** — `tours where is_published=1 …` → `/tours/{slug}`; `All Day Tours` → `/tours`
- **Itineraries & Services** — `itineraries … order is_featured, sort_order, days, name` → `/itineraries/{slug}`; `All Itineraries` → `/itineraries`; plus `footer_service_links` (dynamic `label`/`url`) → `base_url({url})`
- **Copyright row** — `footer_bottom_links` loop → `base_url({url})`

Top footer (`footer-top`): brand + tagline "Sri Lanka Private Travel" + contact items → `tel:` primary/secondary, `mailto:`, WhatsApp `site_whatsapp_url()` → `https://wa.me/{number}?text=…`. Middle (`footer-middle`): bio copy + social icons (max 18 from `fetch_active_social_links()` — `icon_text` fallback `icon_image`) + TripAdvisor widget + Trustpilot widget.

`disableServicesSection` flag skips `services_section.php` include (used on some pages).

---

## 3. Forms and What They POST To

| Form location | Method | Action (`base_url(...)` or literal) | Notes |
|---------------|--------|--------------------------------------|-------|
| `account_customize_itinerary.php:145` | POST | `/account/customize-itinerary?itinerary_id={id}` | account trip-type card |
| `account_inquiry.php:107` | POST | `/account/inquiries/{id}/message` | inquiry reply |
| `account_plan.php:122` | POST | `/account/plans/{planId}/days/{dayId}/request` | per-day change request |
| `account_plan.php:181` | POST | `/account/inquiries/{contact_inquiry_id}/message` | plan-linked inquiry message |
| `ai_search.php:22` | GET | `/ai-search` | search query `?q=` + theme filters |
| `business_partner_apply.php:333` | POST `multipart/form-data` | self (no action — posts to same URL) + CSRF | partner application — includes file uploads |
| `contact.php:424` (`.inquiry-wizard`) | POST | `/contact` | Reacted via `x-data="formWizard(config)"` + `x-ref="form"` — includes CSRF `csrf_field()`, honeypot, reCAPTCHA `g-recaptcha` if enabled |
| `day-tours.php:145` `#dayTourFilterForm` | GET | `/day-tours` | filter/sort — `?duration=&theme=&search=` |
| `install.php:824` | POST | self | one-time installer (self-lock/deletes after success) |
| `plan.php:87` `[data-planner-form]` | GET/JS | handled by `trip-planner.js` | multi-step wizard — JS drives `plan.php` → `contact` handoff |
| `subscribe.php` (AJAX) | POST | `/subscribe.php` | newsletter — CSRF, JSON response |
| `track-event.php` (via `footer.php:397` JS) | POST JSON (`fetch`/`sendBeacon`) | `/track-event.php` with `_token: csrf_token()` | event types: `whatsapp_click`, `phone_click`, `email_click`, `contact_cta_click`, `external_link_click`, plus `data-track-event` custom + GTM `dataLayer.push` |

All POST handlers require `require_csrf()` / `csrf_field()` and `guard_post_size()`. Admin write handlers live under `admin/` (out of scope for redesign — do not touch).

---

## 4. CSS Files

### 4.1 Project CSS (`assets/css/`)

| File | Size | Loaded on | Purpose |
|------|------|-----------|---------|
| `assets/css/style.css` | 178,047 B | **Every page** — `<link href="assets/css/style.css?v={mtime}">` in `header.php:84` | Main stylesheet — `:root` tokens, base, navbar, buttons, cards, dest tiles, hero, detail panels, accordion, footer modern, etc. Versioned by `filemtime`. |
| `assets/css/new-home.css` | 30,039 B | `index.php` + `experience.php` via `$extraStylesheets` | Homepage exact sections: hero precise, floating trust, carousel, dark bands, CTA band. Also reused on experience detail (audit before removing). |
| `assets/css/detail-redesign.css` | 22,647 B | `destination.php` via `$extraStylesheets` | Destination detail hero + content panel overrides. |
| `assets/css/mobile-redesign.css` | 46,898 B | **Every page** — `<link href="assets/css/mobile-redesign.css">` in `header.php:85` (no version query) | Mobile breakpoint overrides — 360–768 pass. Consolidation candidate in Phase 10. |
| `assets/css/admin.css` | 16,026 B | `admin/*` only | Admin template — never load on public pages. |

### 4.2 Vendor / CDN CSS

- `https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css` — `header.php:83` (preconnected via `https://cdn.jsdelivr.net`).
- Google Fonts: `https://fonts.googleapis.com/css2?family=Allura&family=Cormorant+Garamond:wght@500;600;700&family=Montserrat:wght@400;500;600;700;800;900&display=swap` — `header.php:78` (preconnect `fonts.googleapis.com` + `fonts.gstatic.com`).
- Inline `detail-redesign.css` / `new-home.css` use CSS variables from `style.css:6-55`.

### 4.3 Future Component

`assets/css/components.css` does **not** exist yet — to be created in Phase 2 and added after `style.css` in `header.php:84`.

---

## 5. JS Files

### 5.1 Project JS (`assets/js/`)

| File | Size | Load | Purpose |
|------|------|------|---------|
| `assets/js/app.js` | 19,523 B | `footer.php:316` `<script defer src="assets/js/app.js">` | Global: header scroll, mobile nav, motion-reveal, content preview popover, scroll-spy, tracking. No build step. |
| `assets/js/alpine-components.js` | 8,863 B | `header.php:88` defer | Alpine `x-data` definitions: `mobileMenu`, `scrollHeader`, `scrollSpy`, `titleAnimate`, `cardReveal`, `heroSection`, `durAutoScroll`, `themeFilter`, `itinCarousel`, `destCarousel`. |
| `assets/js/scroll-behaviors.js` | 10,140 B | `header.php:87` defer | Scroll header class, parallax, reveal. |
| `assets/js/trip-planner.js` | 515 B | Enqueued via `plan.php` (defer) | Wizard step handling — minimal. |
| `assets/js/admin.js` | 1,957 B | `admin/*` only | Admin helpers. |

All 3 public scripts loaded `defer`; `alpine-components.js` registers `Alpine.data(...)` inside `alpine:init`.

### 5.2 Vendor / CDN JS

- `https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js` — `header.php:86` defer (preconnected CDN).
- `https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js` — `footer.php:315` defer (fallback accordion in footer provides collapse if Bootstrap fails: `footer.php:495-547`).
- `includes/tour-1.0.0/js/*` — legacy theme bundle (jquery, aos, jarallax, etc.) — **not** loaded globally; only referenced by old templates if needed. Keep but do not add to new pages.

---

## 6. Third-Party Scripts and Integrations

| Service | Snippet location | URL / ID | Behaviour |
|---------|-----------------|----------|-----------|
| **Google Tag Manager** | `includes/seo.php` via `render_google_tag_manager_head()` + `render_google_tag_manager_body()`; also `footer.php:401-491` queues `dataLayer.push` | Container ID from `GOOGLE_TAG_MANAGER_ID` / site setting | `header.php:61` + body noscript iframe. Footer JS maps `whatsapp_click`, `phone_click`, etc. to `dataLayer` events. |
| **Trustpilot — TrustBox bootstrap** | `header.php:63` | `//widget.trustpilot.com/bootstrap/v5/tp.widget.bootstrap.min.js` (async) + preconnect `widget.trustpilot.com` | Global. |
| **Trustpilot — invite API** | `header.php:67-70` | `https://invitejs.trustpilot.com/tp.min.js` register `bfIhV41lAqOGqanL` + preconnect `invitejs.trustpilot.com` | Global. |
| **Trustpilot — footer widget** | `footer.php:210-214` + `includes/review_widget_snippets.php` | `data-businessunit-id="69f0e87e25fe3531660ce70c"` template `56278e9a…`, additional snippets for preview/review collector | Rendered in `.footer-trustpilot-widget` + `.footer-tripadvisor-widget`. |
| **TripAdvisor** | `includes/review_widget_snippets.php` + `footer.php:206-210` | `https://www.jscache.com/wejs?wtype=...&locationId=27525299` 8 widget variants; footer uses `tripadvisor_footer_trust_widget()` = `Social Button / Small Icon` | `jscache` scripts with `async` + `data-loadtrk`. |
| **GTranslate** | `header.php:73-74` | `https://cdn.gtranslate.net/widgets/latest/dwf.js` defer + `window.gtranslateSettings` (`default_language: en`, 10 languages, wrapper `.gtranslate_wrapper`) + preconnect `cdn.gtranslate.net` | Renders language switcher in `.site-shell-actions` + topbar (Phase 3). |
| **Tawk.to** | `footer.php:555-579` | `https://embed.tawk.to/{TAWK_TO_PROPERTY_ID}/{TAWK_TO_WIDGET_ID}` | Lazy-load after 3s or on `scroll/mousemove/touchstart/keydown` via `requestIdleCallback`, `crossorigin="*"`. Controlled by `TAWK_TO_ENABLED` + property ID. |
| **reCAPTCHA v2** | `includes/recaptcha.php` + `contact.php` wizard | `https://www.google.com/recaptcha/api/siteverify` (verify) + `https://www.google.com/recaptcha/` (frame) | `recaptcha_site_key` / `recaptcha_secret_key` from settings or env; `recaptcha_html()` injects `<div class="g-recaptcha" data-sitekey="">`. CSP `frame-src` allowlists `www.google.com/recaptcha/`. |
| **Google Fonts** | `header.php:78` | `fonts.googleapis.com` + `fonts.gstatic.com` | Montserrat, Cormorant Garamond, Allura. `font-display: swap` via Google param already present — do not regress. |
| **CSP / Reporting** | `.htaccess:114-130` | `Content-Security-Policy` header + `Reporting-Endpoints csp-endpoint="/csp-report.php"` | Allowlist: `cdn.jsdelivr.net`, `fonts.googleapis.com`, `fonts.gstatic.com`, `googletagmanager.com`, `google-analytics.com`, `google.com/recaptcha`, `gstatic/recaptcha`, `embed.tawk.to`, `api.indexnow.org`, `googleapis.com`, `weather.googleapis.com`, `youtube.com/embed`, `unsafe-inline` for Alpine, `report-uri /csp-report.php`. |

Do **not** add/remove third-party scripts during redesign without tracking parity check (production analytics breakage is a launch blocker).

---

## 7. Body Classes Per Page

`$bodyClass` set **per file** then echoed as `<body class="…">` (`header.php:108`). `site-has-construction-notice` auto-appended if `site_under_construction_notice_enabled()`.

| File | `$bodyClass` value |
|------|-------------------|
| `index.php:661` | `page-home-new page-home-new-exact` |
| `404.php:33` | `page-404` |
| `about-us.php:33` | `page-about` |
| `account.php:22` | `page-account` |
| `account_customize_itinerary.php:102` | `page-account-customizer` |
| `account_inquiry.php:29` | `page-account-inquiry` |
| `account_login.php:24` | `page-account-login` |
| `account_plan.php:29` | `page-account-plan` |
| `blog.php:21` | `page-blog` |
| `blog_post.php:63` | `page-blog-post` |
| `business_partner.php:41` | `page-business-partner` |
| `business_partners.php:43` / `business_partner_apply.php:251` | `page-business-partners` |
| `compare.php:83` | `page-compare` |
| `contact.php:363` | `page-contact` |
| `country.php:98` | `page-country` |
| `day-tour.php:264` | `page-day-tour` |
| `day-tours.php:114` | `page-day-tours` |
| `destination.php:168` | `page-destination ict-detail-page` |
| `destinations.php:145` | `page-destinations` |
| `experience.php:199` | `page-experience` |
| `experiences.php:164` | `page-experiences` |
| `faq.php:85` | `page-faq` |
| `from.php:53` | `page-from-countries` |
| `itineraries.php:147` | `page-itineraries` |
| `itinerary.php:216` | `page-itinerary` |
| `map_preview.php:12` | `page-map-preview` |
| `n_day_itinerary.php:56` | `page-n-day-itinerary` |
| `plan.php:73` | `page-plan` |
| `reviews.php:110` | `page-reviews` |
| `seasonal.php:50` | `page-seasonal-hub` |
| `sri_lanka_in_month.php:97` | `page-seasonal` |
| `static_page.php:70` | `page-static` |
| `theme.php:75` | `page-theme` |
| `tour.php:147` + `496` | `page-tour-detail` (detail) / `page-tours` (category fallback — conditional) |
| `tours.php:50` | `page-tours` |
| `why-choose-us.php:32` | `page-why` |
| `tripadvisor_widgets_preview.php:15` | `page-tripadvisor-widget-preview` |

### 7.1 Overlay Header Pages (`header.php:27-47` — `$overlayHeaderBodyClasses`)

Header becomes transparent fixed with blur until scroll (`is-scrolled` class via JS). Affects:

```
page-home, page-home-new, page-day-tour, page-destination, page-tour,
page-tour-detail, page-itinerary, page-country, page-destinations,
page-tours, page-itineraries, page-from-countries, page-experience,
page-experiences, page-reviews, page-seasonal-hub, page-blog,
page-business-partners, page-contact
```

(Page list is exhaustive per current header — any new page reusing same templates inherits overlay if class added.)

`$headerClass` = `site-shell-header sticky-top [site-shell-header--overlay]` toggled by `$usesOverlayHeader`.

---

## 8. Extra Stylesheets Per Page

Mechanism (`header.php:89-106`):

```php
$extraStylesheets = ['assets/css/new-home.css']; // example
// each entry appended as <link href="base_url(entry)?v={mtime}">
// external https:// URLs passed through without mtime
```

| Page(s) | `$extraStylesheets` value | File |
|---------|--------------------------|------|
| `index.php` | `['assets/css/new-home.css']` | `assets/css/new-home.css` (30k) |
| `experience.php` | `['assets/css/new-home.css']` | same file — reuses homepage blocks; audit in Phase 8 before removing |
| `destination.php` | `['assets/css/detail-redesign.css']` | `assets/css/detail-redesign.css` (22k) |
| All other public pages | *(none)* | Only `style.css` + `mobile-redesign.css` + Bootstrap + Fonts |

Note: `itinerary.php` / `tour.php` previously used `detail-redesign.css` in older revisions but currently load only `style.css` (their GEM redesign is self-contained via `style.css` detail panel classes). Verify before re-adding extra sheet.

`mobile-redesign.css` is **global** (not per-page) — loaded after `style.css` on all pages.

---

## 9. Supporting Details

### 9.1 Global Includes (Do Not Edit During Redesign)

```
config/bootstrap.php
includes/db.php, includes/auth.php, includes/customer_auth.php, includes/sessions.php
includes/schema.php, includes/schema_helpers.php, includes/seo.php, includes/recaptcha.php, includes/indexnow.php
admin/ (entire directory)
database/ (migrations)
cli/
composer.json/lock, robots.txt, sitemap*.php, .htaccess, .env.example
```

### 9.2 SEO / Schema Surface (Preserve)

- Every public page includes `includes/seo.php` (`header.php:60`) + `includes/schema.php` helpers (JSON-LD).
- Canonical logic via `canonical_url()` / `base_url()` — unchanged by redesign.
- `robots.txt` references `sitemap.xml` — must stay.
- Do not change URL or slug columns.

### 9.3 Current Design Tokens (`assets/css/style.css:6-55`)

```css
:root {
  --bg: #ffffff; --surface: #ffffff; --surface-alt: #f7f3ea;
  --surface-dark: #0d1f14; --surface-mid: #132b1c;
  --text: #0c2316; --text-body: #2c3a2e; --muted: #5a6b5e;
  --brand: #2d6a41; --brand-dark: #1a4f2e; --brand-soft: rgba(45,106,65,.10);
  --accent: #8f7a28; --accent-light: #c5a84a;
  --line: rgba(12,35,22,.10); --line-strong: rgba(12,35,22,.18);
  --shadow-sm: 0 4px 20px rgba(12,35,22,.06);
  --shadow-md: 0 12px 36px rgba(12,35,22,.10);
  --shadow-lg: 0 24px 56px rgba(12,35,22,.14);
  --radius-sm: .75rem; --radius-md: 1rem; --radius-lg: 1.25rem;
  --transition-fast: 180ms ease; --transition-med: 320ms ease;
  --container-max: 1280px;
  --font-sans: 'Montserrat'; --font-display: 'Cormorant Garamond';
  --font-serif: var(--font-display); --font-heading: var(--font-display);
  --font-script: 'Allura', cursive;
}
```

Phase 1 will update these tokens (see `references/redesign-reference.html` + plan for target values). No other CSS changes permitted in Phase 1.

### 9.4 Visual Reference

`references/redesign-reference.html` (copied from `sri_lanka_private_travels_redesign.html` — 45k, Tailwind prototype). Not shipped to production; design-system tokens derived from it in Phase 1.

---

## 10. Verification Checklist (Phase 0)

- [x] `git tag pre-redesign` pushed to `origin`
- [x] Branch `redesign/island-culture-theme` created and pushed with `-u`
- [x] `references/redesign-reference.html` present (copied via `cp sri_lanka_private_travels_redesign.html references/redesign-reference.html`)
- [x] This inventory file created at `references/pre-redesign-inventory.md`
- [ ] Next: Phase 1 — `assets/css/style.css` `:root` tokens only

Branch protection: all subsequent work must happen on `redesign/island-culture-theme`; `main` stays deployable until Phase 12 merge.

