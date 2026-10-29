# Route inventory

Captured at Phase 0 (2026-10-29) from `.htaccess` and the root page files.
This is the canonical list the revamp must keep at full parity: **no URL is
added, renamed or redirected** by the remodel.

Column meanings: *Current* is the file that serves the route today; *v2 target*
is the view family planned in `remodel-plan.md`; *Phase* is the delivery phase
that replaces it.

## Public routes

**Phase 2 is done:** `/destinations`, `/experiences`, `/tours`, `/itineraries`,
`/day-tours` and `/activities` now render through
`app/Pages/CollectionIndexPage.php` + `views/pages/collections/index.php`.
Their URLs, canonical rules and listing schema are unchanged.

| Route | Current | v2 target | Phase |
|---|---|---|---|
| `/` | `index.php` | `pages/home.php` | **1 (done)** |
| `/destinations` | `destinations.php` | `pages/destinations/index.php` | 2 |
| `/destinations/{slug}` | `destination.php` | `pages/destinations/show.php` | 3 |
| `/experiences` | `experiences.php` | `pages/experiences/index.php` | 2 |
| `/experiences/{slug}` | `experience.php` | `pages/experiences/show.php` | 3 |
| `/activities` | `activities.php` | `pages/activities/index.php` | 2 |
| `/activities/{slug}` | `activity.php` | `pages/activities/show.php` | 3 |
| `/tours` | `tours.php` | `pages/journeys/tours.php` | 2 |
| `/tours/{slug}` | `tour.php` | `pages/journeys/tour.php` | 3 |
| `/itineraries` | `itineraries.php` | `pages/journeys/itineraries.php` | 2 |
| `/itineraries/{slug}` | `itinerary.php` | `pages/journeys/itinerary.php` | 3 |
| `/day-tours` | `day-tours.php` | `pages/journeys/day-tours.php` | 2 |
| `/day-tours/{slug}` | `day-tour.php` | `pages/journeys/day-tour.php` | 3 |
| `/blog` | `blog.php` | `pages/articles/index.php` | 4 |
| `/blog/{slug}` | `blog_post.php` | `pages/articles/show.php` | 4 |
| `/seasonal` | `seasonal.php` | `pages/guides/index.php` | 4 |
| `/sri-lanka-in-{month}` | `sri_lanka_in_month.php` | `pages/guides/month.php` | 4 |
| `/from` | `from.php` | `pages/guides/country.php` (index) | 4 |
| `/from/{slug}` | `country.php` | `pages/guides/country.php` | 4 |
| `/{theme}-tours` (`honeymoon`, `family`, `adventure`, `wellness`, `luxury`, `wildlife`) | `theme.php` | `pages/guides/theme.php` | 4 |
| `/reviews` | `reviews.php` | `pages/reviews.php` | 4 |
| `/about-us` | `about-us.php` | `pages/support/about.php` | 4 |
| `/why-choose-us` | `why-choose-us.php` | `pages/support/why.php` | 4 |
| `/faq` | `faq.php` | `pages/support/faq.php` | 4 |
| `/contact` | `contact.php` | `pages/support/contact.php` | 5 |
| `/plan` | `plan.php` | `pages/support/plan.php` | 5 |
| `/compare` | `compare.php` | `pages/support/compare.php` | 5 |
| `/ai-search` | `ai_search.php` | `pages/support/search.php` | 5 |
| `/account` | `account.php` | `pages/account/dashboard.php` | 5 |
| `/account/login` | `account_login.php` | `pages/account/login.php` | 5 |
| `/account/logout` | `account_logout.php` | unchanged (POST target) | 5 |
| `/account/google/callback` | `account_google_callback.php` | unchanged (OAuth target) | 5 |
| `/account/customize-itinerary` | `account_customize_itinerary.php` | `pages/account/customise.php` | 5 |
| `/account/plans/{id}` | `account_plan.php` | `pages/account/plan.php` | 5 |
| `/account/plans/{id}/policy-accept` | `account_plan_policy_accept.php` | unchanged (POST target) | 5 |
| `/account/plans/{id}/days/{day}/request` | `account_plan_request_save.php` | unchanged (POST target) | 5 |
| `/account/inquiries/{id}` | `account_inquiry.php` | `pages/account/inquiry.php` | 5 |
| `/account/inquiries/{id}/message` | `account_inquiry_message_save.php` | unchanged (POST target) | 5 |
| `/business-partners` | `business_partners.php` | `pages/partners/index.php` | 5 |
| `/business-partners/{category}` | `business_partners.php` | `pages/partners/index.php` | 5 |
| `/business-partners/partner/{slug}` | `business_partner.php` | `pages/partners/show.php` | 5 |
| `/business-partners/apply` | `business_partner_apply.php` | `pages/partners/apply.php` | 5 |
| `/privacy-policy` | `static_page.php` | `pages/legal/page.php` | 5 |
| `/booking-terms-and-conditions` | `static_page.php` | `pages/legal/page.php` | 5 |
| `/cancellation-refund-policy` | `static_page.php` | `pages/legal/page.php` | 5 |
| `/payment-deposit-policy` | `static_page.php` | `pages/legal/page.php` | 5 |
| `/book-with-confidence` | `static_page.php` | `pages/legal/page.php` | 5 |
| `/{slug}` (other static pages) | `static_page.php` | `pages/legal/page.php` | 5 |
| `/private-driver-sri-lanka/cost` | `static_page.php` | `pages/legal/page.php` | 5 |
| unknown route | `404.php` | `pages/errors/404.php` | 5 |

## Non-page routes

| Route | Handler | Notes |
|---|---|---|
| `/sitemap.xml` | `sitemap_index.php` | must stay byte-compatible in URL terms |
| `/sitemap-{type}.xml` | `sitemap_partial.php` | types: core, destinations, itineraries, tours, experiences, activities, day-tours, partners, countries, blog, themes, seasonal, n-day, images |
| `/admin`, `/admin/*` | `admin/` | untouched until Phase 6 |
| `/subscribe.php` | `subscribe.php` | POST, CSRF + rate limited |
| `/track-event.php` | `track-event.php` | POST, analytics |
| `/csp-report.php` | `csp-report.php` | POST, reporting |
| `/privacy_choices.php` | `privacy_choices.php` | consent UI |
| `/privacy_choices_save.php` | `privacy_choices_save.php` | POST |
| `/content-preview.php` | `content-preview.php` | token-gated preview |

## Blocked / denied paths

`.htaccess` already denies direct access to `config/`, `includes/`, `database/`,
`data/`, `output/`, `storage/`, `cli/`, `tests/`, `NEW-SITE/`, `references/`,
dot-directories, `VPS-LIVE-PULL.txt`, `*.sql`, `*.log`, `*.md`, `install.php`,
`hash.php`, `test.php`, `map_preview.php`, `tripadvisor_widgets_preview.php`,
`map_demo.html`, `ict_mockups.html`, and the retired `n-day-itinerary` route.

The v2 `app/` and `views/` directories must be added to that deny list when they
start holding request-time code (they are only includes today, never routed).

## Redirects that must be preserved

| From | To | Status |
|---|---|---|
| `/tours/sri-lanka-wildlife-safari-tour` | `/tours/sri-lanka-wildlife-safari-package` | 301 |
| `http://` → `https://` | same path | 301 |
| `www.` → apex | same path | 301 |
