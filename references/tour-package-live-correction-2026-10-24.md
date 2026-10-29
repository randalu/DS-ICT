# Live activation correction and HTTP 500 recovery

The owner approved preserving live Safari/Honeymoon edits, omitting the absent 7-day wildlife itinerary, and activating the remaining release with backups.

## HTTP 500 cause and recovery

The private pull wrapper used `umask 077` for backup protection and accidentally passed it to Git checkout. All 92 release files became mode 0600 and two new source directories became 0700. CLI rendering worked as serveradmin, but the website PHP worker could not read the source. This was a deployment-wrapper defect, not a need to replay every historical migration.

The 92 file hashes were verified before targeted permission recovery. Only those files were restored to 0644 and the new package-cover and database-release directories to 0755. Ownership, environment files, uploads and private backup permissions were preserved. Homepage, admin login, Tours, Itineraries and Day Tours then returned HTTP 200 at the origin; the public homepage also returned 200.

`cli/pull_release.sh` now keeps private packet creation under 077 and runs only Git's checkout/pull under 022. It requires reviewed full target/base SHAs, checks the origin/branch, rejects changed source and untracked collisions, lints changed PHP, saves old source privately, fast-forwards only, and verifies resulting public-source readability. The older private wrapper templates were corrected too.

## Live content reconciliation

The live database has 15 itineraries and 146 day records, eight Tours and ten Day Tours. The original local-only 7-day wildlife itinerary does not exist live, so its cover assignment is omitted; it is not recreated. The corrected plan contains 28 covers. The three extra live itinerary records remain unchanged.

These five values are preserved exactly through both curation and consolidation:

- Wildlife Safari Package: `itinerary`.
- Honeymoon Package: `itinerary`, `sample_itinerary`, `cta_text`, `hero_image`.

The preserved route text is still copied into the Tour's own detail sections, while the original stored fields remain unchanged. Every other existing preimage guard remains active. Invalid or contradictory preservation declarations are rejected. The three additive package migrations remain the only SQL migrations in this activation path; no broad historical runner is used.

## Rehearsal evidence

The actual live public-table definitions and records were imported into a disposable local database without copying accounts, inquiries or credentials. Thirty checks passed: read-only preflight, real migration/activation, repeat execution, all five preserved fields, all 15 itinerary records and 146 original day records, all eight Tours and ten Day Tours, gallery preservation, complete published Tour programmes, retained extra itineraries, protected fields and removal of retired reference records. The new fields started absent, matching production.

Local private evidence is in `output/tour-release-20261024/live-rehearsal.json`; recovery evidence is in `live-permission-fix.json`. The permission preimage is retained outside the webroot on the VPS. Production activation must retain its own private source/data preimages and be followed by field-preservation and public-route checks; a rehearsal alone is not production acceptance.
