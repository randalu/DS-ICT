# Activities and shared admin OpenAI release

Prepared on 26 October 2026 from `origin/main` at `0c939bccd37623af62bdc036f03d1b0610d34bb2`. The dirty primary checkout is preserved; this candidate lives in `output/activities-shared-openai-candidate`.

## Resulting behavior

- Standalone Activities hub and 16 categories, destination examples, stable entry IDs, 1–5-image entry galleries, Image Manager and SEO integration, sitemap and Site Audit support.
- One server-only `OPENAI_ADMIN_API_KEY` for Image Manager, SEO Manager and Site Audit. Models default to `gpt-6-luna`, independently configurable with `OPENAI_ALT_MODEL`, `OPENAI_SEO_MODEL` and `OPENAI_AUDIT_MODEL`. The existing OpenRouter key is unchanged.
- Image suggestions are requested, previewed, edited/applied and explicitly saved. An image's first description becomes its Media Library default. Later placement descriptions remain local unless **Update saved image alt** is selected.
- Legacy hero, OG, poster and itinerary-day slots have independent placement metadata. Asset and placement updates retain existing auth, CSRF, transactional and stale-edit checks. Ambiguous path matches are not merged; explicit validated asset associations take precedence.
- Alt requests are limited to 20 per administrator per hour and 100 site-wide per day, including failed/uncertain attempts. Shared reporting identifies feature usage; existing SEO request history and quotas are retained. There are no automatic paid retries.
- Missing image files render text-only entries publicly and remain editorial audit findings. Draft hub records do not create public navigation or core sitemap links.

## Validation evidence

- Composer validation passed. `composer test`: **341 tests, 2,186 assertions, 54 skipped**, no failures/errors. Skips include existing named disposable-schema suites for quotations, credential responses, public trust content and route-map storage; relevant Activities/Image Manager/shared-AI tests ran against the local schema.
- Targeted regression checks cover duplicate and concurrent AI requests, hourly/site quotas, shared model routing, legacy session clearing, revoked login, asset-default revisions, image-only changes and stable Activity entry galleries.
- Chromium: **77 primary checks** at 320, 390, 768, 1024 and 1440px, plus **21 extra checks** covering native touch viewer swipes, portrait containment, focus trapping, zero/missing-image fallbacks, per-file generation controls, sequential real uploads, final-image protection and JavaScript-disabled fallback. **Three Media Library checks** verify asset selection, reuse of saved defaults and preservation of unsaved wording (101 browser checks total).
- Browser suggestions were mocked; actual uploads/saves/removals used the disposable database. No real application content was copied into the browser fixture. No captured uncaught errors or unexpected local HTTP failures in the primary pass.
- Dynamic POST/route smoke: 43 passed, zero failed/skipped. PHP/JS and Bash syntax, responsive-image dry run and whitespace checks passed.
- Image-alt lint reports existing editorial findings: six missing descriptions, nine missing entry galleries and one missing image file. These are not auto-filled with unrelated images. The guarded local metadata backfill filled two empty asset defaults; conflicting/unmatched cases were reported.
- Full local evidence: `output/shared-openai-acceptance/`, `output/candidate-composer-test.txt`, `output/candidate-post-gates.txt`, `output/candidate-alt-lint.txt`, `output/media-alt-backfill-result.json` in the primary workspace. Browser screenshots are local evidence, not release uploads.

## Database activation

Run `php cli/activate_activities_ai.php --check` before `--apply`. It allows only:

1. `2026-10-25-activities.sql`
2. `2026-10-25-activity-entry-galleries.sql`
3. `2026-10-25-shared-admin-openai.sql`
4. `2026-10-25-image-placement-alt.sql`

Existing table types, named indexes/foreign keys and recorded migration checksums are checked before DDL. The owner-run wrapper makes a private targeted database backup first. MySQL DDL is not transactionally reversible; an interrupted activation must be inspected before rerunning. Do not run the global migration command for this release.

`seed_activity_pages.php` adds only missing categories and their examples as drafts. Existing categories, entries, publication states and media stay untouched. `backfill_media_alt.php --check` reports candidates; `--apply` fills only empty defaults with agreeing descriptions, never overwriting existing defaults.

## Owner-run live release

Use the SHA-pinned terminal handoff delivered after push. It downloads `cli/pull_activities_ai.sh` from that commit, runs `--check`, then `--apply`. The helper requires the reviewed live base, exact release manifest, fast-forward ancestry and unchanged release paths. It never resets or cleans the live checkout.

Backups/preflight packets are under the server administrator's home directory with private permissions. Git source checkout uses `umask 022`; file readability is checked. Code pull, schema/draft activation, public HTTP checks and authenticated/provider acceptance are reported as separate stages. No production actions were performed by the implementation agent.

If live HEAD is not the reviewed base, stop and review the intervening release. If GitHub SSH fails, keep host-key verification and existing remote configuration intact; use the established reviewed private-bundle procedure rather than changing SSH settings.

## Private server key and acceptance

No local `OPENAI_ADMIN_API_KEY` is configured, so real-provider acceptance remains pending. Configure the dedicated key privately after deployment, then inspect `php cli/admin_ai_status.php`. The delivered terminal handoff uses hidden input and stdin, with a private environment backup; no key appears in command arguments or Git. Restart hosting PHP workers if the environment is injected outside `.env`.

In an authenticated admin session, verify one real image suggestion, SEO generation/research and a selected Site Audit AI review. Check preview-before-save and unchanged publication state. Technical audits and manual editing remain available without the key.

## Recovery

Each deployment packet contains the source preimage and an integrity-checked targeted JSON database backup. Preserve it outside the webroot. A code rollback should be a separately reviewed revert of this single release commit, preserving source readability and any later live edits. Keep additive tables and their data when rolling back code; do not run destructive down-migrations. If database recovery is necessary, review the affected table preimages and any writes since the backup before restoring; never overwrite newer owner content automatically.
