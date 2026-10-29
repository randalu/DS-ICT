# Separate Tour Packages and Day Tour Packages editors â€” 23 October 2026

Implemented and verified locally. This replaces the earlier label-only diagnosis/candidate `dba26d3`; do not deploy that candidate. The owner clarified that both package collections must remain separate.

## Diagnosis and resulting access

- `tours` is the existing Tour Packages collection, with `admin/tours.php`, `tour_form.php`, and `tour_save.php`. Its listing incorrectly queried optional day-tour-style columns without schema guards, causing SQL failure on the older local table.
- `day_tours` is the independent Day Tour Packages collection used by the public Day Tours pages. It had no admin list, full form, or save endpoint, and no Image Manager type. The existing Site Audit mapping also lacked its full-editor link.

Admin navigation now exposes **Tour Packages** and **Day Tour Packages** separately. Each list has full edit and image-edit links. Image Manager has both types and links each back to the correct full form; it continues using separate parent IDs/tables even where numeric IDs coincide.

Local review:

- `http://localhost/island/admin/tours.php`
- `http://localhost/island/admin/day_tours.php`
- `http://localhost/island/admin/image_manager.php?type=tour`
- `http://localhost/island/admin/image_manager.php?type=day_tour`

## Changes

New `admin/day_tours.php`, `admin/day_tour_form.php`, `admin/day_tour_save.php`, and shared `includes/day_tour_admin.php` provide searchable/paginated records and full editing for supported existing content, schedule, transport, pricing, SEO, JSON FAQ/seasonality, publication and related destination/itinerary/experience fields. The form uses existing Bootstrap/Quill/admin components and old-input/flash behavior. New records are drafts until explicitly published.

Text edits preserve current hero/OG images and gallery rows. Retained relationships preserve their existing ordering and itinerary day-number metadata; omitted relationship groups are preserved, and explicit unchecked groups can be cleared. Hero/OG uploads use the existing image validation, limits, optimization, variants and Media Library registration. Failed transactions clean only newly created files. The editor checks a revision token to reject stale full-form submissions, including concurrent image replacements. Reads never create schema; optional fields/relationships are guarded. Save requests require authoritative admin auth, POST and CSRF.

Image Manager adds type `day_tour` for hero, OG and replacement of existing `day_tour_gallery` photos/alt text. Ownership, per-image revision tokens, prepared statements, transactions and image-only preservation apply. Day tours do not gain unrelated video/poster controls. Existing destination gallery library-only upload/public-selection rules and destination-category browsing are unchanged. This change does not add gallery creation/deletion/reordering for Day Tours.

Existing integrations change only the two navigation entries/labels and editor/image links, the optional-column SELECT guard in the Tour Packages list, the Image Manager type/slot mapping/view, and the Site Audit Day Tour full-editor target. No public templates, URLs, customer auth, existing Tour Package save/delete behavior, migrations, storage conventions, JavaScript workflows, or CSS redesign were changed.

Changed/new application areas: `admin/_top.php`, `admin/tours.php`, `admin/tour_form.php`, `admin/day_tours.php`, `admin/day_tour_form.php`, `admin/day_tour_save.php`, `admin/image_manager.php`, `includes/day_tour_admin.php`, `includes/image_manager.php`, `includes/image_manager_view.php`, `includes/site_audit_portal.php`, and `tests/DayTourAdminTest.php`.

## Validation

- Composer validation passed. Full suite: **222 tests, 1,439 assertions, 14 existing skips**, using the established temporary writable-session INI override.
- Focused Day Tour/Image Manager integration suite: **18 tests, 136 assertions**, all pass. Includes separate collections with the same numeric ID, text/image-only preservation, gallery ownership, stale updates, invalid JSON/prices/relationships, draft creation and retained pivot metadata.
- **27 desktop/mobile browser checks** pass: both admin sections, full editor saves, pricing/SEO, draft status, reciprocal image/full-editor links, destination-filtered library saves, existing gallery photo replacement, separate existing packages, older optional-column fallback, draft creation, phone layouts; no uncaught errors or failing requests.
- **Seven additional browser checks** pass for Quill rich-text serialization, explicit publishing, edited public Day Tour rendering, phone width and unpublishing/draft access protection.
- **19 HTTP/storage checks** pass: guest gates, POST/CSRF, full content persistence, retained other collection/gallery/pivots, old input after invalid JSON, stale/forged requests, injected database failure with asset rollback/exact new-file cleanup, genuine image upload, missing optional relationship/core tables and no schema creation on reads. The older-schema fixture probe uses a fresh HTTP revision after temporary schema changes to avoid its own CLI metadata cache.
- Existing dynamic route/POST smoke suite: **73 pass, 0 fail, 0 skip**, with temporary writable sessions and an owner-restricted workbook spool outside webroot; spool removed afterward.
- PHP syntax sweep: **316 files passed**, followed by final changed-file checks. Diff and new-file whitespace checks pass.
- Image-variant dry run: **0 errors**. Full alt lint retains six pre-existing unrelated Media Library alt gaps; no real media metadata was changed.

Browser/HTTP writes used a disposable local schema and synthetic credential-bound session through the real auth checks. No owner passwords, production writes, paid calls, or live-domain tests were used for this combined fix. The fixture database, created uploads, loopback server and browser were removed after validation. Normal Laragon remains available. Evidence and screenshots are under ignored `output/playwright/day-tour-admin-20261023/`.

## Isolated release recheck

The exact 13-path candidate was rebuilt on GitHub main `b884c15`, retaining its quotation links/login-return and Site Audit startup diagnostics. Composer validation and the candidate PHP syntax sweep pass. Full Composer suite: **263 tests, 1,457 assertions, 48 skips** against the disposable local schema, including unavailable newer quotation/storage capabilities. The first run lacked three synthetic policy prerequisites; after seeding the complete four-document fixture, the suite passes without application changes.

All **27 desktop/mobile checks**, **seven rich-text/public checks** and **19 HTTP/storage checks** pass on the isolated candidate. The copied HTTP harness initially referenced its old image-fixture location; correcting that test-only absolute path allowed the real upload/failure probes to complete. Image variants report zero errors. Alt lint on the synthetic schema reports 27 intentionally incomplete fixture library captions; those synthetic assets are not part of the release. Earlier primary-checkout evidence and its six unrelated historical alt gaps remain separate.

## Release boundary

The owner authorized the combined commit and push on 2026-10-23 and requested a one-time server-terminal pull command. This release is isolated from the dirty primary checkout and based on GitHub main `b884c15`. The previous one-line label candidate is superseded and excluded. Newer quotation navigation/login-return and audit diagnostic changes are preserved.

No live checkout update is performed during release preparation. No schema migration is required where the existing Day Tours tables are installed; missing schema remains an explicit capability notice. The owner will run the prepared pull command, then check both admin editors and Image Manager on live. Additional Day Tour gallery-management features can be scoped later.
