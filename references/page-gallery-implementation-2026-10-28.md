# Page layouts and media

Implementation baseline: main is behind the locally recorded origin/main by 32 commits; worktree has substantial existing changes. PHP baseline hashes are in output/page-gallery-20261028/baseline-hashes.json and edited preimages in its preimages folder. No reset, checkout replacement, staging, or production changes.

## Field coverage contract

| Content | Full Admin | Image Manager | SEO Manager | Site Audit | Gallery storage |
|---|---|---|---|---|---|
| Destination | destination editor | destination | destination | destination | destination_photos |
| Itinerary | itinerary editor | itinerary | itinerary | itinerary | page_gallery_images |
| Tour | tour editor | tour | tour | tour | page_gallery_images |
| Day Tour | day-tour editor | day_tour | day_tour | day_tour | day_tour_gallery |
| Experience | experience editor | experience | experience | experience | experience_gallery |
| Activity | activity editor | activity | activity | activity | page_gallery_images; entry galleries retained |
| Blog post | post editor | blog | blog | blog | page_gallery_images |
| Static page | page editor | page | page | page | optional page_gallery_images |
| Blog landing | Blog Settings | blog_landing | blog_landing | blog_landing | optional page_gallery_images |

All types expose Hero, Thumbnail/Cover, OG/Social and descriptions where schema is installed. Migration is prepared, not applied to the existing database. No photographs are assigned automatically.

## Revised owner direction

Owner correction: every page retains its hero; galleries follow the hero midway or near the end. Existing destination/package/experience/activity layouts remain in place. Blog posts and static pages retain their approved layout improvements. No compact-title substitution or gallery-driven hero/header overrides remain. Detail galleries are at the end of content before the footer/global CTA; Destination uses the former gallery region before related itineraries.

The initial compact-title screenshots are superseded and are not acceptance evidence for the revised placement.

## Validation and release gates

- Composer manifest validation passed. Final composer test: 299 tests, 1,994 assertions, 31 skipped; passed.
- Isolated PageMediaTest: 10 tests, 137 assertions; passed. Video-poster and Blog canonical regressions: 13 tests, 62 assertions; passed.
- Additive migration ran twice successfully against a task-created empty synthetic schema. No real rows were copied into that schema. Migration was not applied to the original database.
- Authenticated HTTP acceptance: 44 checks passed, including every public hero-before-gallery order, all nine full-editor integrations, real multipart PNG upload, assignment removal retaining files, guest/CSRF/method/ownership/stale gates, and gallery image sitemap XML.
- Broad PHP 8.3 parser sweep: 380 files, zero parse errors. Changed PHP lint, new JavaScript syntax and git diff --check passed.
- Dynamic smoke: 84 passed, one unrelated records-workbook download failure (zero XLSX bytes). The records implementation was not changed by this task.
- Image variants dry run: 94 scanned, 12 eligible originals, zero writes and zero errors.
- Content lint: 410 findings, including 118 missing page galleries, missing image descriptions and missing existing files. Manual selection remains required. These findings are not silently repaired or populated.
- Initial shared-gallery geometry and admin checks covered 320, 390, 768, about 1024 (measured 1025), and 1440 CSS pixels on several content types. Those screenshots used the superseded compact-hero placement. Revised placement is verified by HTTP across all types; revised Blog screenshot is screenshots/revised-blog-final.png. Full revised desktop/mobile visual acceptance, swipe, keyboard/focus, and console/network acceptance remain OPEN after repeated browser-control timeouts. Do not describe the release as visually certified.
- Synthetic uploads and the exact task-created database were cleaned up; source-row comparisons are in output/page-gallery-20261028/cleanup.json.

## Review artifacts

Task preimages, baseline hashes, test output, screenshots and release-scope hashes are under output/page-gallery-20261028/. The release scope represents this task delta on an already dirty checkout; it is not a complete production bundle. Reconcile pre-existing uncommitted dependencies before creating an isolated release candidate.

Recommended next step: finish revised-layout visual acceptance, then review the additive migration for local activation. Existing-database migration, image selection, commits, push and deployment require their separate owner gates.

## Resume: AI quotas and remaining acceptance

- Request quotas increased tenfold: SEO generation/connection tests 50 to 500 per admin/day; SEO research 10 to 100 per admin/day; alt generation 20 to 200 per admin/hour and 100 to 1,000 site/day. Configured legacy quota overrides receive the same multiplier, including the prior maximum; explicit zero remains zero. Text lengths, upload limits, authentication, request replay checks and concurrent-request guards remain unchanged.
- Shared alt quota service was missing from this older checkout. The committed origin/main service was restored as includes/admin_openai.php with only the shared-limit import and quota comparisons changed. The newer alt UI/API was not merged over this dirty checkout. A separate quota-only candidate against the recorded origin/main is in output/page-gallery-resume-20261028/quota-candidate/ for review; it is not deployed.
- No provider-account quota, billing setting, API key or model was changed. No paid generation requests were made.
- Final Composer suite: 303 tests, 2,010 assertions, 31 skips; passed. Four quota tests / 16 assertions and four reservation-boundary checks passed. Authenticated HTTP suite: 44 checks passed.
- Viewer keyboard focus bug found and fixed: explicit Tab/Shift+Tab wrapping now keeps focus inside the modal. Chrome verified navigation to 3/5, Escape closure and original-opener focus restoration; Shift+Tab trapping passes after correction.
- Revised Blog, Itinerary and Tour layouts checked at exact 320, 390, 768, 1024 and 1440 CSS pixels: hero remains above the lower-page gallery, one H1, no horizontal overflow and no broken gallery photographs. Recorded in output/page-gallery-resume-20261028/responsive-final.json.
- Browser connection again timed out on Experience and subsequent surfaces, including fresh-tab recovery. Full revised visual coverage for the remaining types, native swipe and final admin visual acceptance remain unverified. Prior compact-title screenshots remain superseded.
- Manual image selection and the existing-database migration remain separate gates. No commit, push or production deployment performed.

## CTA ordering correction

Experience and Day Tour galleries now render immediately before their final CTA sections. Their galleries previously rendered after those sections. Blog, Blog landing, Itinerary, Tour, Destination and Static Page galleries were already above the final shared CTA. Heroes and other sections are preserved.


## Release candidate on current GitHub main

The entries above describe earlier local checkpoints. The release candidate is reconciled onto b0e599be3f44462da0a3afd0404bd633164eb8c4 in a separate checkout; unrelated dirty changes in C:/laragon/www/island are preserved and excluded.

- Restored the existing Generate alt text, preview, Apply and explicit Save workflow in shared Hero, Cover, OG, poster and gallery controls. The existing server-only OPENAI_ADMIN_API_KEY remains shared by image alt, SEO and AI Site Audit. Saved Sri Lanka/page/destination/entry references, credential rejection, stale references and no automatic paid retries are retained. No live credential or environment file is included.
- Fixed the new description-field binding found by the browser test. Existing default image descriptions require the explicit checkbox and a current revision before replacement. Browser acceptance used a simulated provider response; no paid generation request or production login was performed.
- Shared-AI tests: 13 tests / 71 assertions. Isolated gallery and integration tests: 13 tests / 198 assertions. Authenticated HTTP: 62 checks including all nine AI context/editor integrations. The browser confirmed generation leaves saved text intact, Apply remains unsaved, and explicit Save persists. Browser console: zero errors and warnings.
- PHP parser sweep: 421 project files with no parse errors. PHP lint and JavaScript syntax checks passed. Full release smoke: 47 passed, zero failures or skips.
- Nine reviewed migrations applied locally on MySQL 8.4.3; rerun reports zero pending. Eleven historical checksum differences remain untouched. No tour-content consolidation, seeding, photo population or publication changes were run. The release runner handles the policy migration MySQL syntax compatibility and canonical LF checksums explicitly.
- Private database backup restored successfully into a disposable schema: 98 tables, 30,305 rows and 10 triggers. The JSON-compatible backup is a separate private file; the initial backup is preserved. The corrected exporter also passed a direct JSON/binary/numeric-zero/quoted-text round trip. No backup data is included in Git.
- Actual existing-file image lint: 410 content gaps. Variant dry run: 94 scanned, 12 eligible originals, zero writes or errors.
- Production remains unchanged. cli/pull_page_media_release.sh verifies the exact release allowlist, source baseline, live edits/collisions and PHP syntax, then provides separate source and schema result markers. Its default mode is --check. --apply performs the requested pull and guarded migrations with private backups. It never changes the server key.

Final release checks: Composer 363 tests / 2,239 assertions / 66 skips, passed; isolated page media 13 tests / 198 assertions, passed. Public responsive checks: 45 across all nine types at 320/390/768/1024/1440; one H1, hero before gallery, no horizontal overflow, no failed local assets or page errors. Admin panel checks: 90 across Full Admin and Image Manager for the same types and widths, with AI controls and no panel overflow. Real touch-event navigation, keyboard arrows, Escape and focus restoration passed. This supersedes the earlier incomplete responsive-check checkpoint; paid provider and authenticated production acceptance remain separate.


## Historical ledger preflight compatibility correction

The first live preflight stopped on 2026-04-19-cta-faq-review-month.sql before code pull or schema apply. The original runner incorrectly required applied status for every recorded historical migration. Status validation now applies only to the nine reviewed release migrations. Non-applied rows outside the release are counted and preserved, alongside historical checksum drift; they are neither replayed nor rewritten. Missing migrations outside the allowlist, non-applied release rows and release checksum mismatches still stop preflight. This does not certify or repair historical migrations.

Regression coverage: eight tests / 35 assertions cover historical states, immutable history, release failure states, missing unreviewed migrations, release checksum mismatches, version-only history and line endings. A disposable MySQL preflight rehearsal also checks that historical states remain untouched and failed release migrations are rejected without ledger writes.

## Complete reconciled release and manual VPS handoff

The latest release is assembled on GitHub commit `3a98ba48f654a35b1ff37be8391615191c4d0a30`, not by overwriting it with the older local checkout. Existing quotation, statistics, trust-publication, owner-content preservation and AI key safeguards remain intact. Local application changes that were already present upstream are retained there; genuinely new source, tools and test coverage are included. Private uploads, filled records/evidence, dependencies, logs, environment files and generated test output are excluded.

- Restored the original shared-key Image Manager workflow in both the original local checkout and the release: Generate, Preview, Apply and explicit Save. Saved Sri Lanka/page/destination references, per-file Activity upload binding, default-alt revision protection and tenfold quotas remain. Library changes refresh AI references and invalidate old previews. No server key is included or displayed.
- Preserved all heroes and the gallery-before-final-CTA position. Fixed the shared panel checkbox sizing and the static-page navigation at intermediate desktop widths. Media asset timestamps prevent stale browser code from hiding restored controls.
- Included all 55 isolated `NEW-SITE/` source/assets/test files as a blocked development preview. Root Apache rules deny that subtree, CLI/test source and the VPS handoff file before real-file passthrough. The nested preview deny remains. No preview route, fixture database adapter or form is enabled on production by this release.
- Preserved additional policy/booking recovery tools, customizer amendment handling, policy-accept routing, legacy destination-caption capacity and Activity hub inventory while retaining newer upstream security and schema fields. Seed/recovery tools are not automatically run by deployment.
- The migration runner explicitly reviews and pins eight existing additive prerequisites in addition to the nine release migrations. It reports all unknown pending migrations together. Failed release states, altered pinned SQL and release checksum mismatches remain blockers. The only historical-checksum compatibility path is the inspected audit-progress column migration: an existing `applied` row is preserved unchanged only when all three columns match type, nullability, default and non-generated attributes. Missing/partial/incompatible schemas are rejected. Other historical records are not replayed or relabelled.
- Read-only local preflight: zero pending migrations, verified progress columns, unchanged migration history. The live baseline has not been inspected or changed during this release. Unreviewed live migrations or overlapping edits still stop preflight.
- Validation: 461 PHP files parse; 27 migration-plan tests / 126 assertions; isolated shared-AI/media tests 28 / 624; authenticated HTTP checks 63; browser workflow/layout assertions 304 with three simulated provider responses and zero page errors. All nine media types retain their controls and public hero/gallery/CTA order. No paid request was made.
- Expanded smoke coverage: 88 passed, zero failures, one explicit environment skip for the private workbook spool. The preview passes 33 PHP syntax checks, 2,205 page assertions, 435 read-only repository assertions and 333 runtime security assertions. Its older all-in-one development report still references optional development checks; that preview is not published.
- `VPS-LIVE-PULL.txt` contains complete root/serveradmin-safe terminal commands. The final documentation commit pins the preceding application commit; only the handoff text may differ after that pin. The helper verifies the exact changed-path allowlist, live source baseline, ignored/untracked collisions and PHP syntax, then runs read-only preflight before pull/apply. Source and database backups remain private. Production execution is manual, not performed by the coding agent.

Final assembled-release Composer run: 404 tests, 2,503 assertions, 68 environment-dependent skips, no failures. The deployment allowlist contains 142 paths relative to the known live baseline, including the earlier gallery/AI commits and this reconciled source. All original local files remain preserved; no reset or blanket overwrite was performed.
