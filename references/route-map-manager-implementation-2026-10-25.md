# Route Map Manager — local implementation, 25 October 2026

Initially implemented locally against the existing dirty checkout. Itineraries, Tour Packages and Day Tour Packages retain separate collections, editors and public URLs. The initial implementation did not commit, push, deploy, migrate the normal database or backfill real routes. The subsequently authorized isolated release is described below.

## Review

The disposable, loopback-only preview is retained for owner review:

- Start a synthetic admin session and open Map Manager: http://127.0.0.1:8151/__fixture?open=1
- Example itinerary: http://127.0.0.1:8151/itineraries/map-itinerary
- Example Tour: http://127.0.0.1:8151/tours/map-tour
- Example Day Tour: http://127.0.0.1:8151/day-tours/map-day-tour

Only explicitly synthetic content is used. The router checks loopback access, pins the disposable schema name and adds noindex headers. It does not read or change owner login credentials. Its manifest/router and HTTP/compatibility checks live under ignored `output/route-map-20261025/`. Browser scripts and screenshots are under `output/playwright/route-map-20261025/`. If the preview server has stopped, restart it from the repository root with `php -d session.save_path=C:/laragon/www/island/output/session -S 127.0.0.1:8151 output/route-map-20261025/router.php`.

## Delivered behaviour

- Map Manager selects a collection/package and provides shared location and default-design sections. Existing package editors link to the portal. The itinerary day editor, Tour programme editor and Day Tour editor embed the same independently saved map controls.
- Map days follow saved written programmes. Itinerary and Tour day ranges expand into individual map days, with optional Day 0 arrival and up to eight ordered stops per day. Day Tours use Day 1 stops. Missing written days and unverified locations prevent displaying a ready route; incomplete selections can be saved as drafts.
- Add, remove and reorder controls update an unsaved preview through an authenticated, CSRF-protected, read-only endpoint. Saving/reloading works without JavaScript for existing selectors and preserves their order. Map failures retain entries separately from package-content old input. Stale forms require explicit reload/compare.
- Reusable places have WGS84 coordinates, source/verification notes, optional destination links, active/verified flags and revision checks. Suggested GeoNames city/airport points require explicit verification and saving. They are not automatic database seeds. Editing a referenced location flags its maps for review.
- Shared versioned defaults are stored with the application; site-specific title, brand label and readable route colour use only `site_settings.route_map_design_json`. Other settings are preserved. Natural Earth geometry and projection parameters are stored locally, with GeoNames attribution and licensing recorded in `assets/maps/`.
- Maps render as server-generated SVG, with no mapping provider, visitor geolocation, API key or remote map/CDN dependency. Dotted curves and arrowheads illustrate travel order, not roads, distances or driving times. Numbered markers show day labels, start/finish and return trips. Nearby stops share a marked cluster; the accessible ordered list retains every stop and full name. Repeated stays do not create self-loop travel arrows.
- Compact cards appear beside overviews on desktop and before the daily plan on mobile. All three public package types support native-dialog enlargement, keyboard dismissal and readable stop lists. Unconfigured, draft, invalid or stale maps display the branded Sri Lanka overview.
- Source revisions track saved day ranges, locations/overnights and route wording. Photo-only changes do not invalidate maps. Programme/route changes preserve the saved document and require explicit review before the route can display again.

## Schema and activation gate

`database/migrations/2026-10-25-route-maps.sql` creates only `map_locations` and `package_route_maps`. Both tables are InnoDB; the migration is rerunnable and performs no data backfill. Equivalent definitions are included in the baseline installer schema. Reads never install map schema or write default map settings.

The migration was applied twice only on the disposable schema. The normal database still has its original map-schema baseline. Normal Tours, itineraries, itinerary days, Day Tours, destinations and settings match the captured pre-task hashes. Existing uploads, protected configuration, slugs, prices and publication flags were not changed by the implementation.

Recommended next: owner review of the preview, then separately approve this exact additive migration on the normal local database. After activation, verify/save the owner's map locations and author routes manually. Do not run the entire historical migration collection or deploy the dirty checkout. Build an isolated release from the scope manifest and task-only diff, reconciling the older dirty baseline with the current release before any commit/push/deployment.

## Validation

- `composer validate`: pass (Composer cache is not writable; validation completed without caching).
- `composer test -- --do-not-cache-result`: **273 cases, 1,644 assertions, 22 skips**. Used the existing writable session INI directory so child PHP processes inherit it. Fourteen skips are existing schema-dependent coverage; eight Route Map storage cases deliberately skip against the normal database.
- Map-specific pure and disposable storage suites: **21 tests / 85 assertions**. Cover grouped days, arrival/order/returns/stays, reference and plain-text validation, unreadable colours, projection consistency, nearby clusters, draft/ready gating, package isolation, transaction rollback, stale map/location/design writes, explicit source review and photo-versus-route changes.
- Disposable HTTP checks: **31 pass**; older-schema checks: **10 pass**, including all three public routes, embedded editors, fallback overview and no read-time schema creation. The compatibility test temporarily renames only disposable map tables and restores them in a finally block.
- `php cli/smoke_post_gates.php --only=dynamic`: **85 pass / 0 fail / 0 skip**, with writable sessions and a temporary private workbook spool outside public directories. The spool was restricted to the current identity/SYSTEM/administrators. Without that environment provision, the existing workbook-download case fails; no workbook code was changed.
- PHP syntax sweep: **351 source files pass**; final new/touched map PHP and both JavaScript syntax checks pass. `git diff --check` passes; the task-only dirty-file diff was reviewed.
- Browser: all three public detail types at measured **320, 390, 768 and 1440px**; no horizontal overflow, clipped labels or unexplained console/network errors. Verified ordered edits, live preview, save/reload, mobile removal, saved locations/default design, enlarged dialogs, changed-programme day creation/review, no-JavaScript rendering and preservation of existing stop selectors on save. All embedded editors and portal sections also fit at 320px.
- Image-alt lint: **six existing missing Media Library alt values** remain. No raster media or variant handling was changed.

Evidence: `composer-final.txt`, `storage-final.txt`, `http-checks.json`, `compatibility-checks.json`, `normal-preservation.json`, `smoke-private-spool.txt`, `image-alt.txt`, `scope-manifest.json`, `acceptance.json`, `task-only.diff` and the browser scripts/screenshots in the directories above.

PNG/PDF export and actual road routing remain later improvements.

## Isolated release and targeted migration handoff

The authorized commit/push candidate was rebuilt from GitHub main `4393bddd091d1e6285896a64196fb78b3650cd15`, using only the 30-file map scope plus three release/activation files. The primary dirty checkout, protected configuration, uploads and Composer manifests were preserved. Production activation is an owner-run terminal step; no server deployment or normal-database DDL was executed while preparing the release.

`cli/pull_route_maps.sh TARGET BASE` accepts full reviewed commit hashes and requires the existing `serveradmin` account, exact application root, main branch and Git origin. It checks release-file edits and collisions, stages a read-only schema preflight before checkout, and invokes the existing pinned fast-forward pull helper. Source backups remain private while checkout runs under umask 022; source permissions are checked afterward. Rerunning at the target still checks/applies the map schema.

`cli/activate_route_maps.php --check` validates the existing map schema and migration history without DDL. `--apply --backup-dir=/private/outside/site` backs up existing map table definitions/rows and the target migration history row, verifies the private backup by readback, and executes only `2026-10-25-route-maps.sql`. Package, settings and media fingerprints must remain unchanged. The command records only that migration, creates no application records, and stops on incompatible schema or checksum history. It does not run the historical migration collection or overwrite concurrent owner edits.

Validation against this current-main candidate:

- Composer validation passes; full suite: **314 cases, 1,672 assertions, 54 skips**. Map-specific disposable suite: **21 tests / 85 assertions**.
- Migration activation rehearsal: **18 checks pass**, covering empty first activation, rerun with sentinel map rows, private backup/readback, unchanged package/settings/media data, invalid schema/checksum rejection and legacy version-only migration history. Staging the helper and migration outside a previous application root also passes preflight.
- Dynamic route/POST smoke: **43 pass / 0 fail / 0 skip**; disposable HTTP checks: **31 pass**. PHP syntax: **374 source files pass**; both map JavaScript syntax checks, Bash syntax and diff whitespace checks pass.
- Browser checks repeated for itinerary, Tour and Day Tour at **320, 390, 768 and 1440px**, including map editing/reordering, save/reload, changed-programme review, reusable locations/default design, dialog keyboard handling, embedded editors and no-JavaScript selector preservation. No unexplained console/network errors or horizontal overflow were found.
- Image-alt lint retains **six pre-existing Media Library gaps**. Normal database preservation was rechecked; release evidence is under ignored `output/route-map-release-20261025/`.

After the terminal reports `ROUTE_MAP_RELEASE_OK`, the recommended next step is authenticated owner acceptance in Admin > Map Manager: verify/save reusable destinations, author one route for each package type, and inspect its compact public map. Road routing and PNG/PDF export can be added later.
