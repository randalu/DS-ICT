# Tour and itinerary release candidate — 24 October 2026

Built in an isolated `codex/tour-package-release-20261024` worktree from GitHub main `6fc1ff1d82f7a8283b5662f8328ff97999d2d8b0`. The original dirty checkout was preserved. Only the four approved layout/editor/curation/self-contained-Tour scopes and their release support are included. Earlier implementation notes are historical; the final Tour-owned programme supersedes their planning-guide editor/link design.

## Final behaviour

Tours, Itineraries and Day Tours retain separate collections. Tours show their exact places/experiences, route, daily highlights, travel notes, proposed overnight areas and practical details on their own page. The full editors, Image Manager, SEO Manager and Site Audit support the new fields within their existing roles. Independent covers appear on cards and collection banners. Itineraries keep their editable, customisable daily format with improved presentation. Existing trust links and released homepage/editor work were retained during integration.

The old Safari Tour becomes unpublished and its URL redirects to the retained Wildlife Safari Package. Wildlife and Honeymoon coverage is curated by theme. Twenty-nine covers are assigned by slug/path; one selected existing photograph is copied into the versioned `assets/images/package-covers/` folder, leaving its owner upload untouched. Local numeric IDs are not shipped as deployment identities.

Separate root duration-reference pages, their links and the former itinerary/Tour associations are retired. Useful source detail is transferred into Tour-owned programmes before deletion. Numeric root reference URLs return 410 and cannot be recreated through the former editor, CMS alias, intent-map writes or old seed paths. Normal `/itineraries/<slug>` records remain available.

## Activation and recovery boundaries

Code pull and database/content activation are separate steps. Do not run the broad historical migration runner for this release.

After pulling this candidate, `php cli/activate_tour_package_release.php --check` is read-only. It verifies the reviewed package preimages, resolves slugs and media paths, and checks photograph hashes. It stops if owner content has changed.

`php cli/activate_tour_package_release.php --apply --backup-dir=/absolute/private/directory` applies only these three guarded additive SQL files:

- `2026-10-24-tour-coverage.sql`
- `2026-10-24-package-editor-media.sql`
- `2026-10-24-tour-program.sql`

It records those exact migrations, curates covers and the two Tours, copies source details from the target database, removes retired references and installs their rejection triggers. A private preimage of affected public-content tables and migration history is written first. No credentials, account or inquiry tables are copied. Content curation is transactional; MySQL DDL and reference-retirement activation have separate commit boundaries. A failure in a later step may leave earlier additive schema/content steps applied. Preserve the private preimages and review the reported step before retrying. Repeat activation preserves programmes and rejects subsequent conflicting owner edits.

The preimages support reviewed recovery; this release does not supply an automatic whole-database restore or certify a production restore. Keep existing columns/media and later owner edits when planning rollback. A code-only rollback does not restore removed reference records or remove database triggers.

## Candidate evidence

- Composer manifest valid; **293 tests, 1,613 assertions, 48 skips**. Skips are tests gated to other named disposable fixtures; they are not claimed as executed here.
- **36 dynamic route/auth/CSRF checks**, **9 JavaScript audit tests**, **86 HTTP checks**, and **16 release-activation checks** passed.
- PHP/JavaScript syntax checks passed for changed source. Whitespace checks passed.
- The activation rehearsal deliberately changed Tour IDs and media auto-increment values. It verified slug/path resolution, read-only preflight, repeat execution, retention of all Tour rows, preservation of itinerary-day/gallery content and protected fields, retirement of old records, and rejection of owner edits without content writes.
- The candidate was served from its own directory on loopback port 8150 using a disposable public-content database and a synthetic administrator. Public Tour/Itinerary/Day Tour routes, selected covers, sitemaps, retired routes, programme editors and Image Manager slots passed HTTP checks. The PHP test router emulates rewrites; this does not certify production Apache/OpenLiteSpeed routing.
- Chrome review measured 1366px desktop and 390px mobile, with no horizontal overflow on the reviewed Safari and 10-day itinerary pages. Safari showed nine daily cards and five proposed stays; the itinerary retained ten day disclosures. Captured console warnings/errors were empty. Prior tablet/editor workflow evidence is in the earlier local notes; physical-device acceptance is not claimed.

Private local working evidence is under `output/tour-release-20261024/`; it is excluded from Git. No production command was executed by the agent for this release. The owner runs the provided guarded pull wrapper; a successful code pull alone does not establish content activation or authenticated staff acceptance.

## Content review still required before publishing the combined copy

The programme transfer preserves source travel prose, including inherited safari claims such as “guaranteed elephant territory” and time-sensitive feeding/entry statements. Review those against current operating arrangements before activation/publication. Hotel names, rooms and meals remain proposed/unconfirmed until agreed in the written quote. Anuradhapura and Trincomalee/Nilaveli Day Tour covers remain unassigned pending matching photographs. Six existing Media Library alt descriptions remain blank. Legacy local hero gaps do not establish a live media defect.
