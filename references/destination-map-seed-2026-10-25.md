# Destination map location import — 25 October 2026

Requested after route-map release `89e8b40b631430219e1cf58efcd12b0ba748f736` was deployed and its additive schema activated. This is a data import, with no new database migration or changes to public/admin pages.

## Matching and preservation

The pinned GeoNames Sri Lanka catalog contains 41 reviewed city/area points. Source dump: https://download.geonames.org/export/dump/LK.zip, retrieved 25 October 2026, CC BY 4.0. Each entry retains its GeoNames ID, source URL, feature code and reference-point description. These are approximate positions for the illustrative SVG map, not entrances or road navigation.

The importer reads the actual destination IDs/names/slugs from the selected database. No local destination IDs are hardcoded. Exact names and explicit aliases are recognized with case/punctuation normalization; known conflicting or renamed slugs, duplicate destination identities and unknown places require review. Existing linked locations are preserved, including inactive/unverified/custom records. Existing matching unlinked map locations require manual linking instead of creating a duplicate.

The local destination inventory contains 43 records: 41 have catalog points. Ussangoda has no clear park point in the source, and Minneriya / Kaudulla combines two parks. Both are reported for review, as requested. Additional unmatched live records are also reported; the live count is determined by the command output.

## Owner-run application

`php cli/seed_destination_map_locations.php --dry-run` returns the complete create/preserve/skip plan and its SHA256. It performs no database writes. Apply with `--apply --backup-dir=/private/outside/site --expected-plan=PLAN_SHA256`.

Apply requires the existing map tables, their expected unique keys and InnoDB storage. It obtains the shared map activation lock, locks destination/location rows in a transaction, rejects a changed preview, writes and verifies a private JSON preimage, and inserts only missing destination-linked locations. Created records are verified/active using catalog coordinates and source attribution. All existing map rows, destination records, packages, settings and authored routes are preserved; any failure before commit rolls back the inserted rows. Migration history is not changed. A private `prepared_not_committed` record is diagnostic; only successful stdout/result confirms transaction commit.

`bash cli/pull_destination_map_locations.sh TARGET BASE` checks the exact reviewed revisions and eight-path release scope, rejects dirty release files/collisions, checks the existing route-map schema, then uses the existing source-backup/fast-forward helper with readable checkout permissions. It saves the live dry-run plan privately and applies that exact plan. Rerunning at the same target still runs an idempotent fresh import, so subsequently added recognized destinations can be filled.

Success markers: `DESTINATION_MAP_SEED_OK` and `DESTINATION_MAP_RELEASE_OK`. Review skipped names in the output, then select the newly linked places in Admin > Map Manager. No package route is authored or published automatically. Future improvement: add individually sourced catalog points after the owner reviews each unmatched destination.

## Validation

- Composer validation passes; full suite: **320 tests, 1,809 assertions, 54 skips**. The new pure matching/catalog suite passes **6 tests / 137 assertions**.
- Disposable database rehearsal: **21 checks pass**, including 41 sourced inserts, unmatched reporting, exact coordinates/attribution, dry-run read-only behaviour, idempotent rerun, unchanged destinations/packages/settings/routes/history, owner inactive/unverified records, unlinked-name collisions, stale destination/location previews, private backup readback, invalid schema rejection and rollback after a forced mid-import insertion failure.
- All four new PHP files pass syntax checks; the Bash wrapper and final handoff pass syntax checks; diff whitespace checks pass. No public/admin UI, auth, dependency, image or URL changes require new browser checks.
- Normal local packages, destinations, days, settings and map-schema baseline remain unchanged. Evidence is under ignored `output/destination-map-seed-20261025/`. The rehearsal database and private backup records contain synthetic data only.

The isolated eight-path release starts from the user-confirmed deployed base `89e8b40b631430219e1cf58efcd12b0ba748f736`. The dirty primary checkout is excluded. No normal local database writes or live deployment are part of release preparation. Live insertion counts and skipped names remain unverified until the owner runs the supplied terminal block.
