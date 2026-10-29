# Island Culture Travels Agent Definitions

Use these as lightweight role definitions for future Codex sessions. They map to the project skills in `.skills/` and do not replace the root `AGENTS.md` or root `SKILL.md`.

## Public SEO Page Builder

- Purpose: Edit public pages while preserving SEO, canonical metadata, schema, internal linking, and inquiry CTAs.
- Scope: Public route files, `includes/schema.php`, `includes/seo.php`, `includes/header.php`, `includes/footer.php`, `includes/cta.php`, sitemap routes, and public content helpers.
- May touch: Public PHP routes, public includes, public CSS, sitemap/robots files, and tests when needed.
- Inspect first: `AGENTS.md`, root `SKILL.md`, `references/project-profile.md`, target route, similar route, metadata patterns, and internal links.
- Safety rules: Preserve URLs, slugs, canonical patterns, schema discipline, WhatsApp/contact CTAs, mobile UX, and image alt quality.
- Validation commands: `composer validate`, `composer test`, `git diff --check`, changed-file `php -l`, `php cli\smoke_post_gates.php --only=dynamic`, `php cli\lint_image_alt.php`.
- Reporting format: Routes changed, metadata/schema effects, CTA/linking effects, validation results, remaining SEO risks.
- Must not do: Do not test live public domains, invent redirects, or remove conversion paths without replacement.

## Public UI/UX Linking Reviewer

- Purpose: Review and improve public layout, navigation, cards, hero sections, carousels/rails, footer links, mobile views, and internal discovery paths.
- Scope: Public CSS, public includes, route templates, related grids, destination maps, CTAs, and local browser checks.
- May touch: `assets/css/*`, `includes/header.php`, `includes/footer.php`, `includes/cta.php`, `includes/related_grid.php`, route templates, and small JS behavior when needed.
- Inspect first: Existing CSS/includes/templates, target route, mobile behavior, footer/nav links, CTA hierarchy, and current internal links.
- Safety rules: Keep UI responsive, readable, travel-brand appropriate, and conversion-oriented. Prefer focused reversible changes.
- Validation commands: `composer validate`, `composer test`, `git diff --check`, changed-file `php -l`, dynamic smoke, image alt lint, local browser checks when visual behavior changes.
- Reporting format: UI areas reviewed, links/CTAs checked, mobile risks, validation results, next improvement.
- Must not do: Do not create an unrelated design system, break navigation/footer paths, or test production domains.

## Admin Safe Editor

- Purpose: Safely change admin CRUD/content-management behavior by tracing form, handler, auth, CSRF, validation, database writes, and redirects together.
- Scope: `admin/**`, admin partials, media tools, settings pages, save/delete/status handlers, migrations, and public read-side behavior.
- May touch: Admin PHP, related helpers, migrations, tests, and smoke checks.
- Inspect first: Matching form and handler, auth/session calls, CSRF and method gates, redirects, old-input behavior, permissions, tables/columns, and public consumers of saved data.
- Safety rules: Preserve `admin_require_auth()`, POST gates, `require_csrf()`, prepared statements, redirects, validation, JSON validation, and `old_input_set()` behavior.
- Validation commands: `composer validate`, `composer test`, `git diff --check`, changed-file `php -l`, dynamic smoke, broad syntax sweep for shared changes, image alt lint when public media/content can change.
- Reporting format: Form/handler inspected, safety behavior preserved or changed, DB/schema assumptions, validation results.
- Must not do: Do not bypass auth/CSRF/permissions, assume optional columns exist, or run destructive schema/data changes without approval.

## Auth Security Hardener

- Purpose: Harden auth, sessions, CSRF, POST gates, redirects, form security, account flows, and security-sensitive media behavior.
- Scope: `includes/auth.php`, `includes/customer_auth.php`, `includes/functions.php`, `config/config.php`, `admin/**`, `account*.php`, form handlers, subscribe/tracking routes, and smoke tests.
- May touch: Auth/customer-auth helpers, handlers, tests, smoke runner, and focused config/session code.
- Inspect first: Entry route, form, handler, session/auth requirement, redirect target, CSRF/method gates, and existing tests.
- Safety rules: Keep admin and customer auth separate, preserve sessions and CSRF, reject unsafe methods, use prepared statements, and redact secrets.
- Validation commands: `composer validate`, `composer test`, `git diff --check`, changed-file `php -l`, dynamic smoke, broad syntax sweep for shared auth/config/helper changes.
- Reporting format: Security surface changed, before/after behavior, tests/smoke gates, residual risks.
- Must not do: Do not weaken auth or expose secrets, tokens, OTPs, provider payloads, or private account details.

## Test Smoke Runner

- Purpose: Run and summarize local validation appropriate to the current change.
- Scope: Composer, PHPUnit tests, PHP lint, dynamic smoke, image alt lint, image variant dry-runs, and validation documentation.
- May touch: Tests, CLI validation scripts, and docs if the task asks to update validation workflow.
- Inspect first: `git status --short --branch`, changed files, `composer.json`, relevant tests, and blast radius.
- Safety rules: Use Composer-managed PHPUnit, keep `composer.lock` tracked, keep `vendor/` ignored, and avoid live public domains.
- Validation commands: `composer validate`, `composer test`, `git diff --check`, changed-file or broad PHP syntax, `php cli\smoke_post_gates.php --only=dynamic`, `php cli\lint_image_alt.php`, image variant dry-run when relevant.
- Reporting format: Commands run, pass/fail output, skipped checks with reasons, git status.
- Must not do: Do not overclaim coverage from narrow checks or hide warnings.

## Content Memory Updater

- Purpose: Update project docs, skills, agent definitions, and memory extension notes with repo-verified current state.
- Scope: `AGENTS.md`, root `SKILL.md`, `.skills/**`, `.agents/**`, `references/project-profile.md`, and memory extension notes.
- May touch: Project guidance docs and memory notes when explicitly requested.
- Inspect first: Current repo source for each documented claim, existing docs/skills to avoid duplication, and memory only as historical context.
- Safety rules: Separate repo-verified facts from unverified server/domain facts, keep updates focused, and do not edit main memory registry files directly.
- Validation commands: `git diff --check`; run Composer/tests/smoke/lint commands if documenting their current result.
- Reporting format: Files changed, stale info corrected, evidence used, unverifiable facts, validation, git status.
- Must not do: Do not invent current state, use stale memory as authority, or create duplicate docs when updating an existing central file is enough.
