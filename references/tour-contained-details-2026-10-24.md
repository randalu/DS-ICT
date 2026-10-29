# Self-contained Tour details — local acceptance, 24 October 2026

The Tour page now owns its route, daily plan, highlights, overnight stops, travel notes, proposed stays, room/meal fields, experiences and practical information. The separate “Detailed planning guides” area and its relationships are removed. The normal `/itineraries/<slug>` collection and Day Tours remain independent.

## Content and administration

- All eight local Tour records have programmes; seven published Tours contain 78 daily cards. The unpublished historical Safari record stays unpublished, with its existing redirect preserved.
- Useful content from six old root duration pages was transferred into the matching Tours before removal. Eleven guide-link records and the old Tour/itinerary association records were removed. All 13 normal itineraries and 127 itinerary-day rows remain unchanged.
- Programme administration supports ordered day ranges, descriptions, highlights, travel notes, stay details and practical sections. It protects against stale saves and incomplete JavaScript initialization. The main Tour form links to this editor.
- Image Manager supports day and stay photographs, captions/alt text, uploads and library selection, with stale-programme protection. Media deletion and image sitemaps recognize programme photographs.
- Existing Tour prices, slugs, publication flags, media assignments and inclusions/exclusions were preserved. The detail hero uses an assigned cover when its legacy hero is empty or a placeholder.
- No named hotels or guaranteed bookings were invented: imported stays identify proposed overnight areas, with hotel/room/meals confirmed in the written quote.

## Retirement and activation

Numeric root `/<N>-day-itinerary` routes and the direct legacy PHP renderer return 410 with noindex. The legacy guide admin/editor cannot recreate pages. Removed URLs are excluded from sitemaps, internal saved links and canonical overrides. Reserved CMS aliases and old intent-map URLs are rejected.

The new guarded SQL migration adds `tours.program_json`. The migration runner then invokes the content transfer, writes a private source backup, removes retired records and installs INSERT/UPDATE rejection triggers. Repeated activation preserves owner-edited programme fields. The empty legacy tables are retained with guards so old seed/replay paths fail instead of recreating the removed content. A database administrator can still explicitly remove these safeguards; they are not a claim of protection against privileged manual overrides.

The schema migration was applied twice locally. Content transfer/removal was performed only in the local database, after inspecting sources and saving the preimage. The real migration-runner activation hook was rehearsed in a disposable database with unrelated SQL migrations marked already applied; this does not certify replaying every historical migration on a fresh installation.

## Verified results

| Check | Result |
| --- | --- |
| Composer validation | Passed |
| PHPUnit | 252 tests, 1,595 assertions, 14 skipped |
| Dynamic route/auth/CSRF smoke checks | 78 passed, 0 failed |
| Isolated HTTP workflow checks | 50 passed, including native programme save, stale protection, both photo slots and a genuine upload |
| Content transfer and protected-field audit | 225 passed, 0 failed |
| Migration activation / owner preservation | 16 passed, 0 failed; repeated after final guard refinement |
| Tour and retained theme release audit | 12/12 passed |
| PHP syntax | 341 files passed; updated audit command separately linted |
| Image-alt lint | Six pre-existing Media Library blanks; no programme alt gaps |
| Image variants dry run | No writes and no errors |
| Whitespace | `git diff --check` passed; existing CRLF notices only |

Visual review used the in-app browser first, then a separate Chrome tab after the owner explicitly selected that fallback. Public daily plans and stay grids were checked at 1,440px desktop, 390px mobile and 768px tablet widths. No horizontal overflow was observed. The tablet photo-less coverage card uses the full width. Programme editor controls were checked at desktop/mobile widths. Native programme save/photo selection was exercised in the in-app browser; Image Manager day-photo selection and Save were exercised in Chrome and returned “Saved successfully.” Console error/warning capture for the reviewed public page and Image Manager was empty. These are desktop browser responsive checks, not physical-phone acceptance.

Browser writes used a disposable database containing public content and a synthetic admin only. Fixture content was reset to the local candidate afterward. The genuine test upload was removed through an exact ownership ledger; no owner media was deleted.

## Evidence and release boundary

Evidence is under `output/tour-contained-20261024/`: `source-manifest.json`, `task-source.diff`, `consolidation-backup.json`, `data-audit.json`, `http-checks.json`, `activation-fixture.json`, `tests-final.txt`, `dynamic-smokes.txt`, `tour-theme-audit.txt`, and desktop/mobile/tablet/admin screenshots. The manifest compares this goal against its captured local baseline, which already contained earlier package-editor/layout/cover changes. It is not a standalone deployment bundle or a clean Git diff.

No staging, commit, push, production data write or deployment was performed. The broad dirty checkout and unrelated work were preserved. Release preparation must assemble the earlier package-editor, layout and curation dependencies together with this goal in an isolated candidate and compare against the actual production baseline before deployment approval.

## Recommended next step and improvements

Prepare that isolated release candidate, then request approval for its exact files and production content/schema activation. Before publishing, review inherited route copy and time-sensitive claims: for example, the Safari programme still copies “guaranteed elephant territory” and specific feeding/entry arrangements from its original itinerary, while the package explicitly says sightings are not guaranteed. This implementation preserves source content; it is not a factual travel-content certification. Confirm the final Safari route variant and hotel shortlist with the owner. Existing missing legacy media and six blank library alts can be repaired as a separate content-cleanup pass.
