# Package editor and cover-image integration — 24 October 2026

Local implementation for the approved Tour/Itinerary layouts. The three collections remain separate. No package content was merged or moved, and no production changes, commits or pushes were made.

## Delivered behaviour

| Area | Implementation |
| --- | --- |
| Full editors | Existing package fields, independent covers, Tour coverage cards, itinerary traveller types/endpoints, structured seasons, itinerary-day highlights, day-tour gallery management and Tour planning-guide links. |
| Image Manager | Separate cover upload/library selection and alt text for all three collections; Tour coverage photographs/alt text; existing heroes, social images, itinerary-day images, video posters and day-tour gallery images retained. Image-only saves preserve other content. |
| SEO Manager | Day Tours added. Package wording, nested Tour coverage wording, itinerary-day wording/highlights, gallery descriptions and planning-guide link labels are selectable edits. Complete stored context and direct editor links are available. |
| Site Audit | All package field names/presence/lengths, child and relationship fields, covers and Tour coverage media enter the inventory. Missing cover assignments/descriptions, invalid JSON and editorial markers are checked. Day Tour findings link to their own full, image and SEO editors. |
| Public site | Tours added to the shared desktop/mobile top navigation, with its own active state. Package cards prefer their independent covers throughout existing homepage sections, listings, related cards, comparison, theme and day-length pages. The image sitemap includes covers and Tour coverage photography. |

Image Manager retains its media role; fixed facts, prices, locations, relationships, URLs and publication are changed in the full editors. SEO Manager provides complete reference context for those fields and edits selected wording. Photo descriptions require manual review rather than an AI suggestion without inspecting the photograph. Site Audit inventories/checks data but does not establish business or travel-fact accuracy.

### Independent covers

Each collection now has `cover_image`, `cover_alt` and `cover_media_asset_id`. New covers are still images, selectable by upload or Media Library, with required meaningful alt text. Clearing a cover retains the underlying library file. Cover, detail hero and social-sharing image remain independent.

Existing records keep their current photography as a fallback until a separate cover is selected. No automatic image assignment or owner-content backfill was performed. Homepage Itinerary and Day Tour cards use the selected covers; Tour package cards use them wherever that collection is currently displayed.

### Full editing and preservation

- Itinerary traveller checkboxes submit natively, including without JavaScript. Route endpoints accept custom places. Richer season JSON and additional keys survive ordinary saves; complete structured replacements are explicit.
- Itinerary-day highlights are editable as a validated text list. Days retain their existing numbering/ranges, summaries, overnight locations, travel times and images.
- Tour coverage keeps exact locations, experience descriptions, proposed/optional status, destination/experience links, photo selection, alt text and order.
- Day-tour gallery controls add/edit photos and descriptions, set display order and remove gallery entries while retaining library assets. Parent ownership and stale revisions are checked.
- Tour planning-guide controls cover both paths, both labels and active state. Package ownership/IDs/timestamps remain system-managed. SEO edits the labels while preserving paths and state; history restoration rejects changed structure.
- Ordinary relationship saves retain unchanged relationship IDs and metadata. Older forms that omit new cover fields preserve the stored cover.
- Full package saves reject stale parent revisions. Coverage and per-image saves have their own revision checks. SEO history also checks the relevant child structure.
- Combined hero/cover uploads commit together. Legacy itinerary media schema preparation happens before the content transaction; failed saves clean up newly returned media files, without treating existing library selections as new files.

## Field coverage audit

The rendered forms and SEO API were compared with the installed schema, including automatic/system fields. There were no uncovered parent or child columns.

| Record | Stored fields accounted for |
| --- | ---: |
| Itineraries | 42 |
| Tours | 33 |
| Day Tours | 35 |
| Itinerary days | 12 |
| Day-tour gallery | 6 |
| Tour planning-guide links | 9 |
| Total | 137 |

IDs, ownership fields, timestamps and media identifiers are read-only or derived through their appropriate controls. Tour coverage JSON was additionally verified at the card-field level. The authenticated **All fields** review page exposes the complete stored package/child/relationship context and links to the appropriate editor.

## Local migrations and data preservation

Applied locally, twice each to verify idempotence:

- `database/migrations/2026-10-24-tour-coverage.sql`
- `database/migrations/2026-10-24-package-editor-media.sql`

The second migration adds independent covers, itinerary traveller/endpoints, day highlights and the previously unsupported Tour route field. Public/editor reads remain guarded for installations that have not applied the migrations.

Before/after hashes of all original fields matched for 13 itineraries, 8 tours, 10 day tours, 127 itinerary days and 6 day-tour gallery entries. The hashes were checked again after validation. No original package fields or rows were changed by the migration/test work. Synthetic browser/HTTP content used a separate disposable database on loopback port 8140.

## Validation

- Composer manifest valid. **243 PHPUnit tests / 1,562 assertions**, with **14 existing skips**. **333 PHP files** passed syntax checks.
- The **15 focused package tests** cover independent media, omission/clear semantics, stale revisions, nested wording/history, protected facts, gallery operations, guide ownership/paths, structured seasons, native traveller submission, existing-library cleanup protection and image-sitemap coverage.
- **42 HTTP checks passed**, including all three full-editor cover saves, genuine uploads, hero/social preservation, omitted fields, stale parent rejection, combined hero/cover commits and failed-upload rollback/file cleanup.
- **9 additional endpoint checks passed** for the gallery/guide editors and authenticated field review, including CSRF and invalid-record handling.
- **73 dynamic smokes passed, 0 failed, 0 skipped**, using temporary writable sessions and an owner-only workbook spool outside the webroot; the spool was removed afterward.
- **9 JavaScript audit tests passed**. New scripts passed syntax checks; the task diff passed whitespace checks.
- Browser checks used measured CSS widths **320, 390, 768 and 1440**, with additional desktop-navigation checks at **1023 and 1200**. Homepage/listing covers, detail/related cards, mobile navigation and the affected editor layouts showed no horizontal overflow or PHP errors.
- Browser interactions verified Image Manager library/alt saves and a genuine device-file upload, SEO selected-field saves including Day Tours and guide wording, full itinerary traveller/endpoints and day-highlight saves, gallery caption/order saves, planning-guide creation and public rendering. The uploaded cover was confirmed in both the UI and database before its test file was removed. Transient browser-observation timeouts were resolved by inspecting the saved state before retrying.
- The native audit host passed Chromium/private-storage preflight. A disposable local audit completed **30 pages and 511 resource observations**, and its cover findings exposed the correct full/SEO/image/all-field actions. It reported partial coverage and expected findings from the intentionally limited fixture routes, noindex headers and zero external-link probes; this is integration proof, not a clean production audit. The inventory was also inspected after adding the planning-guide fixture.
- Image-alt lint retains **six existing missing Media Library descriptions**. No new cover, coverage or gallery alt findings were introduced. Image-variant dry run: **87 scanned, 0 errors, 0 files written**.

## Evidence and review

Evidence is under `output/package-editor-20261024/`:

- `composer-final.txt`, `php-syntax.json`, `js-tests.txt`, `dynamic-smokes.txt`
- `http-results.json`, `endpoint-results.json`, `field-coverage.json`
- `local-migration-results.json`, `original-data-preserved.json`
- `browser-upload-result.json`, `audit-evidence.json`
- `browser-checks.json`, `completion-audit.json`
- `image-alt.txt`, `image-variants.txt`, `scope-manifest.json`, `task-only.diff`

The existing dirty checkout was preserved. The task-only diff is relative to saved local baselines, so it can be reviewed separately from unrelated work. Production release still requires an isolated candidate and explicit deployment approval.

Recommended next step: review the local previews, select each package’s cover and fill/merge the intended Tour coverage manually, then prepare the isolated release.
