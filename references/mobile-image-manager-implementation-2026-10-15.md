# Mobile Admin Image Manager — local implementation

Status: implemented, verified through Laragon/Apache, and accepted by the owner. Commit, push and production pull are authorized; release results are recorded separately.

## Delivered

Open `admin/image_manager.php`, or use **Media & Social → Image Manager** in the normal admin. The portal shares admin credentials and the normal credential-bound session. Guests return to the portal after login through the fixed `return=image_manager` destination; arbitrary return URLs are ignored.

- Searchable, paginated destinations, itineraries and existing admin tour packages.
- Destination hero/video/poster, cover, OG and gallery replacement/addition with caption/alt text.
- Itinerary hero/video/poster, OG and all existing day-entry images together. Day ranges remain grouped; missing day entries are reported.
- Tour-package hero and OG images; the separate public Day Tours collection remains outside this version.
- Device uploads and searchable/paginated library selection, previews, progress, individual saves, cancel/retry, stale-edit feedback and unsaved-selection warnings.
- Mobile layout tested at 390 × 844; desktop tested at 1440 × 1000.

The portal updates only the chosen media assignment and required media metadata (including gallery caption on replacement). Detailed content, prices, relationships, day descriptions, publication flags and gallery order are preserved. New gallery entries append. Previous files are retained. Hero replacement clears the previous video poster; save a new poster through its own card after saving a video.

Image uploads are capped at 20 MB and video uploads at 100 MB, reduced to account for lower PHP limits. Image dimensions and available processing memory are checked before decoding. Existing MIME allowlists, image optimization and responsive variants are reused. Newly uploaded files register in the existing library.

## Implementation boundaries

New application files:

- `admin/image_manager.php`: standalone lightweight portal shell and server-rendered record/image cards.
- `admin/image_manager_api.php`: authenticated JSON library/save endpoint.
- `includes/image_manager.php`: explicit content/field mapping, revision tokens, ownership checks and transactional image-only saves.
- `assets/css/image-manager.css`, `assets/js/image-manager.js`: scoped responsive styles and interactions.
- `tests/ImageManagerTest.php`: transactional integration and validation tests.

Existing integration changes are one navigation entry in `admin/_top.php`, the fixed login return in `admin/login.php`, and credential stamps in the existing smoke runner's synthetic admin sessions. Pre-existing changes in the navigation and smoke runner are retained.

`includes/auth.php` and its dependency `includes/admin_credentials.php` match the recorded approved `origin/main` release baseline. No reset is performed. Older local admin sessions without credential stamps must log in again. No customer authentication code is changed.

Portal reads do not create schema. Missing optional tables/columns disable or omit the affected capabilities with a notice. Saves require existing InnoDB content/media tables so rollback guarantees hold. Parent locks and conditional image-field updates protect against concurrent portal/full-admin edits. Failed uploads roll back media registration and remove only their newly created original/variants.

## Validation evidence

- Composer manifest validation passed.
- Full PHPUnit suite: **192 tests, 1,251 assertions, 14 skips** using a writable PHP session directory. The 14 skips belong to existing tests requiring their own disposable database; no portal test is skipped.
- Targeted portal suite: **8 tests, 59 assertions** passed.
- Dynamic route/POST gates: **70 passed, 0 failed, 0 skipped** after giving the smoke run its required private temporary workbook spool and writable PHP session directory.
- **17 HTTP/rollback checks** passed: guest protection, fixed login return, method/CSRF rejection, unsupported/oversized uploads, forged day ownership, stale/duplicate saves, and injected database failure with old-record preservation, rolled-back media registration and exact newly-created-file cleanup.
- Older-schema checks passed in a disposable schema: missing gallery, day-image column, video metadata and media library; no schema installation on GET.
- **35 positive browser assertions** passed: upload/library preview/cancel/save, gallery add/replace, video/poster/image transitions, library pagination, search, 7/12-day/grouped itinerary cards, persisted Day 2 replacement, and mobile overflow checks. No uncaught errors or failing network responses in these workflows.
- Negative browser paths exercised stale-edit refresh, interrupted-request retry, unsaved-change warning and expired-session recovery. Their intentional 409, aborted request and 401 network errors were observed. A follow-up probe raced an already completed scripted navigation and timed out; subsequent browser checks and final upload/persistence verification passed.
- Local public destination, itinerary and tour rendering returned HTTP 200, valid local OG URLs and no broken replacement images, uncaught errors or failed network responses.
- PHP syntax sweep: **299 files** passed. JavaScript syntax and `git diff --check` passed. Image-alt lint found no issues. Image-variant dry run reported no errors. Lower 2 MB PHP upload/request limits were also verified.

The standard unconfigured `composer test` first reproduced the existing `C:/laragon/tmp` permissions problem. The successful full run used a temporary `PHP_INI_SCAN_DIR` containing `session.save_path="C:/laragon/www/island/output/session"`, which also applies to isolated PHPUnit subprocesses. No PHP configuration or environment file was changed.

Browser and HTTP evidence is under ignored `output/playwright/image-manager/`. Tests used synthetic records in a disposable MySQL schema and a loopback PHP server; no production data or live-domain checks were used. Test-created uploads and the disposable schema are cleaned after verification.

## Laragon / Apache follow-up acceptance

The original port-80 blocker is resolved. With owner approval, enabled only `proxy_module` and `proxy_http_module` in `C:/laragon/bin/apache/httpd-2.4.66-260223-Win64-VS18/conf/httpd.conf`; its existing `zro.test` virtual host required those modules before Apache could start. A configuration backup is in `output/playwright/image-manager/laragon/httpd.conf.before-proxy-modules`. The permanent server delta was checked against that backup and contains exactly those two uncommented lines. Apache syntax passes and normal Apache is left running.

The working portal entry is **http://localhost/island/admin/image_manager.php**. Its guest redirect and normal login page were checked on the ordinary local site. For authenticated write acceptance, a temporary loopback-only Apache listener on port 8110 used the same application files, `.htaccess`, mod_php and normal login against a disposable schema. A synthetic login credential was generated and retained only in process memory; only its hash entered that temporary database.

Actual Apache testing found a CSP issue hidden by the PHP development server: pending video object URLs were blocked by the default media policy. Added a portal-only `<Files "image_manager.php">` header override in `.htaccess` to allow `media-src 'self' blob: https:`. Other pages retain their existing policy. The existing unrelated `.htaccess` changes are preserved.

After that correction, **42 desktop/mobile browser assertions** passed with **zero console errors, uncaught runtime errors or failing network responses**. They include actual login and return to the portal, shared normal-admin session/navigation, uploads/library selection, gallery editing, video/poster transitions, persisted day replacements, seven/twelve-day/grouped entries, search and pagination. A separate check played a selected video and verified decoded dimensions of 320 × 180 and duration of one second under the real Apache CSP. The portal suite still passes **8 tests / 59 assertions**; Composer validation, Apache syntax and diff whitespace checks pass.

The configured `island.test` HTTPS route failed with curl/Schannel, while Chrome with certificate errors ignored connected and followed the application's configured redirect to the localhost base. Strict HTTPS/certificate acceptance was not certified or changed. Physical-phone acceptance and production deployment remain separate gates.

Evidence is under `output/playwright/image-manager/laragon/`. The temporary virtual host/listener, disposable database and all test-created uploads were removed; normal Apache was restarted and checked afterward.

Recommended next step: owner acceptance using the normal local admin login, then an isolated release candidate for separately approved deployment. Cropping, bulk saves, gallery removal/reordering and replacement history remain later improvements.

## Production CSP compatibility correction

Production inspection confirmed LiteSpeed does not implement Apache `Header edit`. The portal scope now sets the full existing policy with only `media-src 'self' blob: https:` added. The public/login policy remains unchanged. This keeps video object-URL previews compatible with both local Apache and production LiteSpeed without changing hosting configuration. Reference: https://docs.litespeedtech.com/lsws/configuration/ .
