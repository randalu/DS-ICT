# Tours and Itineraries layout implementation — 24 October 2026

Implemented locally. Tours, Itineraries and Day Tours remain separate. No package/content merges, route redirects, slug changes, price transfers, publication changes, commit, push or deployment were performed.

## Review the layouts

The loopback-only preview uses a disposable schema and explicitly synthetic content, with existing local photographs:

- Tour: http://127.0.0.1:8138/tours/layout-tour-901
- Itinerary: http://127.0.0.1:8138/itineraries/layout-classic
- Listings: http://127.0.0.1:8138/tours and http://127.0.0.1:8138/itineraries

The preview server is intentionally retained for owner review. Its router, setup, fixture identity and verification artifacts are in ignored `output/tour-layout-20261024/`. It binds only to loopback; its synthetic credential-bound admin session is confined to that fixture. No owner credentials were read or changed.

## Delivered

- Tours use a photo-led hero, section navigation, ordered location/experience coverage, proposed/optional labels, package inclusions/exclusions, vehicle/pricing notes, secondary sample-route disclosures, linked itineraries, FAQs and enquiry controls carrying the tour name.
- The coverage editor supports adding/removing/reordering cards, exact locations, plain-text descriptions, destination/experience links, existing Media Library images and mandatory image alt text. It reuses the schema-safe authenticated Image Manager library endpoint. Public reference lookups are batched rather than queried once per card.
- Coverage saves validate data and references, preserve existing coverage when omitted or JavaScript is unavailable, support explicit clearing and reject stale coverage revisions inside the existing transaction. A stale form retains its entered cards and offers an explicit Load saved coverage recovery action. Media used by coverage cannot be deleted through the library's delete endpoint.
- Itineraries retain their records, daily plans, relationships and customisation flows. The refreshed presentation adds larger native day disclosures, consistent image crops, visible overnight/drive facts and accessible expand/collapse-all controls. The old exclusive accordion and split expansion panel no longer conflict. Mobile enquiry retains the itinerary name.
- Both listings retain their collections and filters. Prices now use their own stored amounts, currency and unit consistently; no hard-coded per-person/per-couple suffix is added. Card images navigate rather than being intercepted by the shared photo viewer. Standalone coverage images retain the viewer.
- Day Tours and existing theme/category routes retain their implementation. Existing business wording and contradictory package schedules/inclusions were not rewritten; the owner will organise those manually.

## Migration and release boundary

`database/migrations/2026-10-24-tour-coverage.sql` adds nullable `tours.coverage_json`. It is rerunnable on MySQL/MariaDB, uses an information-schema check and executes no content backfill. It was applied twice only on the disposable schema. The normal local database was verified to lack this column after testing; production was not contacted.

On an older install, public tours render normally and the admin form explains that coverage editing requires the migration. Reads never install the new column. Existing editors can save other fields without clearing coverage.

Recommended next step: review the two local layouts, then separately authorise the normal local migration and an isolated release candidate. Coverage cards should be authored manually after that migration. Do not deploy the entire dirty checkout: the scoped source list and hashes are in `output/tour-layout-20261024/scope-manifest.json`. Compare `task-only.diff` against the saved pre-task dirty-file snapshots when preparing release hunks. There are 16 scoped application/test/migration paths plus this implementation note.

The additive column can remain in place if the code is rolled back. Do not drop it or automatically delete the owner's future coverage data.

## Validation

- `composer validate`: pass.
- Full `composer test -- --do-not-cache-result`: **228 tests, 1,472 assertions, 14 existing skips**, with the temporary session INI directory so child PHP processes also use the writable session path. Default `composer test` reproduced the existing `C:/laragon/tmp` permission failure. The full suite's ordinary integration fixtures require the existing local data; an empty disposable clone is not a substitute for those unrelated fixtures.
- New focused coverage suite: **6 tests, 33 assertions**. Covers order, reference resolution, explicit empty data, malformed/oversized/incomplete inputs, forged IDs, required alt text, broken stored JSON and revisions.
- HTTP/storage checks on the disposable database: **21 pass**. Includes auth/CSRF, invalid data and old input, stale writes, omitted/no-JavaScript coverage preservation, explicit clearing, restored content, unchanged prices/publication/media/other packages, draft-link suppression, deletion protection and older-schema rendering with no read-time coverage migration.
- `php cli/smoke_post_gates.php --only=dynamic`: **73 pass, 0 fail, 0 skip**, with temporary writable sessions and a temporary owner-only workbook spool outside webroot. The spool was removed afterward. Without that spool the existing workbook-download case fails; this was an environment issue, not a layout change.
- PHP syntax sweep excluding dependencies, generated output and protected uploads: **323 files pass**; final touched-file syntax and both new JavaScript syntax checks pass.
- Real browser checks: both listings and both detail pages at measured CSS widths **320, 390, 768 and 1440**; no horizontal overflow or PHP errors. Verified editor save/reload, image selection, add/remove/order/optional cards, missing-image presentation, mobile dialog, stale-save recovery, daily expansion/collapse, duration filtering and card-image navigation. Inspected console/resource errors and loaded images; no unexplained failures were observed. Viewport override reset after checks.
- Image-alt lint retains **six pre-existing missing Media Library alt values**. No owner media metadata was changed. Image-variant dry run: **0 errors, 0 files written**.
- Task-only diff reviewed against the pre-existing dirty-file snapshots. Scoped/new-file whitespace checks pass. Existing unrelated dirty work was retained.

Evidence: `composer-final.txt`, `http-checks.json`, `browser-checks.json`, `smoke-private-spool.txt`, `image-alt.txt`, `image-variants.txt`, `scope-manifest.json` and `task-only.diff` under `output/tour-layout-20261024/`.

Later improvements can include tour coverage galleries, richer route maps and owner-reviewed reconciliation of old package schedules/inclusions. These are outside this layout release.
