# Destination uploads: Media Library first — 16 October 2026

Implemented locally following the owner's correction. This supersedes the earlier automatic gallery-addition behavior described in `destination-gallery-management-implementation-2026-10-16.md`.

## Current flow

1. Open Image Manager → Destination Galleries and select an existing destination.
2. Upload one or several photos to that destination's Media Library. Each upload has a caption/alt text and individual progress/result. Uploads do not add gallery rows, change existing public images, or publish destination records.
3. To feature a photo publicly, use **Select a photo for the public gallery → Choose from Media Library → Add to public gallery**. Uploaded alt text prefills the editable caption. Adding a public gallery photo accepts library selection only; the former direct-upload-to-public API path is rejected.
4. When editing an itinerary hero/day/OG, a tour image, or another supported image card, open Media Library and use **Browse by destination**. The selected category contains all its uploaded media regardless of whether it was selected for a public gallery. The selected category is remembered across image selections on that page. All destinations/all media remains available. Gallery selection remains scoped to its destination plus unassigned assets.

Public gallery caption/order/removal controls remain available. Existing public selections are preserved. Replacing an already assigned public image still requires the existing explicit Save this image action. No real images were removed from public galleries as part of this change.

## Implementation

- Added authenticated/CSRF-protected POST `action=library_upload` with destination ID, required caption, request ID, and exactly one file. The service validates an existing destination and genuine supported image, reuses existing limits/optimization/variants, and atomically registers/tag/captions its Media Library asset. It does not require the public gallery table.
- Added per-item random upload identifiers and a bounded session receipt cache for repeated identical requests; the same recent request returns its original asset, and reuse with different content is rejected. Queue success items are not resubmitted; ambiguous outcomes remain paused for library review.
- Library GET requests can filter exactly by `destination_id`; public gallery pickers explicitly include unassigned images. Existing image/video restrictions still apply per card. Caption metadata is returned with optional-column guards.
- Changed the upload/public-selection labels and layout, added a touch-friendly destination selector to the shared modal, retained preview/progress/unsaved warnings, and kept portal asset versioning.
- No new schema, authentication change, commit, push, deployment, live-domain test, or real data modification. Existing unrelated checkout changes are retained.

## Validation

- Composer validation passed. Full suite: **205 tests, 1,337 assertions, 14 existing skips**, using the established temporary writable-session override. Focused Image Manager suite: **12 tests, 85 assertions**, all pass.
- Dynamic route/POST gates: **73 passed, 0 failed, 0 skipped**, using the existing session override and a temporary owner-restricted workbook spool outside webroot; that spool was removed.
- **14 mobile/desktop browser checks** pass: two uploads leave both admin/public gallery counts unchanged, clear library-only feedback, destination filtering exposes both uploads for an itinerary day, another destination is excluded, selected category persists, itinerary image saving works, explicit gallery selection alone adds a public photo, caption prefill, tour-category filtering, phone width, and no runtime/failed network responses.
- **15 HTTP/storage checks** pass: library-only writes and unchanged parent/gallery, destination/alt metadata, duplicate receipt handling, reused-ID rejection, nonexistent destination, CSRF, invalid images, old direct-public upload rejection, injected destination-tag write failure with asset rollback/exact file cleanup, independence from missing gallery table, and expired sessions.
- Changed PHP and JavaScript syntax checks passed. Diff whitespace check passed. Destination-photo alt lint passed; existing six unrelated Media Library alt gaps remain unchanged. Image variant dry run reported zero errors.

Writes were confined to a disposable local database through a loopback PHP harness with the real application auth checks and synthetic credential-bound session. Owner login/physical-phone review is the next step. The fixture database and generated uploads are cleaned afterward; normal Laragon remains available.

Evidence is in ignored `output/playwright/destination-gallery/library-only-*` files, including browser/HTTP results and screenshots of the upload queue and destination filter. Previous evidence in that directory describes the initial behavior and is historical.

Recommended next step: review the revised local flow on a phone. Any commit, push or deployment remains a separate approval.
