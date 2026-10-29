# Site Audit — local activation and normal-admin acceptance

Status: **complete for local activation and the unpaid normal-admin workflow**. Open [the local audit](http://localhost/island/admin/site_audit.php). Run **#4** is retained, along with its findings and observations. No production release, live-site probing, paid OpenAI call or content repair was performed.

## Activation and preservation

- Confirmed local target: `ict` on loopback, with `APP_URL=http://localhost/island`. Recorded row counts/checksums for all 87 existing tables before applying anything.
- Backed up existing table definitions, complete existing audit rows and the migration ledger under protected/ignored `output/site-audit-activation/`.
- Applied only `database/migrations/2026-10-16-site-audit-portal.sql`, verified against the reviewed source hash, and added one checksum-bearing ledger row. No unrestricted migration runner was used. The existing progress columns were already present; the new tables were additive.
- Immediately after migration, all **86 non-ledger tables** had unchanged checksums. After actual acceptance, all **84 original tables outside the ledger and two audit-result tables** still matched. Original legacy audit rows were compared field-for-field and preserved. Existing SEO requests, content, media assignments, settings, administrators and customer records are unchanged.
- Review-state acceptance temporarily acknowledged a verified placeholder finding, then restored its state to open with an empty reason. Only audit metadata was involved.

## Web-worker runtime

Normal Apache and the validation shell use different Windows security identities. The first real Apache preflight correctly rejected the shell-owned directory. Storage is now partitioned by application, database **and actual service identity**, retaining the existing owner/service permission verification. No permissions were broadened on a shared directory.

The local ignored runtime file `output/site-audit-runtime.json` points to the already-installed Playwright packages. It contains no API key and is loaded only for a non-production loopback application URL. It sets `max_external_links` to **0**, so local acceptance does not probe live public domains. No global Apache configuration, system environment, `.env` or uploaded media was changed. The installed package path is machine-specific; if that installation moves, update this ignored runtime file and rerun host readiness.

Normal-admin **Check host readiness** passes for Node, Chromium launch and private storage. Deployment elsewhere still requires its own service-account dependencies and resource checks. `SITE_AUDIT_NODE_MODULES` and `SITE_AUDIT_MAX_EXTERNAL_LINKS` are optional server-side overrides; the normal external-link default remains 25 when no override is supplied.

## Actual local audit result

| Measurement | Result |
|---|---:|
| Pages completed | 199 / 199 |
| Findings | 1,803 |
| Affected pages | 195 |
| High / medium / low | 1,143 / 535 / 125 |
| Internal targets checked | 195 |
| Resource observations/probes reported | 3,104 |
| External links discovered / checked | 229 / 0 |

Largest rule groups: resource failures **738**, suspected decode failures **328**, placeholder occurrences **287**, empty media assignments **135**, potential topic overlap **69**, and canonical-origin findings **62**. Counts are finding occurrences, not distinct broken files. Decode failures, topic overlap and other heuristic findings need review. Canonicals that intentionally point from this local copy to production also need environment-aware triage.

Coverage is explicitly **partial** because external probes were intentionally disabled and some checks were inconclusive or excluded. Completing every discovered page does not certify every resource, SEO assertion, external link or live page. No issue was repaired or automatically marked resolved.

## Normal-admin acceptance

Used the existing ordinary Chrome/admin session at `http://localhost/island`, with real Apache routing and authentication; no synthetic session injection, credentials reset or authentication bypass.

Verified:

- Existing legacy history remains readable; the migration notice clears and launch becomes available.
- Host preflight succeeds from Apache; the manual run starts, reports progress, continues while navigating elsewhere and finishes with imported results.
- Mobile sidebar **Site Audit** navigation, issue search/filtering, structured evidence, review-state save and restoration, and page/resource observations work.
- The exact public AI context preview works and excludes private URLs/credential fields. The paid submission was not clicked; existing `seo_requests` checksum remains unchanged.
- Finding links open normal Image Manager and the corresponding SEO record; the published-save effect is displayed. No image or SEO field was saved during this acceptance.
- Authenticated JSON and CSV exports succeed and are retained as `run-4.json` and `run-4.csv` under the protected evidence directory.
- Desktop at 1440 × 1000 and mobile at 390 × 844 fit without horizontal overflow. Browser console/network error capture for the portal is empty. Screenshots include `overview-desktop.png`, `overview-mobile.png` and `ai-context-mobile.png`.

Automated checks after activation/runtime integration: **209 PHPUnit tests / 1,355 assertions / 14 existing skips**, using the temporary writable session override; **8 JavaScript rule/worker tests**; changed PHP syntax and scoped diff checks pass. Prior feature checks remain documented in the implementation report. Six existing media-library alt omissions are outside this activation.

Evidence: `output/site-audit-activation/before.json`, `activation.json`, `acceptance.json`, the restricted schema/audit/ledger backup, exports, screenshots, console capture and test output. The temporary authenticated diagnostic route was removed. The release source packet is regenerated after these runtime fixes; its apply/reverse rehearsal remains separate from deployment authorization.

Recommended next: **read-only triage of this local report**, grouping duplicate media occurrences by missing file/template and separating local-only canonical/media differences from actionable defects. This changes no content. Content repair, a real paid AI test and production release remain separate owner-approved steps.
