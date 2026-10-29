# Route Classification Map

Purpose: classify the current web route surface so production routes, admin routes, and local/dev utilities are easy to audit before cleanup or deployment.

Evidence checked:
- `.htaccess` rewrite rules and direct-file pass-through behavior.
- Root PHP/HTML files in the web root.
- Admin navigation and admin PHP handlers under `admin/`.
- Database-backed route families from the current schema and migrations.

## Labels

| Label | Meaning | Deployment posture |
| --- | --- | --- |
| `production-public` | Public visitor route or public system endpoint needed by the live website. | Keep reachable, SEO/indexing policy depends on page type. |
| `production-admin` | Authenticated admin route, admin handler, or admin-only preview. | Keep under `/admin`, require admin auth and CSRF on writes. |
| `dev-local` | Development, setup, legacy template, demo, or local-only utility. | Move outside public web root, block in Apache, or guard with `APP_ENV !== 'production'`. |

## Production Public Routes

| Route or file | Label | Purpose | Notes |
| --- | --- | --- | --- |
| `/` -> `index.php` | `production-public` | Homepage with hero, featured content, CTAs, reviews, and footer links. | Core conversion page. |
| `/about-us`, `/why-choose-us`, `/faq`, `/reviews`, `/contact` | `production-public` | Static public business, FAQ, review, and inquiry pages. | `contact.php` is a key lead endpoint and should stay public. |
| `/destinations`, `/destinations/{slug}` | `production-public` | Destination listing and detail pages. | Listing includes route-session/wishlist behavior. |
| `/experiences`, `/experiences/{slug}` | `production-public` | Experience listing and detail pages. | Includes mood/category and related-content behavior. |
| `/itineraries`, `/itineraries/{slug}` | `production-public` | Itinerary listing and detail pages. | Core private route discovery and conversion surface. |
| `/tours`, `/tours/{slug}` | `production-public` | Tour/service listing and detail pages. | Covers package/service style pages. |
| `/business-partners`, `/business-partners/{category}`, `/business-partners/partner/{slug}`, `/business-partners/apply` | `production-public` | Public partner directory, partner detail pages, and partner application form. | Public visibility is controlled by partner settings in code. |
| `/from`, `/from/{slug}` | `production-public` | Country-origin landing pages. | Database-backed country pages. |
| `/seasonal`, `/sri-lanka-in-{month}` | `production-public` | Seasonal guide and month-specific travel pages. | Used by sitemap seasonal partition. |
| `/blog`, `/blog/{slug}` | `production-public` | Blog listing and article pages. | Uses blog posts and categories. |
| `/{n}-day-itinerary` | `production-public` | Day-count programmatic itinerary pages. | Route accepts 1-2 digit day counts. |
| `/honeymoon-tours`, `/family-tours`, `/adventure-tours`, `/wellness-tours`, `/luxury-tours` | `production-public` | Theme tour landing pages. | Backed by itinerary categories/theme metadata. |
| `/compare` | `production-public` | Side-by-side itinerary comparison. | Marked noindex in code, but intentionally public. |
| `/plan` | `production-public` | Trip planner/filter page. | Marked noindex in code, but intentionally public. |
| `/{slug}` -> `static_page.php` | `production-public` | Database-backed single-segment static pages. | Fallback public content route after explicit rewrites. |
| `/account`, `/account/login`, `/account/google/callback`, `/account/logout` | `production-public` | Customer account access routes. | Public entry points; account pages require customer auth where appropriate. |
| `/account/customize-itinerary`, `/account/plans/{id}`, `/account/plans/{id}/days/{day}/request`, `/account/inquiries/{id}`, `/account/inquiries/{id}/message` | `production-public` | Customer portal routes and customer POST handlers. | Public URL surface with customer auth/CSRF guards. |
| `content-preview.php` | `production-public` | Lightweight published-content preview endpoint used by footer hover previews. | Keep unless preview UI is removed. |
| `track-event.php` | `production-public` | Analytics event ingestion endpoint. | Keep; validate event allowlist/settings separately. |
| `subscribe.php` | `production-public` | Newsletter subscription POST endpoint. | Keep unless newsletter widget is removed. |
| `sitemap.php`, `sitemap_index.php`, `sitemap_partial.php`, `/sitemap.xml`, `/sitemap-{type}.xml` | `production-public` | Sitemap index and partitioned XML sitemaps. | Keep as production SEO infrastructure. |
| `robots.txt`, `404.php`, favicon/logo/image assets | `production-public` | Robots policy, error page, and browser/site assets. | Keep public. |

## Production Admin Routes

| Route or file family | Label | Purpose | Notes |
| --- | --- | --- | --- |
| `/admin`, `admin/index.php`, `admin/login.php`, `admin/logout.php` | `production-admin` | Admin entry, login, and logout. | Keep under `/admin`. |
| `admin/dashboard.php`, `admin/webstats.php` | `production-admin` | Admin dashboard and web stats. | Uses inquiry/page-view data. |
| `admin/inquiries.php`, `admin/inquiry_update.php`, `admin/inquiry_message_save.php`, `admin/customer_plan_request_save.php` | `production-admin` | Inquiry CRM, admin messages, and customer change-request review. | Auth and CSRF protected. |
| `admin/business_partner*.php` | `production-admin` | Partner application review, partner CRUD, status, settings, and delete flows. | Keep as admin-only counterpart to public partner routes. |
| `admin/review*.php`, `admin/reviews.php` | `production-admin` | Review CRUD and review media management. | Keep admin-only. |
| `admin/destination*.php`, `admin/destinations.php` | `production-admin` | Destination CRUD and destination photos. | Keep admin-only. |
| `admin/experience*.php`, `admin/experiences.php` | `production-admin` | Experience CRUD, galleries, related destinations/itineraries. | Keep admin-only. |
| `admin/tour*.php`, `admin/tours.php` | `production-admin` | Tour/service CRUD. | Keep admin-only. |
| `admin/itinerary*.php`, `admin/itineraries.php` | `production-admin` | Itinerary CRUD, itinerary days, categories, route metadata. | Keep admin-only. |
| `admin/country*.php`, `admin/countries.php` | `production-admin` | Country-origin page CRUD. | Keep admin-only. |
| `admin/seasonal_guide*.php`, `admin/seasonal_guides.php` | `production-admin` | Seasonal/monthly guide management. | Keep admin-only. |
| `admin/page*.php`, `admin/pages.php` | `production-admin` | Static page CRUD. | Keep admin-only. |
| `admin/faq*.php`, `admin/faqs.php`, `admin/faq_page.php` | `production-admin` | FAQ management. | Keep admin-only. |
| `admin/service*.php`, `admin/services.php` | `production-admin` | Service content management. | Keep admin-only. |
| `admin/blog_*.php`, `admin/blog_posts.php`, `admin/blog_categories.php` | `production-admin` | Blog post/category/settings management. | Keep admin-only. |
| `admin/media_*.php`, `admin/media_library.php`, `admin/media_picker_api.php` | `production-admin` | Media library, picker, download, upload, and delete flows. | Keep admin-only. |
| `admin/social_*.php`, `admin/social_posts.php` | `production-admin` | Social links, channels, drafts, publish logs/connections. | Keep admin-only. |
| `admin/homepage_manager*.php` | `production-admin` | Homepage hero/media/layout/SEO controls. | Keep admin-only. |
| `admin/site_settings*.php`, `admin/analytics_settings*.php`, `admin/trip_planner_settings*.php`, `admin/weather_api*.php` | `production-admin` | Sitewide settings, analytics config, planner config, weather API/data config. | Keep admin-only. |
| `admin/tripadvisor_widgets_preview.php` | `production-admin` | Admin-linked TripAdvisor widget preview. | Keep only inside `/admin`; root preview remains dev-local. |
| `admin/_*.php`, `admin/assets/*`, `admin/new-admin-template/*` | `production-admin` | Admin includes and admin UI assets/templates. | Keep inaccessible except as included/admin assets. |

## Dev Local / Setup / Legacy Routes

| Route or file | Label | Current exposure | Recommended action |
| --- | --- | --- | --- |
| `test.php` | `dev-local` | Root file; directly web-accessible because `.htaccess` lets real files pass through. | Move to `local/` or delete if obsolete; otherwise guard by `APP_ENV !== 'production'`. |
| `hash.php` | `dev-local` | Root file; directly web-accessible. | Move to CLI/local tooling or delete after admin password setup is complete. |
| `map_preview.php` | `dev-local` | Root file; directly web-accessible, noindex page. | Move to `local/` or require dev environment guard. |
| `map_demo.html` | `dev-local` | Root file; directly web-accessible. | Move outside web root or delete after design reference is no longer needed. |
| `ict_mockups.html` | `dev-local` | Root file; directly web-accessible. | Move outside web root or convert into private design reference. |
| `tripadvisor_widgets_preview.php` at project root | `dev-local` | Root file; directly web-accessible, noindex page. | Prefer the `/admin/tripadvisor_widgets_preview.php` route; block or move the root copy. |
| `install.php` | `dev-local` / setup-only | Root file; directly web-accessible while present. | Treat as setup-only. After install, delete, lock, or guard it so production cannot run installer code. |
| `includes/tour-1.0.0/**` | `dev-local` / legacy template | Under `includes/`; currently blocked from direct web access by `.htaccess`, but still lives inside the web tree. | Move outside public tree or archive outside deploy artifact if not actively used. |
| `ALL-30-Destinations-NewFormat.md`, `vps-references-structure.md` | `dev-local` / source notes | Root markdown files; `.md` is not blocked by current `<FilesMatch>`. | Move to `references/` or block `*.md` in production if Apache serves them. |
| `map_demo.html`, template demo HTML under old vendor-like folders | `dev-local` | Demo/mockup HTML. | Keep out of production deploys. |

## Cleanup Plan

Recommended first cleanup:
1. Add an Apache block for explicit dev-local root files while keeping runtime unchanged for production routes:
   `test.php`, `hash.php`, `map_preview.php`, `map_demo.html`, `ict_mockups.html`, root `tripadvisor_widgets_preview.php`.
2. Lock down `install.php` after setup with either deletion, an environment guard, or an Apache deny rule.
3. Move root markdown/source notes into `references/` or block direct `*.md` access in production.
4. Archive `includes/tour-1.0.0/**` outside the deployed web tree unless an active dependency is found.
5. Re-run route smoke checks and sitemap checks after any move/block change.

Do not block without deeper audit:
- `track-event.php`
- `content-preview.php`
- `subscribe.php`
- `sitemap*.php`
- `/account*`
- `/contact`
- `/admin*`

## Validation Checklist For Future Changes

- `git diff --check`
- `php -l` for changed PHP files
- Public route smoke for any affected production-public routes
- Admin auth/CSRF smoke for affected production-admin handlers
- Confirm dev-local routes return 403/404 in production mode after blocking
