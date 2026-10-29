# Island Culture Travels Project Profile

## Overview

Island Culture Travels is a custom PHP travel website for a Sri Lanka private travel and tour business. The site supports private chauffeur-guided travel, private vehicle services, custom Sri Lanka tours, ready-made itineraries, airport transfers, wildlife and safari trips, cultural and heritage tours, beach holidays, and flexible planning for couples, families, and small groups.

The project is served locally from `C:\laragon\www\island` through Laragon/Apache. Rewrites are handled by `.htaccess`. It is a custom PHP 8.3 site, not Laravel or WordPress. Composer is present at the repository root and currently manages PHPUnit for the local test suite. There is no root Node manifest.

Current branch baseline:

- Branch: `main`
- Current tracked upstream status at this profile update: `main...origin/main`, clean
- Latest known pushed commit: `eb560e3 Replace broken PHPUnit phar with Composer PHPUnit`
- Previous major commit: `838786e Harden Island auth, SEO, and public page fallbacks`

## Important Paths

- `composer.json`: Composer project manifest; defines `composer test` as `phpunit tests`.
- `composer.lock`: committed dependency lockfile; keep it tracked.
- `includes/functions.php`: shared helpers, database access, schema guards, upload helpers, and content utilities.
- `includes/schema.php`: JSON-LD schema helper functions.
- `includes/auth.php`: admin authentication.
- `includes/customer_auth.php`: customer account authentication.
- `config/config.php`: environment loading, constants, sessions, and debug settings.
- `config/database.php`: PDO bootstrap.
- `config/bootstrap.php`: shared public-page bootstrap for redesigned routes.
- `admin/`: admin CRUD pages and save handlers.
- `database/schema.sql`: baseline schema.
- `database/migrations/`: dated schema/content migrations.
- `cli/`: local validation and generation scripts.
- `tests/`: Composer-managed PHPUnit tests.
- `assets/uploads/`: local uploaded media; treat as protected runtime content.

## Current Runtime Notes

- Brand name: `Island Culture Travels`.
- Source-controlled config treats `APP_URL` as the canonical base URL. `.env.example` currently sets `APP_URL=https://srilankaprivatetravels.com`, `robots.txt` points the sitemap to `https://srilankaprivatetravels.com/sitemap.xml`, and Trustpilot links reference `islandculturetravels.com`. The repo does not currently prove a cross-domain redirect between `islandculturetravels.com` and `srilankaprivatetravels.com`; verify server/domain configuration before documenting redirect behavior as fact.
- Redesigned public routes such as `destinations.php`, `experiences.php`, `itineraries.php`, `experience.php`, and `itinerary.php` use the shared `config/bootstrap.php`.
- Some pages are designed to tolerate older database installs through `db_table_exists()` and `db_column_exists()`. Preserve this pattern.
- `destination_photos.caption` is used as frontend image alt text and is checked by `php cli\lint_image_alt.php`.
- Local web smoke may be affected by Apache HTTPS/TLS configuration. If `curl.exe -k https://localhost/island/...` fails at the TLS layer, use PHP CLI route execution and report the environment blocker.
- `vendor/` is expected to exist locally after `composer install`, but it stays ignored and must not be committed.

## Standard Local Workflow

Use Composer for the test toolchain:

```powershell
composer install
composer validate
composer test
```

Keep `composer.lock` committed so PHPUnit and related development dependencies stay reproducible. Keep `vendor/` ignored and local-only.

## Safe Validation Set

Use the smallest validation set that matches the change:

- `composer validate`
- `composer test`
- `git diff --check`
- `php -l` for changed PHP files
- Full PHP syntax sweep after shared helpers/config/auth/routes change
- Representative public route execution for touched public pages
- Admin GET entrypoint audit for admin page changes
- `php cli\smoke_post_gates.php --only=dynamic` for route/bootstrap/CSRF coverage
- `php cli\lint_image_alt.php` after media, gallery, or content quality changes
- `php cli\generate_image_variants.php --dry-run --widths=400,800` after image variant logic changes

Latest verified local validation snapshot from this profile update:

- `composer validate`: passed
- `composer test`: passed, 13 tests and 28 assertions
- `git diff --check`: passed
- Full PHP syntax sweep excluding `vendor/`: passed
- `php cli\smoke_post_gates.php --only=dynamic`: 28 pass, 0 fail, 0 skip
- `php cli\lint_image_alt.php`: passed, no alt-text issues found

## Recommended Ongoing Improvements

- Add or maintain repo-local workflow documentation in `AGENTS.md` and this profile.
- Keep public route bootstrap and schema loading consistent.
- Deepen admin save-handler and POST-gate hardening, especially around unsafe methods, CSRF, JSON validation, and `old_input_set()` preservation.
- Improve schema-safe public-route compatibility around optional tables/columns before querying.
- Continue expanding local smoke automation for route, bootstrap, and CSRF coverage.
- Add admin UI controls where content quality lint identifies recurring manual data issues.
- Improve local HTTPS smoke testing so browser/curl checks can run without falling back to CLI route execution.
