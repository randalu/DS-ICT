# AI SEO Manager — local implementation, 15 October 2026

Status: implemented and activated locally. No commit, push, production migration, deployment or paid provider request was made.

## Use

Open **http://localhost/island/admin/seo_manager.php**, or **Media & Social → SEO Manager** in normal admin. The portal uses the existing admin login and fixed return destination. Select a destination, itinerary, tour package or blog post. Edit manually, or connect an OpenAI key in **Connection & Usage** and click a field's AI button. Review the comparison, accept or edit it, then explicitly save selected fields. A published record changes publicly when saved; drafts remain drafts.

The connection action stores the key without testing it. The separate **Run paid connection test** button requires explicit confirmation. The configured model is `gpt-6-luna`; no fallback model is used. No real key has been entered or tested by this implementation task.

## Delivered behaviour

- Search, pagination, publication/missing-metadata/review-date filters; effective metadata fallbacks; individual field selection; keyword plans; overlap notices; revision restore and stale-edit rejection.
- SEO titles, descriptions, excerpts, introductions and FAQ fields on destinations/itineraries/packages; blog titles, excerpts and selected body passages. Missing optional fields are omitted. Names, URLs, publication status, media, prices, itinerary days and relationships are outside portal saves.
- Blog body saves replace one unique text passage, sanitise only the replacement, and preserve surrounding HTML/media. Selections inside HTML attributes or embedded executable content are rejected. Manual edits spanning HTML tags must be made in the normal full editor. Restores preserve recorded body bytes and nullable metadata.
- One shared AI comparison component on supported fields in the four full admin editors. It removes duplicate legacy buttons and the legacy AI rename buttons for destination/itinerary/package names. It includes undo and unsaved-change warnings. Full-form save handlers retain their existing semantics; the portal's selective-save audit/restore guarantees apply to portal saves. Full-form saves do not create portal revision entries.
- Mandatory core rules with an explicit policy version, subordinate versioned editorial guidance, known-site link validation, plain metadata, safe rich text and strict FAQ validation. Suggestion provenance records the model, policy, guidance version and research brief identifiers.
- On-demand platform guidance and keyword research: cited web discovery followed by structured extraction. Briefs retain retrieval dates and sources, cache for 30 days, flag stale evidence and survive failed refreshes. Research never activates core policy changes. Keyword plans contain suggested, unmeasured targets; no meta-keywords tag or fabricated volume/difficulty/traffic data is added.
- Automated checks reject selected unsupported business claims, keyword repetition, unmeasured metric patterns and certain time-sensitive travel claims without current authoritative travel sources. They are guardrails, not comprehensive factual verification. The comparison always requires human review.

## Provider and session boundaries

PHP calls only the fixed OpenAI Responses endpoint over verified TLS, using `store:false`, bounded input/output and explicit timeouts. Platform guidance research prefers Google, Bing and OpenAI documentation. Public page context and guidance are sent; private customer/account records are not queried or sent. Existing OpenRouter generation/public AI search settings are unchanged.

Keys remain in the authenticated server-side PHP session, bound to the admin, credential stamp and connection nonce. They clear on disconnect, logout, invalid credentials or 30 minutes without SEO connection activity. PHP session files may retain expired session data until the next validation/garbage collection; session-only does not mean process-memory-only or immediate physical erasure. Keys are not returned in API responses, database settings, browser storage, URLs, old-input handling or application logs.

The service releases the PHP session lock during provider calls, then revalidates the session, current credentials and exact connection before storing a result. A MySQL advisory lock permits only one active request per admin. Unique request tokens prevent replay. Daily persisted request caps default to 50 generation/test requests and 10 research requests per admin, configurable through `SEO_GENERATION_DAILY_LIMIT` and `SEO_RESEARCH_DAILY_LIMIT` in server configuration. Failed/uncertain attempts count; there are no automatic paid retries. Usage reports tokens returned by the provider, not estimated currency charges. Provider retention rules still apply independently of response storage.

## Local migration and validation

Only `database/migrations/2026-10-15-seo-manager.sql` was applied to loopback database `ict`, and recorded with its checksum in the existing migration ledger. It adds six InnoDB workspace/audit tables. Before/after row counts and checksums for destinations, itineraries, tours, blog posts, site settings, admins and media assets match. No existing content/settings/admin/media rows changed. GET requests never install schema.

- Composer manifest validation passed.
- PHPUnit: **201 tests, 1,310 assertions, 14 existing skips**. The unconfigured run reproduces the existing unwritable `C:/laragon/tmp` issue. The successful run used a temporary `PHP_INI_SCAN_DIR` containing only a writable session-path override, including for isolated subprocesses; no system PHP configuration changed.
- Targeted SEO PHPUnit suite: **9 tests, 59 assertions**.
- Disposable database rehearsal: **54 checks**, including all content types, stale/replayed writes, cross-record restore, byte-exact body/NULL restore, audit-failure rollback, generation isolation, two-step research/cache/failure preservation, token accounting, duplicate submissions, quota, independent-connection concurrency and disconnect during a request. Provider responses were mocked in PHP.
- Dynamic route/POST smoke: **73 pass, 0 fail, 0 skip**. The existing workbook case also required a newly created private temporary spool, which was removed afterward.
- Browser: **35 positive checks** at 1440 × 1000 and 390 × 844, with no uncaught errors or unexpected HTTP failures. Checks cover preview/accept/discard/undo, selected saves, revision restore, manual blog-passage extension/media preservation, keyword-plan persistence, guidance, key clearing, responsive comparisons and integrations in all four full editors. UI generation responses were mocked in the browser; separate PHP tests validate suggestion persistence/provenance.
- Negative browser: **5 checks** for provider failure, interruption, unsaved-change protection, stale save and expired session. Intentional 502/aborted/409/401 paths preserve editor text.
- HTTP: **21 checks**, including guest/method/CSRF/shape rejection, protected fields, stale conflict, disconnected generation, selected save/restore and metadata rendering on all four local public routes.
- PHP syntax sweep: **307 application/test files**; JavaScript syntax and diff whitespace checks passed.
- Image-alt lint reports **six existing media-library records with missing alt text**. Media row counts/checksums are unchanged by the local activation; media repair remains outside this task.

Evidence is under ignored `output/seo-manager/`. Browser/HTTP writes used an isolated synthetic database and loopback PHP server. The test router needed explicit handling of existing admin static assets; after correction, the positive browser run has no failed resources. No production domains were tested. The normal local Apache portal guest redirect was verified, but authenticated SEO workflows under Apache/LiteSpeed CSP and physical-phone acceptance are separate from the loopback browser checks.

The temporary test browser/server and synthetic schema were removed. Final verification confirms the local SEO installation is ready and all seven original data-table fingerprints still match the pre-activation snapshot.

## Release and next acceptance

The current checkout contains substantial pre-existing dirty work, and local main is behind its recorded upstream. `seo-manager-release-scope-2026-10-15.json` inventories only this task's source paths and current hashes. Shared-file hashes include inherited changes: they are review evidence, not permission to deploy those files wholesale. Build an isolated candidate from the verified approved image-manager/auth baseline and transplant only the SEO integration hunks. No deploy-ready Git candidate is claimed here.

Recommended next: owner acceptance in normal local admin, then one explicitly requested paid connection/output test using a key entered only in the panel. Confirm model access, live research citations and real output quality. Separately approve the production migration/release after backup/restore and staging gates; rehearse rollback before publication. App rollback must remove the new navigation/form hooks together with the new services, while retaining the additive tables and audit history.

Later improvements: Search Console/Bing performance imports, measured keyword data, more content types, richer portal copy/FAQ editing, and multilingual support. The first version does not promise ranking or AI-citation improvements.

Official references: [model capabilities](https://developers.openai.com/api/docs/models/gpt-6-luna), [web search](https://developers.openai.com/api/docs/guides/tools-web-search), [structured output](https://developers.openai.com/api/docs/guides/structured-outputs), [Google AI search guidance](https://developers.google.com/search/docs/appearance/ai-features), [Google meta tags](https://developers.google.com/search/docs/crawling-indexing/special-tags).
