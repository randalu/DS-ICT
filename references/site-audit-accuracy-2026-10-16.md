# Site Audit accuracy — local implementation and acceptance

Status: complete locally. No production release, paid AI request, content repair or schema migration. Normal-admin acceptance retained run #5; run #4 remains unchanged.

## Implemented scope

- Inventory applies the public blog date predicate, `published_at IS NULL OR published_at <= NOW()`, alongside the publication flag. Future posts enter the unpublished-route list rather than the public inventory. Older schemas without that column retain guarded compatibility.
- DOM label checks exclude hidden, inert, aria-hidden, CSS-hidden and non-rendered controls while retaining visible unlabelled inputs. Contact/partner honeypots are preserved.
- JSON-LD checks recognize typed `@id`-only references instead of requiring entity fields on them. Expanded entities still receive supported checks; this is not a comprehensive schema validator.
- Page discovery and duplicate analysis merge the configured site root's slash alias and empty query markers. Meaningful query values/order and arbitrary route trailing slashes remain distinct. This changes audit identity only, not public routing or canonical output.
- Browser navigation reports a redirect chain only above one hop. Existing internal-link redirect/loop safeguards remain.
- Excluded resource probes use low-severity `resource_not_checked` with `not_checked` certainty; they do not count as attempted checks. Blocked image decoding uses the same classification. Timeouts/blocks remain inconclusive with low severity; observed 404s retain high severity. Repeated observations and unique findings have separate counts.
- Overview shows the largest 30 groups by cause, target and certainty, plus the total group count. Empty targets stay page-specific. Matching-findings controls search targets as well as pages/rules. Original findings, evidence, review states and exports remain intact. The JavaScript asset has a file-version query to prevent stale UI code.
- New runs use profile `public-v3-desktop1440-mobile390`, plus the inventory capability signature. Rule changes cannot silently resolve findings from the older profile. The worker emits its actual configured limits at startup for future runs.

Changed application areas: `includes/site_audit_portal.php`, `cli/site_audit_checks.js`, `cli/site_audit_crawl.js`, `admin/site_audit.php`, `admin/site_audit_api.php`, `assets/js/site-audit.js`. Regression coverage is in `tests/SiteAuditPortalTest.php`, `tests/site_audit_portal_test.js` and the two existing audit fixtures. No shared public URL/configuration helpers were edited.

## Validation

| Gate | Result |
|---|---|
| `composer validate` | Pass |
| `composer test` | 210 tests, 1,361 assertions; 14 existing skips |
| `composer test -- --filter SiteAuditPortalTest` after final grouping change | 5 tests, 24 assertions |
| `node --test tests/site_audit_portal_test.js tests/site_audit_worker_test.js` | 12 tests pass |
| Synthetic MySQL publication fixture | Future excluded; past and undated included; inserted fixture records rolled back |
| Real Chromium DOM fixture | Only `#visible-unlabelled` flagged; labelled/hidden controls excluded; nested schema reference accepted |
| `php cli/smoke_post_gates.php --only=dynamic` | 73 pass, zero failures with verified private test spool |
| Changed PHP and JavaScript syntax | Pass |
| `git diff --check` | Pass |
| Image-alt lint | Six pre-existing media-library omissions; unchanged |

Tests use a temporary process-scoped `PHP_INI_SCAN_DIR` to select the existing writable session directory. The first dynamic-smoke attempt had one unrelated workbook failure because its default Windows spool did not pass privacy checks. The same suite passes with process-scoped `ICT_RECORDS_TMP_DIR` set to the existing verified private audit directory for the validation identity. No workbook code, global environment, permissions or system PHP configuration changed. The temporary INI file was removed after validation.

The initial publication fixture attempted an ALTER after the schema helper had cached the missing column; it correctly failed the eligibility assertion. The fixture now inspects its own disposable table directly before ALTER, avoiding that cache trap. The rerun passes. A separately invoked older broad fixture check requires a completed fixture crawl and was not applicable to the standalone publication test; no broad fixture acceptance count is claimed here.

The browser's restricted read-only inspection scope does not expose Performance APIs. The DOM visibility fixture used the unchanged snapshot logic with only TTFB replaced by zero; this check makes no timing claim. The actual crawler ran the full function in Chromium.

## Real local recheck

Run **#5** completed at local application time 11:21 on 16 October 2026:

| Measurement | Result |
|---|---:|
| Pages | 195 / 195 |
| Findings | 1,435 |
| High / medium / low | 378 / 513 / 544 |
| Confirmed / suspected / not checked | 540 / 481 / 414 |
| Internal-link targets checked | 200 |
| External discovered / checked | 229 / 0 |
| Attempted resource-probe observations / excluded observations | 1,967 / 987 |
| Read-only finding groups | 431 |

No scheduled-page HTTP-error, unlabelled-input or redirect-chain findings occur in this run. Sixty-two image-decode findings remain, while excluded resources are explicit: 413 not-checked resource findings plus the partial-coverage finding. Twenty schema-required-field findings remain for Organization URL fields, not the sampled WebPage references. Existing canonical/base-path and media/content concerns remain for separate investigation.

The two runs have different rule profiles and observations; the count decrease is not a count of repaired public defects. Coverage remains partial. No prior findings were marked resolved. A comparison correctly displays “No earlier compatible completed run.”

## Browser and preservation acceptance

Used the normal authenticated local Apache portal at `http://localhost/island/admin/site_audit.php`. Started run #5 through its manual control, observed progress, reconnected in a fresh tab when browser inspection detached, and verified completion. Group expansion, matching-target filtering, evidence certainty and history comparison work. Desktop 1440 × 1000 and mobile 390 × 844 have no horizontal overflow. Mobile matching findings display low/not-checked resource evidence. Console/warning capture is empty; all four portal JS/CSS asset GETs return 200, and rendered API data loads successfully. No unexpected network errors were recorded for the portal.

Before/after checksums match for **85 non-audit tables**, including the migration ledger, SEO requests, settings, content, media assignments and customer data. Run #4 row and full presented-issue hash match their pre-phase values. Only the new audit's records/observations were added; no review-state saves were made. The disposable synthetic schema/server were cleaned up after testing. No uploads were modified.

Evidence: ignored `output/site-audit-accuracy/` contains the full/targeted test logs, dynamic smokes, original preservation baseline, acceptance JSON, exported run #5, DOM fixture result, browser log capture and desktop/mobile screenshots. The exact-scope release packet was refreshed and its apply/reverse/source comparison rehearsed; it is not a production deployment authorization. The heavily dirty checkout remains unstaged and uncommitted.

## Recommended next step

Read-only reconciliation of the 47 absent local media paths from run #4 against the refreshed run #5, grouping only remaining missing files and distinguishing local-copy gaps from assignment/rendering defects. This produces owner-reviewable media choices before any upload, replacement, canonical/CSS change or publication. A source-of-truth/live-media comparison, actual content repairs and production release each need separate scope selection.
