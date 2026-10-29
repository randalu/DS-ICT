# Quotation builder local candidate — 23 October 2026

## State and review location

Implemented in the managed `codex/quotation-builder-20261023` worktree, based on locally recorded `origin/main` at `788aba6244727c5fe2bc51f3f6a080e46797065c`. This is a local candidate, not a production release. No staging, commit, push, deployment, operational migration, real customer writes or outbound messaging was performed.

The original dirty checkout remains untouched. Eleven booking dependency files were copied explicitly from that checkout, recorded in `output/quotation/dependency-baseline.json`, and rechecked byte-for-byte in `original-preservation.json`. They provide the existing confirmation, policy-acceptance and customer-page safeguards absent from the clean baseline. These dependencies must be reconciled against the actual release target before applying the candidate.

The loopback demo uses only `ict_quotation_rehearsal_20261023`, synthetic travel content and a fictional administrator. `http://127.0.0.1:8153/__quotation_fixture` starts a fixture session; this route exists only in the explicitly selected CLI test router. It does not authenticate to the ordinary local or production site.

## Delivered behavior

- Dedicated protected quotation listing and five-step editor using existing admin sessions, with an allowlisted login return.
- Customer/trip details; manually numbered Day 0 and ranges; travel plans, experiences, destinations, images, overnight locations and approximate journey details.
- Destination/experience still-photo suggestions; manual image overrides; existing media-library selection; private activity, day-template and hotel suggestions.
- Two options by default, either optional; manual group or adult/child pricing with integer-cent calculations; optional deposits and calculated balances.
- Hotel category, room, occupancy, meals, alternatives, starting day and nights. These are proposed arrangements, not reservations.
- Permanent service checklist and editable master wording, copied into each new quote. Named activity defaults start excluded until selected or reviewed; inclusion decisions stay quote-specific.
- Draft autosave, tab recovery, stale-write rejection, duplication, searchable numbers, issued revisions, revision history, withdrawal and audit events.
- A4 PDF preview/export with local raster images, selectable text, repeated page furniture, activity comparisons and controlled pagination. Dompdf 3.1.6 is locked under `^3.1.6`; remote resources, embedded PHP and PDF JavaScript are disabled.
- Issued PDF bytes and image snapshots are archived in protected database records. Official exports use the stored bytes, preserving historical PDFs after edits to live media or templates.
- Device file-sharing preparation, explicit user share action, download/manual-attachment fallback, and message-copy/WhatsApp links. No delivery claim is made from a share-sheet result.
- Reviewed, transaction-safe conversion to guest or existing-account plans. Preserve Day 0/ranges, activities, images, selected hotel details and a binding to the exact price option/revision. Duplicate conversion is idempotent. Replacement requires an explicit review and cannot replace a confirmed booking or change account ownership.
- Separate guest acceptance evidence bound to the exact policies, quotation and services. Guest inquiries are excluded from email-based account claiming and customer inquiry views.
- Customer plans show their selected quotation and protected PDF. A submitted acceptance token binds the displayed price/revision to the acceptance record. Changed services, policies, withdrawn/superseded quotes or missing evidence prevent confirmation.

Quotation days/options/activities are stored as a whitelisted, bounded JSON document per revision. Private reusable content and audit events have separate tables. Confirmation reuses the existing operator payment/supplier checks and inquiry activity/message records.

## Database and deployment boundary

The explicit migration is `database/migrations/2026-10-23-quotation-builder.sql`. It adds quotation tables, permits NULL account ownership for guest plans, and adds the default-off `contact_inquiries.admin_guest_only` flag. It performs no destructive data changes. Existing day-number columns already support zero. Request handlers do not run this migration; incomplete migration readiness disables the quotation portal.

Before a separately approved deployment:

1. Pin and reconcile the real target commit and the eleven dependency changes. Review the exact path/hash manifest and SQL migration.
2. Verify backup/recovery, existing InnoDB booking tables, the guest ownership migration and database packet capacity for image/PDF archives. No production recovery claim is made by the synthetic tests.
3. Install production Composer dependencies from the tracked lockfile. Confirm PHP DOM, mbstring and GD support and a writable private system-temp directory outside the application root.
4. Apply the reviewed migration through the established owner-approved database procedure. Do not use the installer. Quotation storage contains customer data and must be included in existing private backup/retention operations; this change adds no automatic disposal.
5. Verify admin access, a synthetic draft/issue/guest conversion, PDF output, customer ownership, and physical-device sharing on the actual supported setup before operational use.

Public guest viewing/acceptance links, automated WhatsApp delivery, supplier reservations and margin costing are not included.

## Validation evidence

- Composer validation: passes; dependency installation/audit reported no known advisories.
- Final full suite: 255 tests, 1,486 assertions, no failures; 30 fixture-gated cases skipped. The final log is `output/quotation/full-tests-latest.log`.
- Final targeted quotation plus booking confirmation suite: 31 tests, 258 assertions, no skips. Includes immutable archives, decimal amounts, Day 0, guest/account acceptance, fresh quote binding, late-write rollback, changed policies/services/trip support and confirmed-booking protection.
- Separate credential-session schema: five tests, 28 assertions pass.
- Separate public-link schema: 17 tests, 422 assertions; one existing footer-order assertion fails. Both `includes/footer.php` and `tests/PublicTrustLinksTest.php` match the clean baseline. The test's broad footer XPath selects the existing developer-credit link before the configured Contact link. This unrelated baseline defect was not changed.
- Quotation HTTP checks: 14 pass, including authentication, CSRF, method gates, draft save, issue validation, stale writes and suggestions.
- Existing dynamic smoke: 29 pass, zero fail, two fixture skips. New quotation endpoints are covered separately by the HTTP checks.
- Broad syntax: 332 PHP files checked without errors; quotation JavaScript syntax checks pass. Recheck changed PHP and JS after final edits.
- Image-alt lint: passes after adding meaningful alt text to the synthetic media fixture. Variant dry-run: no writes, zero errors.
- Compact PDF follow-up: the short, eight-day and long samples now render as 2, 3 and 10 A4 pages, reduced from 3, 6 and 16. The cover shares a row with the title; smaller day photos, tighter heading/table spacing and flowing long days reduce whitespace while retaining 12px body text. Hotel details, both prices, per-option activity/service statuses and all wording remain present. Text extraction verifies repeated headers, footers and numbering, both prices, and all 65 paragraphs of the intentionally long day. Rendered pages were visually inspected, including long-day continuations. Existing issued archives remain immutable; the compact template applies to previews and newly issued revisions.
- Desktop browser: creation, customer/trip input, Day 0–1, destination image, existing experience, both prices, hotel fields, defaults, preview, issue, conversion, guest acceptance and existing written-confirmation form exercised successfully. The synthetic plan reached `confirmed`.
- Customer browser: selected quote price and Day 0–1 display, owned PDF download (200), and policy acceptance submission passed. A different customer account received 404 for both the owner's PDF and the guest PDF. These deliberate denied requests are expected negative-test console evidence.
- Mobile browser at 390×844: navigation, day editing, manual photo preservation, template save, per-person decimal keyboard, offline-save recovery and simultaneous-tab conflict/recovery exercised. Document width was 375px within the 390px viewport.
- File sharing: fallback and a simulated native-file payload pass in a clean browser session. Actual Android/iOS share sheets and WhatsApp attachment/delivery were not exercised. No messages were sent.
- Clean positive browser requests returned 200 with no console errors. Injected offline and conflict tests produced the expected failed request and 409; these are deliberate negative-test evidence.

Evidence files and generated sample PDFs live under ignored `output/quotation/`; browser CLI snapshots are local test artifacts, not part of the release allowlist. Test fixtures are hard-gated to named synthetic schemas and loopback CLI-server execution.

## Recommended next step

Review the local demo and sample PDF, then reconcile and approve the exact release candidate. Production migration and deployment remain separate approval gates. A later improvement can replace numeric inquiry/customer selectors with searchable contact pickers; current IDs are validated against existing records and ownership.
