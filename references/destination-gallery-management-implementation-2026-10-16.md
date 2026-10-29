> Upload behavior revised: uploads now go to the destination Media Library only. Public gallery inclusion requires a separate selection. See `destination-media-library-upload-flow-2026-10-16.md` for the current flow and verification. The original implementation record below is historical.

# Destination gallery management — local implementation, 16 October 2026

Status: implemented locally for review. No commit, push, deployment, production upload, or live-domain test was performed.

## Delivered

Open `http://localhost/island/admin/image_manager.php?section=galleries`, sign in with the normal admin account, and choose an existing destination. Published and draft destinations have status badges and remain in their existing publication state. The same gallery component is also below the destination's other Image Manager cards.

- Multiple device photos, per-photo previews and required 1–300-character captions/alt text, explicit sequential upload queue, individual progress/results and retry of failed items.
- Per-photo replacement through the existing upload/library cards; independent caption editing; Move up/down and explicit Save order; confirmed removal from the gallery.
- Gallery uploads register in Media Library and receive the selected destination assignment and uploaded asset alt text atomically. Library browsing includes that destination and unassigned images; another destination's assets are excluded and rejected again during saving. Unassigned selections receive the destination assignment. Existing gallery photos without library records remain visible.
- Removal deletes only the gallery entry. Files, library assets, and other usages remain intact. No migration, schema creation, historical retagging, or filesystem relocation is performed.
- Authenticated POST/CSRF gates, parent/child ownership checks, whole-gallery revision tokens, exact reorder membership validation, InnoDB transactions, rollback and request-created-file cleanup.
- Successful queue items are never resent. A lost/ambiguous response pauses the queue and requires gallery refresh/review; uncertain items are excluded from automatic retries and can be removed from the queue after review.
- Unsaved selections/captions/order trigger leave warnings; refresh warns before discarding work and retains scroll position. Queue captions are locked during submission.
- Missing optional gallery/library-destination schema produces an explicit unavailable notice without installation on reads.

The current public destination template did not render `destination_photos`. Added a guarded, lazy-loaded responsive gallery using existing card/gallery styles, saved ordering, captions and alt text. Draft destinations remain private. Existing URLs and other page sections remain unchanged.

## Files and interfaces

Gallery changes touch `admin/image_manager.php`, `admin/image_manager_api.php`, `includes/image_manager.php`, `assets/js/image-manager.js`, `assets/css/image-manager.css`, and `tests/ImageManagerTest.php`. New `includes/image_manager_view.php` shares card/gallery markup between initial render and authenticated refresh. New `assets/js/destination-gallery.js` manages the queue and gallery controls. `destination.php` and a scoped rule in `assets/css/detail-redesign.css` render the public gallery. Portal scripts/styles use modification-time versioning to prevent cached older JavaScript from breaking the new controls.

The existing API adds authenticated GET `action=gallery&id=...`, optional destination filtering on GET `action=library`, and POST `gallery_caption`, `gallery_remove`, `gallery_order` actions. Metadata actions use the destination ID, CSRF and current gallery revision; caption/removal also use the child ID, and order supplies a JSON list of every gallery photo ID exactly once. Existing image-save requests retain their interface.

Existing dirty work is preserved. In particular, the pre-existing `seo_manager` login-return allowlist in the shared service is not part of this extension and must be separated when preparing a release. Admin/customer authentication code was not edited.

## Validation

- Composer validation: pass.
- Full Composer test suite: **204 tests, 1,334 assertions, 14 existing skips**, using the established temporary `PHP_INI_SCAN_DIR` session override. The unconfigured command reproduces the existing unwritable `C:/laragon/tmp` problem; no PHP installation configuration was changed.
- Focused Image Manager tests: **11 tests, 82 assertions**, all pass. These include gallery destination assignment, unrelated content preservation, cross-destination rejection, caption/order/removal, stale actions, invalid reorder membership, forged child IDs, and retained shared assets/files.
- Dynamic POST/route smoke checks: **73 pass, 0 fail, 0 skip**, with writable sessions and an owner-restricted temporary workbook spool outside webroot. The spool was removed afterward. The initial run without that spool had the existing workbook-download failure.
- **29 portal browser checks** and **7 public/scroll/preview checks** pass at 390 × 844 and 1440 × 1000. Covered selection/search, drafts, legacy/empty galleries, library scoping, captions, ordering, removal, queue completion/partial failure, duplicate prevention, lost responses, unsaved-queue warning, previews, public caption/order persistence, and horizontal overflow. No uncaught runtime errors; network/console errors in the negative suite correspond to deliberately injected 419/422/409 responses and an aborted response.
- **20 HTTP/rollback/compatibility checks** pass: injected gallery-insert failure leaves the gallery unchanged, rolls back asset registration/tagging, and cleans only new files; valid upload metadata; unchanged parent record/publication state; foreign assets/nonexistent parents; replay rejection; unsupported/oversized files; expired session; missing destination column/gallery table; no tables or columns created by reads; public older-schema fallback.
- PHP syntax sweep: **308 files pass**, plus the final changed portal/public PHP checks. Both JavaScript files pass syntax checking. Repository diff check and scoped checks including new/untracked source pass.
- Destination-photo alt lint: pass. Full alt lint reports **six pre-existing Media Library assets** without alt text (IDs 92–97); those real records were left unchanged. New gallery uploads save asset alt text from their caption.
- Image variant dry run: **0 errors**.

Functional writes used synthetic records in a disposable local MySQL schema and a loopback PHP server. The ordinary Laragon/Apache portal guest route was also checked. Authenticated browser tests use the normal application auth check with a synthetic credential-bound session; actual owner login and physical-phone acceptance remain review steps. No shared Apache configuration was changed.

Evidence: ignored `output/playwright/destination-gallery/` contains browser/HTTP scripts and results, source review diffs, Composer/smoke logs, and desktop/mobile screenshots. The disposable database and its generated uploads are removed after verification; the normal Laragon site remains available.

Recommended next step: review the local portal on a phone, then separately approve an exact-scope release candidate if satisfied. Cropping and replacement history remain later improvements. The previously documented production video-preview CSP issue is outside this gallery extension.
