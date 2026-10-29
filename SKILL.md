---
name: island-culture-travels
description: Use for Island Culture Travels work in C:\laragon\www\island, including public page edits, admin features, SEO, UI/UX, internal linking, auth/session/CSRF safety, forms, Composer/PHPUnit tests, smoke checks, media/alt text, and project memory or profile updates.
---

# Island Culture Travels

## Purpose

Island Culture Travels is a custom PHP 8.3/Laragon site for a Sri Lanka private travel business. Use this skill when working on public pages, admin content tools, SEO, UI/UX, linking, auth, forms, tests, smoke checks, and project memory/profile docs.

## Scope

- Public pages: destinations, experiences, itineraries, tours, reviews, guide pages, contact, account-facing pages, sitemaps, schema, and CTAs.
- Admin/backend: admin CRUD pages, save handlers, auth/session flows, customer account flows, media handling, settings, and database compatibility.
- Workflow/docs: `AGENTS.md`, `references/project-profile.md`, `SKILL.md`, `.skills/**`, `.agents/**`, memory update notes, and validation guidance.

## May Touch

- Public PHP route files, public includes, public CSS/JS, sitemap/robots files, admin forms/handlers, auth/customer-auth helpers, CLI validation scripts, tests, and project guidance docs when the task requires them.
- Keep edits scoped to the task; do not rewrite unrelated content or shared files opportunistically.

## Inspect First

- `git status --short --branch`
- `AGENTS.md`
- `references/project-profile.md`
- The route/form/handler/include/CSS files directly affected by the task.
- `composer.json`, `.gitignore`, and existing tests when changing tooling or validation.
- Existing schema guards and migrations before changing queries or database fields.

## Safety Rules

- Prefer small, reversible edits that match existing helpers, includes, CSS, route structure, and admin patterns.
- Preserve public URLs, route names, slugs, conversion CTAs, auth/session behavior, CSRF checks, POST gates, schema guards, and tracked dependency rules.
- Do not read secrets or protected runtime files unless explicitly required.
- Do not use memory or old chat context as authority when the current repo can verify the fact.

## Safe Public Page Editing

- Preserve public URLs, route names, slugs, canonical patterns, internal links, and contact/WhatsApp CTAs unless the task explicitly asks for a migration.
- Maintain links between destinations, experiences, itineraries, reviews, guide pages, and inquiry CTAs.
- Keep public pages SEO-first, mobile-first, fast-loading, conversion-focused, and consistent with the travel brand.
- Protect hero sections, navigation, cards, carousels/rails, footer links, CTA hierarchy, and mobile views.
- Inspect existing CSS/includes/templates before changing UI. Avoid broad visual rewrites unless requested.

## SEO And Metadata

- Check existing meta title, meta description, canonical URL, schema/JSON-LD, Open Graph, Twitter metadata, sitemap, and robots patterns before editing.
- Use `includes/schema.php`, `includes/seo.php`, `includes/header.php`, sitemap routes, and nearby route code as the source of truth.
- Do not assert a public-domain redirect unless repo or server config proves it. Repo config currently uses `APP_URL` and `.env.example` as source-controlled domain guidance.

## Safe Admin Handler Editing

- Trace form to handler to database writes to redirects before editing.
- Preserve `admin_require_auth()`, sessions, CSRF checks, POST gates, prepared statements, redirects, permissions, validation, and `old_input_set()` behavior where present.
- Validate JSON before saving JSON fields.
- Do not assume optional tables or columns exist. Use `db_table_exists()` and `db_column_exists()` where older installs may differ.
- Avoid destructive DB/schema changes until schema, migrations, runtime guards, and affected queries have been inspected.

## Auth, Forms, And Security

- Keep admin auth (`includes/auth.php`) separate from customer auth (`includes/customer_auth.php`).
- Preserve CSRF token creation, `require_csrf()`, `guard_post_size()`, session behavior, safe redirects, and local-only form assumptions.
- Do not read `.env` unless the task explicitly requires it. Prefer `.env.example` for non-secret shape.
- Treat uploads, logs, lock files, `.claude/`, `.env*`, and local runtime artifacts as protected.

## Media And Alt Text

- Treat `assets/uploads/*` as local runtime content.
- Preserve responsive image helpers and stored media URL helpers.
- Keep image alt text meaningful, especially destination photo captions and public gallery content.
- Run `php cli\lint_image_alt.php` after media, gallery, destination photo, or content quality changes.

## Validation Commands And Smoke Workflow

Use the smallest useful validation set, then explain skipped checks.

```powershell
git status --short --branch
composer validate
composer test
git diff --check
```

For changed PHP files:

```powershell
php -l path\to\changed-file.php
```

For shared helpers, auth, config, routes, or many PHP files:

```powershell
$errors = @()
Get-ChildItem -Recurse -Filter *.php -File | Where-Object { $_.FullName -notlike '*\vendor\*' } | ForEach-Object {
    $result = php -l $_.FullName 2>&1
    if ($LASTEXITCODE -ne 0) { $errors += $result }
}
if ($errors.Count) { $errors; exit 1 }
```

For route/bootstrap/CSRF smoke and image quality:

```powershell
php cli\smoke_post_gates.php --only=dynamic
php cli\lint_image_alt.php
```

## Focused Skills

Use these project skills when the task matches a narrower lane:

- `.skills/island-public-seo-page-builder/SKILL.md`
- `.skills/island-public-uiux-linking-reviewer/SKILL.md`
- `.skills/island-admin-safe-editor/SKILL.md`
- `.skills/island-auth-security-hardener/SKILL.md`
- `.skills/island-test-smoke-runner/SKILL.md`
- `.skills/island-content-memory-updater/SKILL.md`

## Reporting Format

Report:
- Files changed.
- What changed in each file.
- Validation commands and results.
- Blockers or skipped checks with reasons.
- Stale information corrected, if documentation changed.
- Remaining ambiguity.
- Git status.

## Must Not Do

- Do not invent features, schema, routes, domains, redirects, or review history.
- Do not test live public domains unless explicitly asked.
- Do not push, deploy, upload, or run destructive database/schema actions without explicit approval.
- Do not commit unless the user asks.
- Do not commit `vendor/`, uploads, secrets, logs, lock/runtime files other than tracked `composer.lock`, or local environment files.
